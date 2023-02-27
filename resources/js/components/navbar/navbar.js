import React, {useState} from 'react';
import {makeStyles} from '@material-ui/core/styles';
import AppBar from '@material-ui/core/AppBar';
import Toolbar from '@material-ui/core/Toolbar';
import Button from '@material-ui/core/Button';
import Menu from '@material-ui/core/Menu';
import MenuItem from '@material-ui/core/MenuItem';
import Divider from "@material-ui/core/Divider";
import Typography from "@material-ui/core/Typography";
import ArrowDropDownIcon from '@material-ui/icons/ArrowDropDown';

const useStyles = makeStyles((theme) => ({
    root: {
        flexGrow: 1,
    },
    appbar: {
        backgroundColor: 'white',
        border: "1px solid #D3D3D3",
    },
    toolbar: {
        marginLeft: 'unset',
        backgroundColor: 'white'
    },
    menuSectionTitle: {
        marginLeft: '10px',
    },
}));

export default function NavBar({menuConfig}) {
    const classes = useStyles();

    const [oauthMenuEl, setOauthMenuEl] = useState(null);
    const [serverAdminEl, setServerAdminEl] = useState(null);

    const handleOauthMenuClick = (event) => {
        setOauthMenuEl(event.currentTarget);
    };

    const handleServerAdminMenuClick = (event) => {
        setServerAdminEl(event.currentTarget);
    };

    const handleClose = () => {
        setOauthMenuEl(null);
        setServerAdminEl(null);
    };

    const goTo = (url) => {
        window.location.href = url;
    };

    return (
        <div className={classes.root}>
            <AppBar position="static" elevation={0} className={classes.appbar}>
                <Toolbar className={classes.toolbar}>
                    <Button onClick={() => goTo(`${menuConfig.settingURL}`)}>
                        {menuConfig.settingsText}
                    </Button>
                    <Button aria-controls="oauth-menu"
                            aria-haspopup="true"
                            onClick={handleOauthMenuClick}
                            endIcon={<ArrowDropDownIcon/>}>
                        {menuConfig.oauthConsoleText}
                    </Button>
                    <Menu
                        id="oauth-menu"
                        anchorEl={oauthMenuEl}
                        keepMounted
                        open={Boolean(oauthMenuEl)}
                        onClose={handleClose}
                    >
                        <MenuItem
                            onClick={() => goTo(`${menuConfig.oauthAppsURL}`)}>{menuConfig.oauthAppsText}</MenuItem>
                        <MenuItem
                            onClick={() => goTo(`${menuConfig.oauthGrantsURL}`)}>{menuConfig.oauthGrantsText}</MenuItem>
                    </Menu>
                    {(menuConfig.isOAuth2ServerAdmin || menuConfig.isOpenIdServerAdmin || menuConfig.isSuperAdmin) &&
                        <>
                            <Button aria-controls="server-admin-menu"
                                    aria-haspopup="true"
                                    onClick={handleServerAdminMenuClick}
                                    endIcon={<ArrowDropDownIcon/>}>
                                {menuConfig.serverAdminText}
                            </Button>
                            <Menu
                                id="server-admin-menu"
                                anchorEl={serverAdminEl}
                                keepMounted
                                open={Boolean(serverAdminEl)}
                                onClose={handleClose}
                            >
                                {(menuConfig.isOpenIdServerAdmin || menuConfig.isSuperAdmin) &&
                                    <span>
                                        <li>
                                            <Typography
                                                className={classes.menuSectionTitle}
                                                display="block"
                                                variant="caption"
                                            >
                                                {menuConfig.securitySectionText}
                                            </Typography>
                                        </li>
                                        {menuConfig.isSuperAdmin &&
                                            <>
                                                <MenuItem
                                                    onClick={() => goTo(`${menuConfig.usersAdminURL}`)}>{menuConfig.usersAdminText}</MenuItem>
                                                <MenuItem
                                                    onClick={() => goTo(`${menuConfig.groupsAdminURL}`)}>{menuConfig.groupsAdminText}</MenuItem>
                                            </>
                                        }
                                        <MenuItem
                                            onClick={() => goTo(`${menuConfig.bannedIPsAdminURL}`)}>{menuConfig.bannedIPsAdminText}</MenuItem>
                                        <Divider component="li"/>
                                    </span>
                                }
                                {menuConfig.isOAuth2ServerAdmin &&
                                    <span>
                                        <li>
                                            <Typography
                                                className={classes.menuSectionTitle}
                                                display="block"
                                                variant="caption"
                                            >
                                                {menuConfig.oauthAdminSectionText}
                                            </Typography>
                                        </li>
                                        <MenuItem
                                            onClick={() => goTo(`${menuConfig.serverPrivateKeysAdminURL}`)}>{menuConfig.serverPrivateKeysAdminText}</MenuItem>
                                        <MenuItem
                                            onClick={() => goTo(`${menuConfig.resourceServersAdminURL}`)}>{menuConfig.resourceServersAdminText}</MenuItem>
                                        <MenuItem
                                            onClick={() => goTo(`${menuConfig.apiScopesAdminURL}`)}>{menuConfig.apiScopesAdminText}</MenuItem>
                                        <MenuItem
                                            onClick={() => goTo(`${menuConfig.lockedClientsAdminURL}`)}>{menuConfig.lockedClientsAdminText}</MenuItem>
                                        <Divider component="li"/>
                                    </span>
                                }
                                {menuConfig.isOpenIdServerAdmin &&
                                    <span>
                                        <li>
                                            <Typography
                                                className={classes.menuSectionTitle}
                                                display="block"
                                                variant="caption"
                                            >
                                                {menuConfig.serverConfigSectionText}
                                            </Typography>
                                        </li>
                                        <MenuItem
                                            onClick={() => goTo(`${menuConfig.serverConfigURL}`)}>{menuConfig.serverConfigText}</MenuItem>
                                    </span>
                                }
                            </Menu>
                        </>
                    }
                    <Button onClick={(e) => {
                        window.location.href = `mailto:${menuConfig.helpMailto}`;
                        e.preventDefault();
                    }}>
                        Help
                    </Button>
                    <Button onClick={() => goTo(`${menuConfig.logoutURL}`)}>
                        Logout
                    </Button>
                </Toolbar>
            </AppBar>
        </div>
    );
}
