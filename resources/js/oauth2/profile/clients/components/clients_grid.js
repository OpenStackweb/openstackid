import React, {useEffect, useState} from "react";
import {DataGrid, getGridDateOperators} from "@mui/x-data-grid";
import moment from "moment";
import DeleteIcon from "@material-ui/icons/Delete";
import EditIcon from "@material-ui/icons/Edit";
import PersonIcon from "@material-ui/icons/Person";
import {Checkbox, IconButton, Tooltip} from "@material-ui/core";
import {handleErrorResponse} from "../../../../utils";

const ClientsGrid = ({getClients, pageSize, clientsListChanged, onActivate, onDeactivate, onEdit, onDelete}) => {
    const [page, setPage] = useState(1);
    const [clientsRows, setClientsRows] = useState([]);
    const [clientsRowsCount, setClientsRowsCount] = useState(0);
    const [loading, setLoading] = useState(false);

    const [sortModel, setSortModel] = useState({});
    const [filterModel, setFilterModel] = useState({});

    const clientsColumns = [
        {
            field: 'is_own',
            headerName: ' ',
            width: 15,
            disableColumnMenu: true,
            sortable: false,
            renderCell: (params) => (
                !params.formattedValue &&
                <Tooltip title='you have admin rights on this application'>
                    <PersonIcon fontSize="small"/>
                </Tooltip>
            ),
        },
        {
            field: 'app_name',
            headerName: 'Application Name',
            width: 150,
            disableColumnMenu: true,
            sortable: false,
            renderCell: (params) => (
                <Tooltip title={params.formattedValue}>
                    <span>{params.formattedValue}</span>
                </Tooltip>
            ),
        },
        {
            field: 'friendly_application_type',
            headerName: 'Application Type',
            width: 150,
            disableColumnMenu: true,
            sortable: false,
            renderCell: (params) => (
                <Tooltip title={params.formattedValue}>
                    <span>{params.formattedValue}</span>
                </Tooltip>
            ),
        },
        {
            field: 'active',
            headerName: 'Is Active',
            width: 90,
            disableColumnMenu: true,
            sortable: false,
            renderCell: (params) => (
                <Checkbox
                    checked={params.row.active}
                    onChange={() => handleActiveChange(params.row)}
                    disabled={!params.row.is_own}
                />
            ),
        },
        {
            field: 'locked',
            headerName: 'Is Locked',
            type: 'boolean',
            width: 90,
            disableColumnMenu: true,
            sortable: false
        },
        {
            field: 'updated_at',
            headerName: 'Modified',
            type: 'date',
            width: 170,
            disableColumnMenu: true,
            sortable: false,
            filterOperators: getGridDateOperators().filter(
                operator => operator.value === 'after' || operator.value === 'before',
            ),
            valueFormatter: params => moment.unix(params?.value).format("DD/MM/YYYY hh:mm A")
        },
        {
            field: 'modified_by',
            headerName: 'Modified By',
            width: 140,
            disableColumnMenu: true,
            sortable: false,
            renderCell: (params) => (
                <Tooltip title={params.formattedValue}>
                    <span>{params.formattedValue}</span>
                </Tooltip>
            ),
        },
        {
            field: 'actions',
            headerName: ' ',
            width: 110,
            disableColumnMenu: true,
            sortable: false,
            renderCell: (params) => (<>
                <IconButton onClick={() => onEdit(params)}>
                    <EditIcon fontSize="small"/>
                </IconButton>
                {
                    params.row.is_own &&
                    <IconButton onClick={() => onDelete(params.id)}>
                        <DeleteIcon fontSize="small"/>
                    </IconButton>
                }
            </>),
        }
    ];

    const refreshClients = (active, page = 1, order = null, orderDir = 'desc', filters = {}) => {
        setLoading(true);
        getClients(page, order, orderDir, filters).then(res => {
            if (active) {
                setClientsRowsCount(res?.total ?? 0);
                setClientsRows(res?.data ?? []);
            }
            setLoading(false);
        });
    }

    useEffect(() => {
        let active = true;
        refreshClients(active, page, sortModel?.field, sortModel?.sort, filterModel);
        return () => {
            active = false;
        };
    }, [page, sortModel, filterModel, clientsListChanged]);

    const handleSortModelChange = (model) => {
        const currentSortModel = model[0];
        if (JSON.stringify(sortModel) !== JSON.stringify(currentSortModel)) {
            setSortModel(currentSortModel);
        }
    };

    const handleFilterChange = (model) => {
        const currentFilterModel = model.items[0];
        if (JSON.stringify(filterModel) !== JSON.stringify(currentFilterModel)) {
            setFilterModel(currentFilterModel);
        }
    };

    const toggleActive = (id, active) => active ? onDeactivate(id) : onActivate(id);

    const handleActiveChange = (clickedRow) => {
        toggleActive(clickedRow.id, clickedRow.active).then(() => {
            const updatedData = clientsRows.map((x) => {
                if (x.id === clickedRow.id) {
                    return {
                        ...x,
                        active: !clickedRow.active
                    };
                }
                return x;
            });
            setClientsRows(updatedData);
        }).catch((err) => {
            handleErrorResponse(err);
        });
    }

    return (
        <div style={{height: 650, width: '100%'}}>
            {clientsRows &&
                <DataGrid
                    rows={clientsRows}
                    columns={clientsColumns}
                    disableColumnSelector={true}
                    disableSelectionOnClick={true}
                    pagination
                    pageSize={pageSize}
                    rowsPerPageOptions={[pageSize]}
                    rowCount={clientsRowsCount}
                    paginationMode="server"
                    onPageChange={(newPage) => setPage(newPage + 1)}
                    sortingMode="server"
                    onSortModelChange={handleSortModelChange}
                    filterMode="server"
                    onFilterModelChange={handleFilterChange}
                    loading={loading}
                />
            }
        </div>
    );
}

export default ClientsGrid;