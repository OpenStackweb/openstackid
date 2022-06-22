import ReactDOM from "react-dom";
import {createMuiTheme, MuiThemeProvider} from "@material-ui/core/styles";
import React from "react";
import SelectAccountPage from './select_account';
import { Provider } from 'react-redux'
import store, {persistor} from '../store';
import { PersistGate } from 'redux-persist/es/integration/react'

const onBeforeLift = () => {
    console.log("reading state ...")
}

const theme = createMuiTheme({
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
    <Provider store={store}>
            <PersistGate
                onBeforeLift={onBeforeLift}
                persistor={persistor}>
                <MuiThemeProvider theme={theme}>
                    <SelectAccountPage {...config} />
                </MuiThemeProvider>
            </PersistGate>
    </Provider>,
    document.querySelector('#root')
);