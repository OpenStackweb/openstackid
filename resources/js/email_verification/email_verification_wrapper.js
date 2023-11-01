import React from "react";
import ReactDOM from "react-dom";
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

ReactDOM.render(
    <MuiThemeProvider theme={theme}>
      <EmailVerificationPage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);
