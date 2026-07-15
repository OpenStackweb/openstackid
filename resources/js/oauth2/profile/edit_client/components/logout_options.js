import React, {useState} from "react";
import {object, string} from "yup";
import {useFormik} from "formik";
import {Box, Button, Divider, FormControl, FormGroup, FormLabel, Tooltip, Typography} from "@material-ui/core";
import {CheckboxFormControl, SimpleTextFormControl} from "./form_controls";
import Swal from "sweetalert2";
import {handleErrorResponse} from "../../../../utils";
import LoadingIndicator from "../../../../components/loading_indicator";
import TagsInput, {getTags} from "../../../../components/tags_input";

import styles from "./common.module.scss";

// mirrors Client::isDisallowedNativeUriScheme() on the backend: window.DISALLOWED_NATIVE_URI_SCHEMES and
// window.NATIVE_LOOPBACK_HOSTS are injected server-side from IClient::DISALLOWED_NATIVE_URI_SCHEMES /
// IClient::NATIVE_LOOPBACK_HOSTS (see edit-client.blade.php) - the deny-list has one owner, not two.
const isDisallowedNativeUriScheme = (protocol, host) => {
    const scheme = protocol.toLowerCase().replace(/:$/, '');
    if (scheme === 'http') {
        return !(window.NATIVE_LOOPBACK_HOSTS || []).includes((host || '').toLowerCase());
    }
    return (window.DISALLOWED_NATIVE_URI_SCHEMES || []).includes(scheme);
}

const LogoutOptions = ({appTypes, initialValues, onSavePromise}) => {
    const [loading, setLoading] = useState(false);

    const validatePostLogoutRedirectURI = (value) => {
        // native clients may register genuine custom app schemes (myapp://...), https, or an RFC 8252
        // http loopback redirect, but not plain non-loopback http nor dangerous/launch pseudo-schemes
        // (javascript:, data:, intent:, ...): matches the backend deny-list.
        if (initialValues.application_type === appTypes.Native) {
            try {
                const url = new URL(value);
                return url.protocol === 'https:' || !isDisallowedNativeUriScheme(url.protocol, url.hostname);
            } catch (err) {
                return false;
            }
        }
        const regex = /^https:\/\/([\w@][\w.:@]+)\/?[\w\.?=%&=\-@/$,]*$/ig;
        return regex.test(value);
    }

    const buildValidationSchema = () => {
        return object({
            logout_uri: string().nullable(true).matches(/^https:\/\//, {message: 'URL must be SSL'}),
        });
    }

    const formik = useFormik({
        initialValues: initialValues,
        enableReinitialize: true,
        validationSchema: buildValidationSchema(),

        onSubmit: (values) => {
            setLoading(true);
            onSavePromise(values).then(() => {
                setLoading(false);
                Swal("Logout options saved", "The logout options section info has been saved successfully", "success");
            }).catch((err) => {
                //console.log(err);
                setLoading(false);
                handleErrorResponse(err);
            });
        },
    });

    const handleFormKeyDown = (e) => {
        if ((e.charCode || e.keyCode) === 13) {
            e.preventDefault();
        }
    }

    return (
        <>
            <form
                onSubmit={formik.handleSubmit}
                onKeyDown={handleFormKeyDown}
                method="post"
                encType="multipart/form-data"
                target="_self"
                className={styles.main_container}
            >
                <FormGroup>
                    <Typography variant="h6">Logout Options</Typography>
                    <Box component="div" whiteSpace="nowrap" height="20px"/>
                    <Divider/>
                    <FormControl variant="outlined" className={styles.form_control}>
                        <FormLabel htmlFor="post_logout_redirect_uris">
                            <Typography variant="subtitle2" display="inline">Post Logout Uris (optional)</Typography>
                        </FormLabel>
                        <TagsInput
                            id="post_logout_redirect_uris"
                            name="post_logout_redirect_uris"
                            fullWidth
                            size="small"
                            variant="outlined"
                            tags={getTags(formik.values.post_logout_redirect_uris)}
                            errors={formik.errors.post_logout_redirect_uris}
                            onChange={formik.handleChange}
                            isValid={validatePostLogoutRedirectURI}
                        />
                    </FormControl>
                    <SimpleTextFormControl
                        id="logout_uri"
                        title="Logout Uri (optional)"
                        tooltip=""
                        type="url"
                        value={formik.values.logout_uri ?? ''}
                        touched={formik.touched.logout_uri}
                        errors={formik.errors.logout_uri}
                        onChange={formik.handleChange}
                    />
                    <CheckboxFormControl
                        id="logout_session_required"
                        title="Session Required (Optional)"
                        tooltip=""
                        value={!!formik.values.logout_session_required}
                        onChange={formik.handleChange}
                    />
                    <CheckboxFormControl
                        id="logout_use_iframe"
                        title="Use IFrame (Optional)"
                        tooltip=""
                        value={!!formik.values.logout_use_iframe}
                        onChange={formik.handleChange}
                    />
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
            <LoadingIndicator open={loading}/>
        </>
    )
}

export default LogoutOptions;