import React, {useState} from "react";
import ReactDOM from "react-dom";
import ClientsGrid from "./components/clients_grid";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import Navbar from "../../../components/navbar/navbar";
import NewClientDialog from "./components/new_client_dialog";
import {activateClient, deactivateClient, getClients, addClient, deleteClient, PAGE_SIZE} from "./actions";
import {handleErrorResponse} from "../../../utils";
import Swal from "sweetalert2";
import TopLogo from "../../../components/top_logo/top_logo";
import {Card, CardContent, Container, CssBaseline, Grid, Tooltip, Typography} from "@material-ui/core";

import styles from "./clients.module.scss";

const ClientsPage = (
    {
        appLogo,
        csrfToken,
        editURL,
        fetchAdminUsersURL,
        menuConfig
    }) => {
    const [refreshClientsList, setRefreshClientsList] = useState(true);

    const handleEdit = (params) => {
        window.location.href = editURL.replace('@id', params.id);
    };

    const handleDelete = (id) => {
        Swal({
            title: 'Are you sure to delete this registered application?',
            text: 'This is an non reversible process!',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.value) {
                deleteClient(id, csrfToken).then(() => {
                    Swal("Client deleted", "The client has been deleted successfully", "success");
                    setRefreshClientsList(!refreshClientsList);
                }).catch((err) => {
                    handleErrorResponse(err);
                });
            }
        });
    };

    const handleActivate = (id) => {
        return activateClient(id, csrfToken);
    }

    const handleDeactivate = (id) => {
        return deactivateClient(id, csrfToken);
    }

    return (
        <Container component="main" maxWidth="xs" className={styles.main_container}>
            <CssBaseline/>
            <TopLogo appLogo={appLogo}/>
            <Navbar menuConfig={menuConfig}/>
            <Card className={styles.clients_container} variant="outlined">
                <CardContent>
                    <Grid
                        container
                        direction="column"
                        spacing={2}
                        justifyContent="center"
                    >
                        <Grid item container direction="row">
                            <Tooltip title='Users can keep track of their registered applications and manage them'>
                                <InfoOutlinedIcon/>
                            </Tooltip>
                            &nbsp;
                            <Typography variant="subtitle1">
                                Registered Applications
                            </Typography>
                        </Grid>
                        <Grid item container alignItems="center" justifyContent="center">
                            <ClientsGrid
                                onActivate={handleActivate}
                                onDeactivate={handleDeactivate}
                                getClients={
                                    (page, order, orderDir, filters) =>
                                        getClients(page, order, orderDir, filters, initialValues.id)
                                }
                                clientsListChanged={refreshClientsList}
                                pageSize={PAGE_SIZE}
                                onEdit={handleEdit}
                                onDelete={handleDelete}
                            />
                        </Grid>
                        <Grid item container alignItems="center" justifyContent="center">
                            <NewClientDialog
                                onSave={(values) => addClient({...values}, csrfToken)
                                    .then((res) => {
                                        setRefreshClientsList(!refreshClientsList)
                                        return res
                                    })}
                                fetchAdminUsersURL={fetchAdminUsersURL}/>
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
        <ClientsPage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);
