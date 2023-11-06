import React from "react";
import ReactDOM from "react-dom";
import {LoginPage} from "./login";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";

const theme = createTheme({
    palette: {
        primary: {
            main: '#3fa2f7'
        },
    },
    overrides: {
        MuiButton: {
            containedPrimary: {
                color: 'white'
            }
        }
    }
});

ReactDOM.render(
    <MuiThemeProvider theme={theme}>
        <LoginPage {...config} />
    </MuiThemeProvider>,
    document.querySelector('#root')
);
