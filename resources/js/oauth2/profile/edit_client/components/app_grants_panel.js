import React, {useState} from "react";
import {Grid, IconButton, Tooltip, Typography} from "@material-ui/core";
import RefreshIcon from '@material-ui/icons/Refresh';
import TokensGrid from "./tokens_grid";
import Swal from "sweetalert2";
import {handleErrorResponse} from "../../../../utils";

const AppGrantsPanel = ({getAccessTokens, onRevokeAccessToken, getRefreshTokens, onRevokeRefreshToken}) => {
    const [accessTokensListRefresh, setAccessTokensListRefresh] = useState(true);
    const [refreshTokensListRefresh, setRefreshTokensListRefresh] = useState(true);

    const reloadAccessTokensList = () => {
        setAccessTokensListRefresh(!accessTokensListRefresh);
    }

    const reloadRefreshTokensList = () => {
        setRefreshTokensListRefresh(!refreshTokensListRefresh);
    }

    const confirmRevocation = (id, value, subjectName, callback, reloadCallback) => {
        Swal({
            title: 'Are you sure to revoke this token?',
            text: 'This is an non reversible process!',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, revoke it!'
        }).then((result) => {
            if (result.value) {
                callback(id, value).then(() => {
                    Swal(`${subjectName} revoked`, `The ${subjectName} has been revoked successfully`, "success");
                    reloadCallback();
                }).catch((err) => {
                    handleErrorResponse(err);
                });
            }
        });
    };

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
                    <IconButton size="small" onClick={reloadAccessTokensList}>
                        <RefreshIcon fontSize="small"/>
                    </IconButton>
                </Tooltip>
            </Grid>
            <Grid item>
                <TokensGrid
                    getTokens={getAccessTokens}
                    pageSize={6}
                    tokensListChanged={accessTokensListRefresh}
                    noTokensMessage="** There are not currently access tokens granted for this application."
                    onRevoke={(id, value) => {
                        confirmRevocation(id, value, 'access token', onRevokeAccessToken, reloadAccessTokensList)
                    }}
                />
            </Grid>
            <Grid item container direction="row" alignItems="center">
                <Typography variant="subtitle2" display="inline">Issued Refresh Tokens</Typography>
                <Tooltip title="Update Refresh Tokens List">
                    <IconButton size="small" onClick={reloadRefreshTokensList}>
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
                    onRevoke={(id, value) => {
                        confirmRevocation(id, value, 'refresh token', onRevokeRefreshToken, reloadRefreshTokensList)
                    }}
                />
            </Grid>
        </Grid>
    );
}

export default AppGrantsPanel;