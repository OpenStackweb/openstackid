import React, {useEffect, useState} from "react";
import {DataGrid, getGridDateOperators} from "@mui/x-data-grid";
import moment from "moment";
import {Button, Paper, Tooltip} from "@material-ui/core";

const TokensGrid = ({getTokens, pageSize, tokensListChanged, noTokensMessage, onRevoke}) => {
    const [page, setPage] = useState(1);
    const [tokensRows, setTokensRows] = useState([]);
    const [tokensRowsCount, setTokensRowsCount] = useState(0);
    const [loading, setLoading] = useState(false);

    const [sortModel, setSortModel] = useState({});
    const [filterModel, setFilterModel] = useState({});

    const tokensColumns = [
        {
            field: 'created_at',
            headerName: 'Issued',
            type: 'date',
            width: 170,
            disableColumnMenu: true,
            sortable: false,
            filterOperators: getGridDateOperators().filter(
                operator => operator.value === 'after' || operator.value === 'before',
            ),
            valueFormatter: params => moment.unix(params?.value).format("YYYY-MM-DD HH:mm:ss")
        },
        {
            field: 'scope',
            headerName: 'Scopes',
            width: 450,
            disableColumnMenu: true,
            sortable: false,
        },
        {
            field: 'remaining_lifetime',
            headerName: 'Remaining Lifetime',
            width: 160,
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
            renderCell: (params) => (
                <Button variant="contained" color="primary" onClick={() => onRevoke(params.id)}>
                    Revoke
                </Button>
            ),
        }
    ];

    const reloadTokens = (active, page = 1, perPage) => {
        setLoading(true);
        getTokens(page, perPage).then(res => {
            if (active) {
                setTokensRowsCount(res?.total ?? 0);
                setTokensRows(res?.data ?? []);
            }
            setLoading(false);
        });
    }

    useEffect(() => {
        let active = true;
        reloadTokens(active, page, pageSize);
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
        <>
            {tokensRows.length > 0 ?
                <div style={{height: 450, width: '100%'}}>
                    <DataGrid
                        rows={tokensRows}
                        columns={tokensColumns}
                        disableColumnSelector={true}
                        disableSelectionOnClick={true}
                        pagination
                        pageSize={pageSize}
                        rowsPerPageOptions={[pageSize]}
                        rowCount={tokensRowsCount}
                        paginationMode="server"
                        onPageChange={(newPage) => setPage(newPage + 1)}
                        sortingMode="server"
                        onSortModelChange={handleSortModelChange}
                        filterMode="server"
                        onFilterModelChange={handleFilterChange}
                        loading={loading}
                    />
                </div>
                :
                <Paper variant="outlined">
                    {noTokensMessage}
                </Paper>
            }
        </>
    );
}

export default TokensGrid;