import React from "react";
import ReactDOM from "react-dom";
import {createTheme, MuiThemeProvider} from "@material-ui/core/styles";
import {EditClientPage} from "./edit_client";

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
        <EditClientPage {...config} entity={entity}/>
    </MuiThemeProvider>,
    document.querySelector("#root")
);