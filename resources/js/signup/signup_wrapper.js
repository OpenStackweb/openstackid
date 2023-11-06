import React from "react";
import ReactDOM from "react-dom";
import {SignUpPage} from "./signup";
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
        <SignUpPage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);
