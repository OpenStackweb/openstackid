import styles from './select_account.module.scss'
import React from 'react';
import ReactDOM from 'react-dom';
import Avatar from '@material-ui/core/Avatar';
import { MuiThemeProvider, createMuiTheme } from '@material-ui/core/styles';
import IconButton from '@material-ui/core/IconButton';
import DeleteIcon from '@material-ui/icons/Delete';
import CssBaseline from "@material-ui/core/CssBaseline";
import Container from '@material-ui/core/Container';
import List from '@material-ui/core/List';
import ListItem from '@material-ui/core/ListItem';
import Divider from '@material-ui/core/Divider';
import ListItemText from '@material-ui/core/ListItemText';
import ListItemAvatar from '@material-ui/core/ListItemAvatar';
import Typography from '@material-ui/core/Typography';
import ListItemSecondaryAction from '@material-ui/core/ListItemSecondaryAction';
import Tooltip from '@material-ui/core/Tooltip';
import {removeFormerAccount} from './actions';
import Swal from "sweetalert2";

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


const SelectAccountPage = (props) => {
    const { accounts } = props;

    const onHandleSelectAccount = (account) => {
        window.location = `/auth/login?login_hint=${encodeURIComponent(account.username)}`;
    }

    const onHandleRemoveAccount = (account) => {
        removeFormerAccount(account.username, props.token).then((payload) => {
            let { response } = payload;
        }, (error) => {
            let { response, status, message } = error;
            Swal('Oops...', 'Something went wrong!', 'error')
        });
    }

    return (
        <Container component="main" maxWidth="xs" className={styles.main_container}>
            <CssBaseline />
            <Container className={styles.login_container}>
                <div className={styles.inner_container}>
                    <Typography component="h1" className={styles.app_logo_container}>
                        <a href={window.location.href}><img className={styles.app_logo} alt={props.appName} src={props.appLogo} /></a>
                    </Typography>
                    <Typography component="h1" variant="h5" style={{ textAlign: "center"}}>
                        Select an Account
                    </Typography>
                    <List style={{ width: '100%', backgroundColor: 'background.paper' }}>
                        {
                            accounts.map(a => {
                                return (
                                    <>
                                        <ListItem
                                            title={`Proceed with ${a.full_name} ...`}
                                            button
                                            onClick={(ev) => {onHandleSelectAccount(a)}}
                                            alignItems="flex-start"
                                            disablePadding
                                            key={a.username}>
                                            <ListItemAvatar>
                                                <Avatar alt={a.full_name} src={a.pic} />
                                            </ListItemAvatar>
                                            <ListItemText
                                                primary={a.full_name}
                                                secondary={
                                                    <React.Fragment>
                                                        <Typography
                                                            style={{ display: 'inline' }}
                                                            component="span"
                                                            variant="body2"
                                                            color="text.primary">
                                                            {a.username}
                                                        </Typography>
                                                    </React.Fragment>
                                                }
                                            />
                                            <ListItemSecondaryAction>
                                                <Tooltip title="remove" aria-label="remove">
                                                    <IconButton edge="end" aria-label="remove" onClick={(ev) => {onHandleRemoveAccount(a)}}>
                                                        <DeleteIcon />
                                                    </IconButton>
                                                </Tooltip>
                                            </ListItemSecondaryAction>
                                        </ListItem>
                                        <Divider variant="inset" component="li" />
                                    </>
                                )
                            })
                        }

                    </List>
                </div>
            </Container>
        </Container>
    );
}


ReactDOM.render(
    <MuiThemeProvider theme={theme}>
        <SelectAccountPage {...config} />
    </MuiThemeProvider>,
    document.querySelector('#root')
);