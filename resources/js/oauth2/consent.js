import React, {useRef, useState} from "react";
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
import {ClickAwayListener} from "@material-ui/core";

import styles from "./consent.module.scss";

const HtmlTooltip = withStyles((theme) => ({
    tooltip: {
        backgroundColor: '#fff',
        color: 'rgba(0, 0, 0, 0.90)',
        maxWidth: 350,
        fontSize: theme.typography.pxToRem(11),
        border: '1px solid #dadde9',
    },
}))(Tooltip);

const ConsentPage = (
    {
        appLogo,
        appName,
        appDescription,
        contactEmail,
        csrfToken,
        disclaimer,
        formAction,
        redirectURL,
        requestedScopes
    }) => {
    const formEl = useRef(null);
    const trustEl = useRef(null);
    const [open, setOpen] = useState(false);
    const [cancelButtonDisabled, setCancelButtonDisabled] = useState(false);
    const [acceptButtonDisabled, setAcceptButtonDisabled] = useState(false);

    const handleTooltipClose = () => {
        setOpen(false);
    };

    const handleTooltipOpen = () => {
        setOpen(true);
    };

    const handleAccept = (e) => {
        setAcceptButtonDisabled(true)
        trustEl.current.value = 'AllowOnce';
        formEl.current.submit();
        e.preventDefault();
    }

    const handleCancel = (e) => {
        setCancelButtonDisabled(true)
        trustEl.current.value = 'DenyOnce';
        formEl.current.submit();
        e.preventDefault();
    }

    return (
        <Container component="main" maxWidth="xs" className={styles.main_container}>
            <CssBaseline/>
            <div className={styles.title_container}>
                <a href={window.location.href} target='_self'>
                    <img className={styles.app_logo} alt="idpLogo" src={appLogo}/>
                </a>
                <h1>
                    <a target='_blank' href={redirectURL}>{appName}</a>&nbsp;
                    <ClickAwayListener onClickAway={handleTooltipClose}>
                        <HtmlTooltip
                            arrow
                            PopperProps={{
                                disablePortal: true,
                            }}
                            onClose={handleTooltipClose}
                            open={open}
                            disableFocusListener
                            disableHoverListener
                            disableTouchListener
                            interactive
                            title={
                                <React.Fragment>
                                    <Typography color="inherit">{appName}</Typography>
                                    <hr/>
                                    <div>{appDescription}</div>
                                    <hr/>
                                    {contactEmail &&
                                        <div>Contact Email: <a href={`mailto:${contactEmail}`}>{contactEmail}</a>.
                                        </div>}
                                    <div>Clicking 'Accept' will redirect you to: <a href={redirectURL}
                                                                                    target='_blank'>{redirectURL}</a>.
                                    </div>
                                </React.Fragment>
                            }
                        >
                            <InfoOutlinedIcon onClick={handleTooltipOpen}/>
                        </HtmlTooltip>
                    </ClickAwayListener>
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
                                        disabled={cancelButtonDisabled}
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
                                        disabled={acceptButtonDisabled}
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
