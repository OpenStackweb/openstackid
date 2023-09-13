import React, {useEffect, useState} from "react";
import ReactDOM from "react-dom";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";
import AssignmentIcon from "@material-ui/icons/Assignment";
import CheckCircleIcon from "@material-ui/icons/CheckCircle";
import ExpandMoreIcon from "@material-ui/icons/ExpandMore";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import Swal from "sweetalert2";
import Navbar from "../../../components/navbar/navbar";
import TopLogo from "../../../components/top_logo/top_logo";
import OauthPanel from "./components/oauth_panel";
import AllowedScopesPanel from "./components/allowed_scopes_panel";
import AppGrantsPanel from "./components/app_grants_panel";
import SecuritySettingsPanel from "./components/security_settings_panel";
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Card,
    CardContent,
    Container,
    CssBaseline,
    Divider,
    Grid,
    Tooltip,
    Typography
} from "@material-ui/core";
import {addScope, getAccessTokens, getRefreshTokens, removeScope} from "./actions";

import styles from "./edit_client.module.scss";

const EditClientPage = (
    {
        appLogo,
        clientId,
        clientName,
        csrfToken,
        editorName,
        editURL,
        fetchAdminUsersURL,
        isOwner,
        menuConfig,
        ownerName,
        publicKeys,
        scopes,
        selectedScopes,
        supportedContentEncryptionAlgorithms,
        supportedKeyManagementAlgorithms,
        supportedSigningAlgorithms,
    }) => {
    const [selScopes, setSelScopes] = useState([]);
    const [copyingScopes, setCopyingScopes] = useState(false);
    const [expanded, setExpanded] = useState(false);

    useEffect(() => {
        setSelScopes(selectedScopes);
    }, []);

    const handleScopeSelected = (scopeId) => {
        setSelScopes([...new Set([...selScopes, scopeId])]);
        addScope(clientId, scopeId, csrfToken)
            .catch((err) => {
                Swal("Something went wrong!", "Can't add this scope", "error");
            });
    }

    const handleScopeUnselected = (scopeId) => {
        setSelScopes([...selScopes.filter(id => scopeId !== id)]);
        removeScope(clientId, scopeId, csrfToken)
            .catch((err) => {
                Swal("Something went wrong!", "Can't remove this scope", "error");
            });
    }

    const handleCopyScopes = (e) => {
        e.stopPropagation();
        setCopyingScopes(true);
        navigator.clipboard.writeText(JSON.stringify(selScopes)).then(() => {
            setTimeout(() => {
                setCopyingScopes(false);
            }, 1000);
        });
    }

    const handleAccordionChange = (panel) => (event, isExpanded) => {
        setExpanded(isExpanded ? panel : false);
    };

    return (
        <Container component="main" maxWidth="xs" className={styles.main_container}>
            <CssBaseline/>
            <TopLogo appLogo={appLogo}/>
            <Navbar menuConfig={menuConfig}/>
            <Card className={styles.client_container} variant="outlined">
                <CardContent>
                    <Grid
                        container
                        direction="column"
                        spacing={2}
                        justifyContent="center"
                    >
                        <Grid item container direction="row">
                            <Tooltip
                                title='OAuth 2.0 allows users to share specific data with you (for example, contact lists) while keeping their usernames, passwords, and other information private.'>
                                <InfoOutlinedIcon/>
                            </Tooltip>
                            &nbsp;
                            <Typography variant="subtitle1">
                                {clientName} - Client # {clientId}
                            </Typography>
                        </Grid>
                        <Divider/>
                        <Grid item container direction="row">
                            <Grid item xs={2}>
                                <Typography variant="subtitle2">
                                    Created By:
                                </Typography>
                            </Grid>
                            <Grid item xs={4}>
                                <Typography variant="body2">
                                    {ownerName}
                                </Typography>
                            </Grid>
                        </Grid>
                        <Grid item container direction="row">
                            <Grid item xs={2}>
                                <Typography variant="subtitle2">
                                    Edited By:
                                </Typography>
                            </Grid>
                            <Grid item xs={4}>
                                <Typography variant="body2">
                                    {editorName}
                                </Typography>
                            </Grid>
                        </Grid>
                        <Grid item container alignItems="center" justifyContent="center">
                            <Accordion className={styles.accordion}
                                       expanded={expanded === "oauth2-panel"}
                                       onChange={handleAccordionChange("oauth2-panel")}>
                                <AccordionSummary
                                    expandIcon={<ExpandMoreIcon/>}
                                    aria-controls="oauth2-panel-content"
                                    id="oauth2-panel-header"
                                >
                                    <Typography>OAuth 2.0 Client Data</Typography>
                                </AccordionSummary>
                                <AccordionDetails>
                                    <OauthPanel initialValues={initialValues} isOwner={isOwner}/>
                                </AccordionDetails>
                            </Accordion>
                            <Accordion className={styles.accordion}
                                       expanded={expanded === "allowed-scopes-panel"}
                                       onChange={handleAccordionChange("allowed-scopes-panel")}>
                                <AccordionSummary
                                    expandIcon={<ExpandMoreIcon/>}
                                    aria-controls="allowed-scopes-panel-content"
                                    id="allowed-scopes-panel-header"
                                >
                                    <Typography>Application Allowed Scopes</Typography>
                                    &nbsp;
                                    {copyingScopes ?
                                        <CheckCircleIcon/>
                                        :
                                        <Tooltip title="Copy Allowed Scopes to Clipboard">
                                            <AssignmentIcon onClick={handleCopyScopes}/>
                                        </Tooltip>
                                    }
                                </AccordionSummary>
                                <AccordionDetails>
                                    <AllowedScopesPanel
                                        scopes={scopes}
                                        selectedScopes={selScopes}
                                        onScopeSelected={handleScopeSelected}
                                        onScopeUnselected={handleScopeUnselected}
                                    />
                                </AccordionDetails>
                            </Accordion>
                            <Accordion className={styles.accordion}
                                       expanded={expanded === "app-grants-panel"}
                                       onChange={handleAccordionChange("app-grants-panel")}>
                                <AccordionSummary
                                    expandIcon={<ExpandMoreIcon/>}
                                    aria-controls="app-grants-panel-content"
                                    id="app-grants-panel-header"
                                >
                                    <Typography>Application Grants</Typography>
                                </AccordionSummary>
                                <AccordionDetails>
                                    <AppGrantsPanel
                                        getAccessTokens={(page, perPage) => getAccessTokens(clientId, page, perPage)}
                                        getRefreshTokens={(page, perPage) => getRefreshTokens(clientId, page, perPage)}
                                    />
                                </AccordionDetails>
                            </Accordion>
                            <Accordion className={styles.accordion}
                                       expanded={expanded === "security-panel"}
                                       onChange={handleAccordionChange("security-panel")}>
                                <AccordionSummary
                                    expandIcon={<ExpandMoreIcon/>}
                                    aria-controls="security-panel-content"
                                    id="security-panel-header"
                                >
                                    <Typography>Security Settings</Typography>
                                </AccordionSummary>
                                <AccordionDetails>
                                    <SecuritySettingsPanel
                                        initialValues={initialValues}
                                        publicKeys={publicKeys}
                                        supportedContentEncryptionAlgorithms={supportedContentEncryptionAlgorithms}
                                        supportedKeyManagementAlgorithms={supportedKeyManagementAlgorithms}
                                        supportedSigningAlgorithms={supportedSigningAlgorithms}
                                    />
                                </AccordionDetails>
                            </Accordion>
                        </Grid>
                    </Grid>
                </CardContent>
            </Card>
        </Container>
    );
};

// Or Create your Own theme:
const theme = createTheme({
    palette: {
        primary: {
            main: "#3fa2f7",
        },
    },
    overrides: {
        MuiButton: {
            containedPrimary: {
                color: "white",
            },
        },
    },
});

ReactDOM.render(
    <MuiThemeProvider theme={theme}>
        <EditClientPage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);