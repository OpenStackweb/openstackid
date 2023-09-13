import React, {useState} from "react";
import {Grid, IconButton, Tooltip, Typography} from "@material-ui/core";
import RefreshIcon from '@material-ui/icons/Refresh';
import TokensGrid from "./tokens_grid";

const AppGrantsPanel = ({getAccessTokens, getRefreshTokens}) => {
    const [accessTokensListRefresh, setAccessTokensListRefresh] = useState(true);
    const [refreshTokensListRefresh, setRefreshTokensListRefresh] = useState(true);

    return (
        <Grid
            container
            direction="column"
            spacing={2}
            justifyContent="center"
        >
            <Grid item container direction="row" alignItems="center">
                <Typography variant="subtitle2" display="inline">Issued Access Tokens</Typography>
                <Tooltip title="Update Access Tokens List">
                    <IconButton size="small" onClick={() => {
                        setAccessTokensListRefresh(!accessTokensListRefresh)
                    }}>
                        <RefreshIcon fontSize="small"/>
                    </IconButton>
                </Tooltip>
            </Grid>
            <Grid item>
                <TokensGrid
                    getTokens={getAccessTokens}
                    pageSize={6}
                    tokensListChanged={accessTokensListRefresh}
                    noTokensMessage="** There are not any Access Tokens granted for this application."
                    onRevoke={(id) => {
                        console.log(`Refresh Token ${id} revoked`)
                    }}/>
            </Grid>
            <Grid item container direction="row" alignItems="center">
                <Typography variant="subtitle2" display="inline">Issued Refresh Tokens</Typography>
                <Tooltip title="Update Refresh Tokens List">
                    <IconButton size="small" onClick={() => {
                        setRefreshTokensListRefresh(!refreshTokensListRefresh)
                    }}>
                        <RefreshIcon fontSize="small"/>
                    </IconButton>
                </Tooltip>
            </Grid>
            <Grid item>
                <TokensGrid
                    getTokens={getRefreshTokens}
                    pageSize={6}
                    tokensListChanged={refreshTokensListRefresh}
                    noTokensMessage="** There are not currently refresh tokens issued for this user."
                    onRevoke={(id) => {
                        console.log(`Refresh Token ${id} revoked`)
                    }}/>
            </Grid>
        </Grid>
    );
}

export default AppGrantsPanel;