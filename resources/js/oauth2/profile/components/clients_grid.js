import React, {useEffect, useState} from "react";
import {DataGrid, getGridDateOperators} from "@mui/x-data-grid";
import moment from "moment";

const ClientsGrid = ({getClients, pageSize}) => {
    const [page, setPage] = useState(1);
    const [uaRows, setUARows] = useState([]);
    const [uaRowsCount, setUARowsCount] = useState(0);
    const [loading, setLoading] = useState(false);

    const [sortModel, setSortModel] = useState({});
    const [filterModel, setFilterModel] = useState({});

    const uaColumns = [
        {field: 'realm', headerName: 'From Realm', width: 400},
        {field: 'user_action', headerName: 'Action', width: 150},
        {field: 'from_ip', headerName: 'From IP', width: 150},
        {
            field: 'created_at',
            headerName: 'When (UTC)',
            type: 'date',
            width: 170,
            filterOperators: getGridDateOperators().filter(
                operator => operator.value === 'after' || operator.value === 'before',
            ),
            valueFormatter: params => moment.unix(params?.value).format("DD/MM/YYYY hh:mm A")
        },
    ];

    const refreshClients = (active, page = 1, order = 'created_at', orderDir = 'desc', filters = {}) => {
        setLoading(true);
        getClients(page, order, orderDir, filters).then(res => {
            if (active) {
                setUARowsCount(res?.total ?? 0);
                setUARows(res?.data ?? []);
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
    }, [page, sortModel, filterModel]);

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
            {uaRows &&
                <DataGrid
                    rows={uaRows}
                    columns={uaColumns}
                    disableColumnSelector={true}
                    pagination
                    pageSize={pageSize}
                    rowsPerPageOptions={[pageSize]}
                    rowCount={uaRowsCount}
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