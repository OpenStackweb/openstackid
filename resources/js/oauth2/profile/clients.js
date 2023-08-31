import React from "react";
import ReactDOM from "react-dom";
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import {MuiThemeProvider, createTheme, withStyles} from "@material-ui/core/styles";
import Card from "@material-ui/core/Card";
import CardContent from "@material-ui/core/CardContent";
import Grid from "@material-ui/core/Grid";
import TopLogo from "../../components/top_logo/top_logo";
import Navbar from "../../components/navbar/navbar";
import ClientsGrid from "./components/clients_grid";
import {getClients, PAGE_SIZE} from "./actions";

import styles from "./clients.module.scss";

const ClientsPage = (
    {
        appLogo,
        menuConfig,
    }) => {
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
                        <Grid item container alignItems="center" justifyContent="center">
                            <ClientsGrid getUserActions={
                                (page, order, orderDir, filters) =>
                                    getClients(page, order, orderDir, filters, initialValues.id)
                            } pageSize={PAGE_SIZE}/>
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
