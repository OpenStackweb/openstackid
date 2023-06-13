import React, {useState} from 'react';
import {
    Button,
    Divider,
    Drawer,
    IconButton,
    List,
    ListItem,
    Menu,
    MenuItem,
    makeStyles,
    Typography
} from '@material-ui/core';
import ArrowDropDownIcon from '@material-ui/icons/ArrowDropDown';
import MenuIcon from '@material-ui/icons/Menu';

const useStyles = makeStyles(() => ({
    link: {
        textDecoration: "none",
        color: "blue",
        fontSize: "20px",
    },
    icon: {
        color: "white"
    },
    menuSectionTitle: {
        marginLeft: '10px',
    }
}));

function DrawerComponent() {
    const classes = useStyles();
    const [openDrawer, setOpenDrawer] = useState(false);
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
        <>
            <Drawer
                open={openDrawer}
                onClose={() => setOpenDrawer(false)}
            >
                <List>
                    <ListItem>
                        <Button onClick={() => goTo(`${menuConfig.settingURL}`)}>
                            {menuConfig.settingsText}
                        </Button>
                    </ListItem>
                    <ListItem>
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
                    </ListItem>
                    {(menuConfig.isOAuth2ServerAdmin || menuConfig.isOpenIdServerAdmin || menuConfig.isSuperAdmin) &&
                        <>
                            <ListItem>
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
                            </ListItem>
                        </>
                    }
                    <ListItem onClick={() => setOpenDrawer(false)}>
                        <Button onClick={(e) => {
                            window.location.href = `mailto:${menuConfig.helpMailto}`;
                            e.preventDefault();
                        }}>
                            Help
                        </Button>
                    </ListItem>
                    <ListItem onClick={() => setOpenDrawer(false)}>
                        <Button onClick={() => goTo(`${menuConfig.logoutURL}`)}>
                            Logout
                        </Button>
                    </ListItem>
                </List>
            </Drawer>
            <IconButton onClick={() => setOpenDrawer(!openDrawer)}>
                <MenuIcon/>
            </IconButton>
        </>
    );
}

export default DrawerComponent;