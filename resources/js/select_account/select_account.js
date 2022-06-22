import styles from './select_account.module.scss'
import React, {useEffect} from 'react';
import Avatar from '@material-ui/core/Avatar';
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
import Swal from "sweetalert2";
import {connect} from 'react-redux';
import {loadFormerAccounts, removeFormerAccount} from './actions'
import Link from "@material-ui/core/Link";

const SelectAccountPage = ({formerAccounts, loadFormerAccounts, removeFormerAccount, ...rest}) => {

    useEffect(() => {
            const {accounts} = rest;
            loadFormerAccounts(accounts);
        },
        []);

    const onHandleSelectAccount = (account) => {
        window.location = `/auth/login?login_hint=${encodeURIComponent(account.username)}`;
    }

    const onHandleRemoveAccount = (account) => {
        removeFormerAccount(account.username).then((payload) => {
            console.log(payload)
        }).catch((error) => {
            let {response, status, message} = error;
            Swal('Oops...', 'Something went wrong!', 'error')
        });
    }

    if (!formerAccounts.length) return null;

    return (
        <Container component="main" maxWidth="xs" className={styles.main_container}>
            <CssBaseline/>
            <Container className={styles.login_container}>
                <div className={styles.inner_container}>
                    <Typography component="h1" className={styles.app_logo_container}>
                        <a href={window.location.href}><img className={styles.app_logo} alt={rest.appName}
                                                            src={rest.appLogo}/></a>
                    </Typography>
                    <Typography component="h1" variant="h5" style={{textAlign: "center"}}>
                        Choose an account
                    </Typography>
                    <List style={{width: '100%', backgroundColor: 'background.paper'}}>
                        {
                            formerAccounts.map( ( a, idx ) => {
                                return (
                                    <React.Fragment key={idx}>
                                        <ListItem
                                            title={`Proceed as ${a.full_name} .`}
                                            button
                                            onClick={(ev) => {
                                                onHandleSelectAccount(a)
                                            }}
                                            alignItems="flex-start">
                                            <ListItemAvatar>
                                                <Avatar alt={a.full_name} src={a.pic}/>
                                            </ListItemAvatar>
                                            <ListItemText
                                                primary={a.full_name}
                                                secondary={
                                                    <React.Fragment>
                                                        <Typography
                                                            style={{display: 'inline'}}
                                                            component="span"
                                                            variant="body2"
                                                            color="textPrimary">
                                                            {a.username}
                                                        </Typography>
                                                    </React.Fragment>
                                                }
                                            />
                                            <ListItemSecondaryAction>
                                                <Tooltip title="remove" aria-label="remove">
                                                    <IconButton edge="end" aria-label="remove" onClick={(ev) => {
                                                        onHandleRemoveAccount(a)
                                                    }}>
                                                        <DeleteIcon/>
                                                    </IconButton>
                                                </Tooltip>
                                            </ListItemSecondaryAction>
                                        </ListItem>
                                        <Divider variant="inset" component="li"/>
                                    </React.Fragment>
                                )
                            })
                        }

                    </List>
                </div>
                <hr className={styles.separator} />
                <Container className={styles.links_container}>
                    <Link href="#" target="_self" variant="body2" align="left">
                        Help?
                    </Link>
                    &nbsp;
                    <Link href="#" target="_self" variant="body2">
                        Privacy
                    </Link>
                    &nbsp;
                    <Link href="#" target="_self" variant="body2" align="left">
                        Terms
                    </Link>
                </Container>
            </Container>
        </Container>
    );
}

const mapStateToProps = ({selectAccountState}) => ({
    formerAccounts: selectAccountState.accounts,
});

export default connect(
    mapStateToProps,
    {
        loadFormerAccounts,
        removeFormerAccount,
    }
)(SelectAccountPage);