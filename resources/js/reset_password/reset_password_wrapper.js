import React from "react";
import ReactDOM from "react-dom";
import {ResetPasswordPage} from "./reset_password";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";

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
        <ResetPasswordPage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);

