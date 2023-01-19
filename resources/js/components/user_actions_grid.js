import React, {useEffect, useState} from "react";
import {DataGrid} from "@mui/x-data-grid";
import {getUserActions, PAGE_SIZE} from "../profile/actions";
import moment from "moment";

const UserActionsGrid = () => {
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
            field: 'created_at', headerName: 'When (UTC)', width: 170, valueFormatter: params =>
                moment.unix(params?.value).format("DD/MM/YYYY hh:mm A")
        },
    ];

    const refreshUserActions = (active, page = 1, order = 'created_at', orderDir = 'asc', filters = {}) => {
        setLoading(true);
        getUserActions(page, order, orderDir, filters).then(res => {
            if (active) {
                setUARowsCount(res?.total ?? 0);
                setUARows(res?.data ?? []);
            }
            setLoading(false);
        });
    }

    useEffect(() => {
        let active = true;
        refreshUserActions(active, page, sortModel?.field, sortModel?.sort, filterModel);
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
            {uaRows?.length > 0 &&
                <DataGrid
                    rows={uaRows}
                    columns={uaColumns}
                    pagination
                    pageSize={PAGE_SIZE}
                    rowsPerPageOptions={[PAGE_SIZE]}
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

export default UserActionsGrid;