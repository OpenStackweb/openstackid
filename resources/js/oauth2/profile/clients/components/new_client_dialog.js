import React, {useState} from 'react';
import UsersSelector from "../../../../components/users_selector";
import {withStyles} from '@material-ui/core/styles';
import MuiDialogTitle from '@material-ui/core/DialogTitle';
import MuiDialogContent from '@material-ui/core/DialogContent';
import MuiDialogActions from '@material-ui/core/DialogActions';
import InfoOutlinedIcon from '@material-ui/icons/InfoOutlined';
import CloseIcon from '@material-ui/icons/Close';
import Swal from "sweetalert2";
import {handleErrorResponse} from "../../../../utils";
import {useFormik} from 'formik';
import {object, ref, string} from 'yup';
import {
    Button,
    Checkbox,
    Dialog,
    FormControl,
    FormControlLabel,
    FormGroup,
    FormLabel,
    Grid,
    IconButton,
    Select,
    TextField,
    Tooltip,
    Typography
} from '@material-ui/core';

import styles from "./new_client_dialog.module.scss";

const classes = (theme) => ({
    root: {
        margin: 0,
        padding: theme.spacing(2),
    },
    closeButton: {
        position: 'absolute',
        right: theme.spacing(1),
        top: theme.spacing(1),
        color: theme.palette.grey[500],
    },
});

const DialogTitle = withStyles(classes)((props) => {
    const {children, classes, onClose, ...other} = props;
    return (
        <MuiDialogTitle disableTypography className={classes.root} {...other}>
            <Typography variant="h6">{children}</Typography>
            {onClose ? (
                <IconButton aria-label="close" className={classes.closeButton} onClick={onClose}>
                    <CloseIcon/>
                </IconButton>
            ) : null}
        </MuiDialogTitle>
    );
});

const NewClientDialog = ({onSave, fetchAdminUsersURL}) => {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const handleClickOpen = () => {
        setOpen(true);
    };

    const handleClose = () => {
        setOpen(false);
    };

    const buildValidationSchema = () => {
        return object({
            app_name: string("The app name field is required.").required(
                "The app name field is required."
            ),
            app_description: string("The app description field is required.").required(
                "The app description field is required."
            ),
        });
    }

    const formik = useFormik({
        initialValues: initialValues,
        validationSchema: buildValidationSchema(),
        onSubmit: (values, {resetForm}) => {
            setLoading(true);
            onSave(values).then(({response}) => {
                setLoading(false);
                setOpen(false);
                resetForm();
                let message = `<h4>The client has been added successfully</h4><h5>CLIENT ID</h5>${response.client_id}`;
                if (response.client_secret) message += `<h5>CLIENT SECRET</h5>${response.client_secret}`;
                Swal("Client added", message, "success");
            }).catch((err) => {
                //console.log(err);
                setLoading(false);
                setOpen(false);
                handleErrorResponse(err);
            });
        },
    });

    return (
        <div>
            <Button variant="outlined" color="primary" onClick={handleClickOpen}>
                Register Application
            </Button>
            <Dialog onClose={handleClose} aria-labelledby="customized-dialog-title" open={open}
                    className={styles.dialog}>
                <form
                    onSubmit={formik.handleSubmit}
                    method="post"
                    encType="multipart/form-data"
                    target="_self"
                >
                    <DialogTitle id="customized-dialog-title" onClose={handleClose}>
                        Register new Application
                    </DialogTitle>
                    <MuiDialogContent dividers className={styles.content}>
                        <Grid
                            container
                            direction="row"
                            spacing={2}
                            justifyContent="center"
                        >
                            <Tooltip
                                title='OAuth 2.0 allows users to share specific data with you (for example, contact lists) while keeping their usernames, passwords, and other information private.'>
                                <InfoOutlinedIcon fontSize="small"/>
                            </Tooltip>
                            &nbsp;
                            <Typography gutterBottom variant="caption">
                                You need to register your application to get the necessary credentials to call a
                                Openstack API
                            </Typography>
                        </Grid>
                        <FormGroup>
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="app_name">
                                    Application Name *
                                </FormLabel>
                                <TextField
                                    id="app_name"
                                    name="app_name"
                                    variant="outlined"
                                    fullWidth
                                    size="small"
                                    inputProps={{maxLength: 100}}
                                    autoFocus={true}
                                    value={formik.values.app_name}
                                    onChange={formik.handleChange}
                                    error={
                                        formik.touched.app_name &&
                                        Boolean(formik.errors.app_name)
                                    }
                                    helperText={
                                        formik.touched.app_name && formik.errors.app_name
                                    }
                                />
                            </FormControl>
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="app_web_site_url">
                                    Application Web Site Url (optional)
                                </FormLabel>
                                <TextField
                                    id="app_web_site_url"
                                    name="app_web_site_url"
                                    variant="outlined"
                                    fullWidth
                                    size="small"
                                    inputProps={{maxLength: 100}}
                                    autoFocus={true}
                                    value={formik.values.app_web_site_url}
                                    onChange={formik.handleChange}
                                    error={
                                        formik.touched.app_web_site_url &&
                                        Boolean(formik.errors.app_web_site_url)
                                    }
                                    helperText={
                                        formik.touched.app_web_site_url && formik.errors.app_web_site_url
                                    }
                                />
                            </FormControl>
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="app_description">
                                    Application Description *
                                </FormLabel>
                                <TextField
                                    id="app_description"
                                    name="app_description"
                                    variant="outlined"
                                    fullWidth
                                    multiline
                                    minRows={5}
                                    maxRows={5}
                                    size="small"
                                    autoFocus={true}
                                    value={formik.values.app_description}
                                    onChange={formik.handleChange}
                                    error={
                                        formik.touched.app_description &&
                                        Boolean(formik.errors.app_description)
                                    }
                                    helperText={
                                        formik.touched.app_description && formik.errors.app_description
                                    }
                                />
                            </FormControl>
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="application_type">
                                    <Tooltip
                                        title="Web Server Application : The FNid OAuth 2.0 endpoint supports web server applications that use languages and frameworks such as PHP, Java, Python, Ruby, and ASP.NET. These applications might access an Openstack API while the user is present at the application or after the user has left the application. This flow requires that the application can keep a secret. Client Side (JS) : JavaScript-centric applications. These applications may access a Openstack API while the user is present at the application, and this type of application cannot keep a secret. Service Account : The FNid OAuth 2.0 Authorization Server supports server-to-server interactions. The requesting application has to prove its own identity to gain access to an API, and an end-user doesn't have to be involved.">
                                        <InfoOutlinedIcon fontSize="small"/>
                                    </Tooltip>
                                    Application Type
                                </FormLabel>
                                <Select
                                    id="application_type"
                                    name="application_type"
                                    native
                                    size="small"
                                    value={formik.values.application_type}
                                    displayEmpty
                                    onChange={formik.handleChange}
                                    error={
                                        formik.touched.app_type &&
                                        Boolean(formik.errors.app_type)
                                    }
                                >
                                    <option value="WEB_APPLICATION">Web Server Application</option>
                                    <option value="JS_CLIENT">Client Side (JS)</option>
                                    <option value="SERVICE">Service Account</option>
                                    <option value="NATIVE">Native Application</option>
                                </Select>
                                {formik.touched.app_type &&
                                    formik.errors.app_type && (
                                        <div className={styles.error_label}>
                                            {formik.errors.app_type}
                                        </div>
                                    )}
                            </FormControl>
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="admin_users">
                                    <Tooltip title='Choose which users would be administrator of this application.'>
                                        <InfoOutlinedIcon fontSize="small"/>
                                    </Tooltip>
                                    Admin Users
                                </FormLabel>
                                <UsersSelector
                                    id="app_admin_users"
                                    name="app_admin_users"
                                    fetchUsersURL={fetchAdminUsersURL}
                                    onChange={formik.handleChange}
                                />
                            </FormControl>
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormControlLabel
                                    control={
                                        <Checkbox
                                            checked={formik.values.app_active}
                                            onChange={formik.handleChange}
                                            id="app_active"
                                            name="app_active"
                                            color="primary"
                                        />
                                    }
                                    label="Active"
                                />
                            </FormControl>
                        </FormGroup>
                    </MuiDialogContent>
                    <MuiDialogActions>
                        <Button autoFocus onClick={handleClose} color="primary">
                            Close
                        </Button>
                        <Button
                            variant="contained"
                            disableElevation
                            type="submit"
                        >
                            Save changes
                        </Button>
                    </MuiDialogActions>
                </form>
            </Dialog>
        </div>
    );
}

export default NewClientDialog;