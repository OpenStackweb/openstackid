import React from "react";
import ReactDOM from "react-dom";
import {ProfilePage} from "./profile";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";

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

Object.assign(theme, {
    overrides: {
        MUIRichTextEditor: {
            root: {
                marginTop: 5,
                height: 400,
                border: "1px solid #D3D3D3",
                borderRadius: "5px"
            },
            editor: {
                borderTop: "1px solid #D3D3D3"
            }
        }
    }
})

ReactDOM.render(
    <MuiThemeProvider theme={theme}>
        <ProfilePage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);