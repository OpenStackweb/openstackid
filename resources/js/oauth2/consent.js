import React, {useRef} from "react";
import ReactDOM from "react-dom";
import Button from '@material-ui/core/Button';
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import {MuiThemeProvider, createTheme, withStyles} from "@material-ui/core/styles";
import Card from "@material-ui/core/Card";
import CardHeader from "@material-ui/core/CardHeader";
import CardContent from "@material-ui/core/CardContent";
import Divider from "@material-ui/core/Divider";
import Grid from "@material-ui/core/Grid";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import Typography from "@material-ui/core/Typography";
import Tooltip from '@material-ui/core/Tooltip';

import styles from "./consent.module.scss";

const HtmlTooltip = withStyles((theme) => ({
    tooltip: {
        backgroundColor: '#f5f5f9',
        color: 'rgba(0, 0, 0, 0.87)',
        maxWidth: 220,
        fontSize: theme.typography.pxToRem(12),
        border: '1px solid #dadde9',
    },
}))(Tooltip);

const ConsentPage = ({appLogo, appName, appDescription, csrfToken, disclaimer, requestedScopes, formAction}) => {
    const formEl = useRef(null);
    const trustEl = useRef(null);

    const handleAccept = (e) => {
        trustEl.current.value = 'AllowOnce';
        formEl.current.submit();
        e.preventDefault();
    }

    const handleCancel = (e) => {
        trustEl.current.value = 'DenyOnce';
        formEl.current.submit();
        e.preventDefault();
    }

    return (
        <Container component="main" maxWidth="xs" className={styles.main_container}>
            <CssBaseline/>
            <div className={styles.title_container}>
                <a href={window.location.href} target='_self'>
                    <img className={styles.app_logo} alt="appLogo" src={appLogo}/>
                </a>
                <h1>{appName}&nbsp;
                    <HtmlTooltip
                        arrow
                        title={
                            <>
                                <Typography color="inherit">{appName}</Typography>
                                {appDescription}
                            </>
                        }
                    >
                        <InfoOutlinedIcon/>
                    </HtmlTooltip>
                </h1>
            </div>
            <Card className={styles.consent_container} variant="outlined">
                <CardHeader title='This app would like to:'/>
                <Divider/>
                <CardContent>
                    <form
                        ref={formEl}
                        method="post"
                        action={formAction}
                        autoComplete="off"
                        target="_self">
                        <Grid
                            container
                            direction="column"
                            spacing={2}
                            justifyContent="center">
                            <Grid item>
                                <div className={styles.scopes_list_container}>
                                    {
                                        requestedScopes.map((s, ix) => {
                                            return <div className={styles.scope_item} key={ix}
                                                        aria-hidden="true">{s}&nbsp;
                                                <HtmlTooltip arrow title={<Typography color="inherit">{s}</Typography>}>
                                                    <InfoOutlinedIcon fontSize="small"/>
                                                </HtmlTooltip>
                                            </div>;
                                        })
                                    }
                                </div>
                            </Grid>
                            <Grid item className={styles.disclaimer}>
                                <Typography variant="body2" dangerouslySetInnerHTML={{__html: disclaimer}}>
                                </Typography>
                            </Grid>
                            <Grid
                                item
                                container
                                direction="row"
                                spacing={2}
                                justifyContent="center"
                                alignItems="center">
                                <Grid item container xs={6} justifyContent="center">
                                    <Button
                                        id="cancel-authorization"
                                        variant="outlined"
                                        size="large"
                                        className={styles.button}
                                        disableElevation
                                        type="button"
                                        onClick={handleCancel}
                                    >
                                        Cancel
                                    </Button>
                                </Grid>
                                <Grid item container xs={6} justifyContent="center">
                                    <Button
                                        id="approve-authorization"
                                        variant="contained"
                                        size="large"
                                        className={styles.summit_button}
                                        disableElevation
                                        type="button"
                                        onClick={handleAccept}
                                    >
                                        Accept
                                    </Button>
                                </Grid>
                            </Grid>
                        </Grid>
                        <input type="hidden" value={csrfToken} id="_token" name="_token"/>
                        <input type="hidden" ref={trustEl} name='trust' id='trust' value=""/>
                    </form>
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
        <ConsentPage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);
