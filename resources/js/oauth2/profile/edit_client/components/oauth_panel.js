import React, {useState} from "react";
import UsersSelector from "../../../../components/users_selector";
import TagsInput from "../../../../components/tags_input";
import {CheckboxFormControl, SimpleTextFormControl} from "./form_controls";
import {useFormik} from 'formik';
import {object, ref, string} from 'yup';
import AssignmentIcon from "@material-ui/icons/Assignment";
import CheckCircleIcon from '@material-ui/icons/CheckCircle';
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import RefreshIcon from "@material-ui/icons/Refresh";
import {
    Box, Button,
    Divider,
    FormControl,
    FormGroup,
    FormLabel,
    IconButton,
    InputAdornment,
    OutlinedInput,
    TextField,
    Tooltip,
    Typography
} from "@material-ui/core";

import styles from "./common.module.scss";

const OauthPanel = ({
                        appType,
                        appTypes,
                        canRequestRefreshTokens,
                        clientType,
                        clientTypes,
                        fetchAdminUsersURL,
                        initialValues,
                        isOwner,
                        onClientSecretRegenerate
                    }) => {
    const [formValues, setFormValues] = useState({});
    const [copyEventInfo, setCopyEventInfo] = useState({});

    const handleCopyClick = (target, value) => {
        navigator.clipboard.writeText(value).then(() => {
            setCopyEventInfo({...copyEventInfo, [target]: true});
            setTimeout(() => {
                setCopyEventInfo({...copyEventInfo, [target]: false});
            }, 2000);
        });
    }

    const buildValidationSchema = () => {
        return object({
            app_name: string("The app name field is required.").required(
                "The app name field is required."
            ),
        });
    }

    const formik = useFormik({
        initialValues: initialValues,
        enableReinitialize: true,
        validationSchema: buildValidationSchema(),
        onSubmit: (values) => {
            console.log(values);
            // setLoading(true);
            // onSave(values).then(() => {
            //     setLoading(false);
            //     setOpen(false);
            //     Swal("Client added", "The client has been added successfully", "success");
            // }).catch((err) => {
            //     //console.log(err);
            //     setLoading(false);
            //     setOpen(false);
            //     handleErrorResponse(err);
            // });
        },
    });

    const handleFormKeyDown = (e) => {
        if ((e.charCode || e.keyCode) === 13) {
            e.preventDefault();
        }
    }

    return (
        <form
            onSubmit={formik.handleSubmit}
            onKeyDown={handleFormKeyDown}
            method="post"
            encType="multipart/form-data"
            target="_self"
            className={styles.main_container}
        >
            <Typography>Client Credentials</Typography>
            <Divider/>
            <FormGroup>
                <FormControl variant="outlined" className={styles.form_control}>
                    <FormLabel htmlFor="client_id">
                        <Typography variant="subtitle2" display="inline">CLIENT ID</Typography>
                    </FormLabel>
                    <OutlinedInput
                        id="client_id"
                        name="client_id"
                        type="text"
                        value={formik.values.client_id}
                        onChange={formik.handleChange}
                        className={styles.outline_input}
                        disabled={true}
                        endAdornment={
                            <InputAdornment position="end">
                                <IconButton
                                    aria-label="copy to clipboard"
                                    onClick={() => handleCopyClick("client_id", formik.values.client_id)}
                                    edge="end"
                                >
                                    {copyEventInfo.client_id ?
                                        <CheckCircleIcon/> :
                                        <Tooltip title="Click to copy">
                                            <AssignmentIcon/>
                                        </Tooltip>
                                    }
                                </IconButton>
                            </InputAdornment>
                        }
                    />
                </FormControl>
                {
                    clientType === clientTypes.Confidential &&
                    <FormControl variant="outlined" className={styles.form_control}>
                        <FormLabel htmlFor="client_secret">
                            <Typography variant="subtitle2" display="inline">CLIENT SECRET</Typography>
                            <Tooltip title="Regenerate">
                                {
                                    isOwner && <IconButton
                                        aria-label="regenerate"
                                        onClick={onClientSecretRegenerate}
                                        edge="end"
                                        size="small"
                                    >
                                        <RefreshIcon/>
                                    </IconButton>
                                }
                            </Tooltip>
                        </FormLabel>
                        <OutlinedInput
                            id="client_secret"
                            name="client_secret"
                            type="text"
                            value={formik.values.client_secret}
                            onChange={formik.handleChange}
                            className={styles.outline_input}
                            disabled={true}
                            endAdornment={
                                <InputAdornment position="end">
                                    <IconButton
                                        aria-label="copy to clipboard"
                                        onClick={() => handleCopyClick("client_secret", formik.values.client_secret)}
                                        edge="end"
                                    >
                                        {copyEventInfo.client_secret ?
                                            <CheckCircleIcon/> :
                                            <Tooltip title="Click to copy">
                                                <AssignmentIcon/>
                                            </Tooltip>
                                        }
                                    </IconButton>
                                </InputAdornment>
                            }
                        />
                    </FormControl>
                }
                {
                    canRequestRefreshTokens &&
                    <>
                        <Box component="div" whiteSpace="nowrap" height="20px"/>
                        <Typography>Client Settings</Typography>
                        <CheckboxFormControl
                            id="use_refresh_token"
                            title="Use Refresh Tokens"
                            tooltip=""
                            value={formik.values.use_refresh_token}
                            onChange={formik.handleChange}
                        />
                        <CheckboxFormControl
                            id="rotate_refresh_token"
                            title="Use Rotate Refresh Token Policy"
                            tooltip=""
                            value={formik.values.rotate_refresh_token}
                            onChange={formik.handleChange}
                        />
                    </>
                }
            </FormGroup>
            <Box component="div" whiteSpace="nowrap" height="20px"/>
            <Typography>Client Data</Typography>
            <Divider/>
            <FormGroup>
                <SimpleTextFormControl
                    id="app_name"
                    title="Application Name"
                    tooltip=""
                    value={formik.values.app_name}
                    touched={formik.touched.app_name}
                    errors={formik.errors.app_name}
                    onChange={formik.handleChange}
                />
                <FormControl variant="outlined" className={styles.form_control}>
                    <FormLabel htmlFor="app_description">
                        <Typography variant="subtitle2">Application Description</Typography>
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
                    <FormLabel htmlFor="admin_users">
                        <Typography variant="subtitle2" display="inline">Admin Users</Typography>&nbsp;
                        <Tooltip title="Choose which users would be administrator of this application.">
                            <InfoOutlinedIcon fontSize="small"/>
                        </Tooltip>
                    </FormLabel>
                    <UsersSelector
                        id="app_admin_users"
                        name="app_admin_users"
                        fetchUsersURL={fetchAdminUsersURL}
                        initialValue={formik.values.app_admin_users}
                        onChange={formik.handleChange}
                    />
                </FormControl>
                <SimpleTextFormControl
                    id="app_web_site_url"
                    title="Application Web Site Url (optional)"
                    tooltip="Client home page URL."
                    value={formik.values.app_web_site_url}
                    touched={formik.touched.app_web_site_url}
                    errors={formik.errors.app_web_site_url}
                    onChange={formik.handleChange}
                />
                <SimpleTextFormControl
                    id="app_logo_url"
                    title="Application Logo Url (optional)"
                    tooltip="URL that references a logo for the Client application."
                    value={formik.values.app_logo_url}
                    touched={formik.touched.app_logo_url}
                    errors={formik.errors.app_logo_url}
                    onChange={formik.handleChange}
                />
                <SimpleTextFormControl
                    id="app_term_of_service_url"
                    title="Application Term of Service Url (optional)"
                    tooltip="URL that the Relying Party Client provides to the End-User to read about the Relying Party's terms of service."
                    value={formik.values.app_term_of_service_url}
                    touched={formik.touched.app_term_of_service_url}
                    errors={formik.errors.app_term_of_service_url}
                    onChange={formik.handleChange}
                />
                <SimpleTextFormControl
                    id="app_policy_url"
                    title="Application Policy Url (optional)"
                    tooltip="URL that the Relying Party Client provides to the End-User to read about the how the profile data will be used."
                    value={formik.values.app_policy_url}
                    touched={formik.touched.app_policy_url}
                    errors={formik.errors.app_policy_url}
                    onChange={formik.handleChange}
                />
                <FormControl variant="outlined" className={styles.form_control}>
                    <FormLabel htmlFor="contact_emails">
                        <Typography variant="subtitle2" display="inline">Contact Emails (optional)</Typography>&nbsp;
                        <Tooltip title="e-mail addresses of people responsible for this Client.">
                            <InfoOutlinedIcon fontSize="small"/>
                        </Tooltip>
                    </FormLabel>
                    <TagsInput
                        id="contact_emails"
                        name="contact_emails"
                        fullWidth
                        size="small"
                        variant="outlined"
                        type="email"
                        onChange={formik.handleChange}
                        tags={formik.values.contact_emails}
                    />
                </FormControl>
                {
                    //appType === appTypes.Service &&
                    <FormControl variant="outlined" className={styles.form_control}>
                        <FormLabel htmlFor="redirect_uris">
                            <Typography variant="subtitle2" display="inline">Allowed Redirection Uris
                                (optional)</Typography>&nbsp;
                            <Tooltip title="Redirection URI values used by the Client.">
                                <InfoOutlinedIcon fontSize="small"/>
                            </Tooltip>
                        </FormLabel>
                        <TagsInput
                            id="redirect_uris"
                            name="redirect_uris"
                            fullWidth
                            size="small"
                            variant="outlined"
                            type="url"
                            onChange={formik.handleChange}
                            tags={formik.values.redirect_uris}
                        />
                    </FormControl>
                }
                {
                    //appType === appTypes.JSClient &&
                    <FormControl variant="outlined" className={styles.form_control}>
                        <FormLabel htmlFor="allowed_origins">
                            <Typography variant="subtitle2" display="inline">Allowed javascript origins
                                (optional)</Typography>&nbsp;
                            <Tooltip title="Allowed js origin URI values used by the Client.">
                                <InfoOutlinedIcon fontSize="small"/>
                            </Tooltip>
                        </FormLabel>
                        <TagsInput
                            id="allowed_origins"
                            name="allowed_origins"
                            fullWidth
                            size="small"
                            variant="outlined"
                            type="url"
                            onChange={formik.handleChange}
                            tags={formik.values.allowed_origins}
                        />
                    </FormControl>
                }
                <FormControl variant="outlined" className={styles.form_control}>
                    <Button
                        variant="contained"
                        disableElevation
                        color="primary"
                        className={styles.button}
                        type="submit"
                    >
                        Save
                    </Button>
                </FormControl>
            </FormGroup>
        </form>
    );
}

export default OauthPanel;