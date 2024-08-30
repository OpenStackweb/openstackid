import React, {useEffect, useState} from "react";
import {DataGrid, getGridDateOperators} from "@mui/x-data-grid";
import moment from "moment";
import Tooltip from '@material-ui/core/Tooltip'
import {Button} from "@material-ui/core";

const UserAccessTokensGrid = ({getUserAccessTokens, pageSize, tokensListChanged, onRevoke}) => {
    const [page, setPage] = useState(1);
    const [uatRows, setUATRows] = useState([]);
    const [uatRowsCount, setUATRowsCount] = useState(0);
    const [loading, setLoading] = useState(false);

    const [sortModel, setSortModel] = useState({});
    const [filterModel, setFilterModel] = useState({});

    const uaColumns = [
        {field: 'client_name', headerName: 'Client Name', width: 110},
        {
            field: 'created_at',
            headerName: 'Created At (UTC)',
            type: 'date',
            width: 180,
            filterOperators: getGridDateOperators().filter(
                operator => operator.value === 'after' || operator.value === 'before',
            ),
            valueFormatter: params => moment.unix(params?.value).format("DD/MM/YYYY hh:mm:ss A")
        },
        {field: 'remaining_lifetime', headerName: 'Remaining Lifetime', width: 160},
        {field: 'from_ip', headerName: 'From IP', width: 120},
        {
            field: 'browser_info', headerName: 'Browser Info', width: 140 ,
            renderCell: (params) =>  (
                <Tooltip title={params.value} >
                    <span className="table-cell-trucate">{params.value}</span>
                </Tooltip>
            ),
        },
        {
            field: 'scope', headerName: 'Scopes', width: 120 ,
            renderCell: (params) =>  (
                <Tooltip title={params.value} >
                    <span className="table-cell-trucate">{params.value}</span>
                </Tooltip>
            ),
        },
        {
            field: 'actions',
            headerName: ' ',
            width: 110,
            disableColumnMenu: true,
            sortable: false,
            renderCell: (params) => (
                <Button variant="contained" color="primary" onClick={() => onRevoke(params.row.value)}>
                    Revoke
                </Button>
            ),
        }
    ];

    const refreshUserAccessTokens = (active, page = 1, order = 'created_at', orderDir = 'desc', filters = {}) => {
        setLoading(true);
        getUserAccessTokens(page, order, orderDir, filters).then(res => {
            if (active) {
                setUATRowsCount(res?.total ?? 0);
                setUATRows(res?.data ?? []);
            }
            setLoading(false);
        });
    }

    useEffect(() => {
        let active = true;
        refreshUserAccessTokens(active, page, sortModel?.field, sortModel?.sort, filterModel);
        return () => {
            active = false;
        };
    }, [page, sortModel, filterModel, tokensListChanged]);

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

    return (
        <div style={{height: 650, width: '100%'}}>
            {uatRows &&
                <DataGrid
                    rows={uatRows}
                    columns={uaColumns}
                    disableColumnSelector={true}
                    pagination
                    pageSize={pageSize}
                    rowsPerPageOptions={[pageSize]}
                    rowCount={uatRowsCount}
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

export default UserAccessTokensGrid;