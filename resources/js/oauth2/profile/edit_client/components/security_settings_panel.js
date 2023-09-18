import React, {useState} from "react";
import {
    Box,
    Button,
    Divider,
    FormControl,
    FormGroup,
    Grid,
    Typography
} from "@material-ui/core";
import {object, string} from "yup";
import {useFormik} from "formik";
import PublicKeysAdmin from "./public_keys_admin";
import {CheckboxFormControl, SelectFormControl, SimpleTextFormControl} from "./form_controls";

import styles from "./common.module.scss";

const SecuritySettingsPanel = (
    {
        initialValues,
        isClientAllowedToUseTokenEndpointAuth,
        onSave,
        publicKeys,
        supportedContentEncryptionAlgorithms,
        supportedKeyManagementAlgorithms,
        supportedSigningAlgorithms,
        supportedTokenEndpointAuthMethods,
    }) => {
    const [passwordless, setPasswordless] = useState(false);

    const buildValidationSchema = () => {
        return object({
            jwks_uri: string("The JWK Url field is required.").required(
                "The JWK Url field is required."
            ),
        });
    }

    const formik = useFormik({
        initialValues: initialValues,
        validationSchema: buildValidationSchema(),
        onSubmit: (values) => {
            console.log('formik', values);
            // onSave(values).then(() => {
            //     //console.log('Security settings saved!');
            // }).catch((err) => {
            //     //console.log(err);
            // });
        },
    });

    const buildLogoutOptionsValidationSchema = () => {
        return object({});
    }

    const logoutOptionsformik = useFormik({
        initialValues: initialValues,
        validationSchema: buildLogoutOptionsValidationSchema(),

        onSubmit: (values) => {
            console.log('logoutOptionsformik', values);
            // onSave(values).then(() => {
            //     //console.log('Security settings saved!');
            // }).catch((err) => {
            //     //console.log(err);
            // });
        },
    });

    return (
        <Grid container>
            <Grid item container>
                <form
                    onSubmit={formik.handleSubmit}
                    method="post"
                    encType="multipart/form-data"
                    target="_self"
                    className={styles.main_container}
                >
                    <FormGroup>
                        <CheckboxFormControl
                            id="passwordless"
                            title="Use Passwordless?"
                            tooltip="Use Passwordless Authentication"
                            value={formik.values.passwordless}
                            onChange={() => {
                                setPasswordless(!passwordless);
                            }}
                        />
                        {
                            passwordless &&
                            <>
                                <SimpleTextFormControl
                                    id="otp_length"
                                    title="OTP Length"
                                    tooltip="One Time Password Length"
                                    type="number"
                                    value={formik.values.otp_length}
                                    touched={formik.touched.otp_length}
                                    errors={formik.errors.otp_length}
                                    onChange={formik.handleChange}
                                />
                                <SimpleTextFormControl
                                    id="otp_lifetime"
                                    title="OTP LifeTime (Seconds)"
                                    tooltip="One Time Password span lifetime in seconds"
                                    type="number"
                                    value={formik.values.otp_lifetime}
                                    touched={formik.touched.otp_lifetime}
                                    errors={formik.errors.otp_lifetime}
                                    onChange={formik.handleChange}
                                />
                            </>
                        }
                        <SimpleTextFormControl
                            id="default_max_age"
                            title="Default Max. Age (optional)"
                            tooltip="Default Maximum Authentication Age. Specifies that the End-User MUST be actively authenticated if the End-User was authenticated longer ago than the specified number of seconds. The max_age request parameter overrides this default value. If omitted, no default Maximum Authentication Age is specified."
                            type="number"
                            value={formik.values.default_max_age}
                            touched={formik.touched.default_max_age}
                            errors={formik.errors.default_max_age}
                            onChange={formik.handleChange}
                        />

                        {isClientAllowedToUseTokenEndpointAuth &&
                            <>
                                <SelectFormControl
                                    id="token_endpoint_auth_signing_alg"
                                    title="Token Endpoint Authorization Signed Algorithm"
                                    tooltip="JWS [JWS] alg algorithm [JWA] that MUST be used for signing the JWT [JWT] used to authenticate the Client at the Token Endpoint for the private_key_jwt and client_secret_jwt authentication methods. All Token Requests using these authentication methods from this Client MUST be rejected, if the JWT is not signed with this algorithm. Servers SHOULD support RS256. The value none MUST NOT be used. The default, if omitted, is that any algorithm supported by the OP and the RP MAY be used."
                                    value={formik.values.token_endpoint_auth_signing_alg}
                                    touched={formik.touched.token_endpoint_auth_signing_alg}
                                    errors={formik.errors.token_endpoint_auth_signing_alg}
                                    onChange={formik.handleChange}
                                    options={supportedSigningAlgorithms.map((alg) => {
                                        return {value: alg, text: alg};
                                    })}
                                />
                                <SelectFormControl
                                    id="token_endpoint_auth_method"
                                    title="Token Endpoint Authorization Method"
                                    tooltip="Requested Client Authentication method for the Token Endpoint. The options are client_secret_post, client_secret_basic, client_secret_jwt, private_key_jwt, and none, as described in Section 9 of OpenID Connect Core 1.0 [OpenID.Core]. Other authentication methods MAY be defined by extensions. If omitted, the default is client_secret_basic -- the HTTP Basic Authentication Scheme specified in Section 2.3.1 of OAuth 2.0 [RFC6749]."
                                    value={formik.values.token_endpoint_auth_method}
                                    touched={formik.touched.token_endpoint_auth_method}
                                    errors={formik.errors.token_endpoint_auth_method}
                                    onChange={formik.handleChange}
                                    options={supportedTokenEndpointAuthMethods.map((method) => {
                                        return {value: method, text: method};
                                    })}
                                />
                            </>
                        }
                        <SelectFormControl
                            id="subject_type"
                            title="Subject Type"
                            tooltip="subject_type requested for responses to this Client. The subject_types_supported Discovery parameter contains a list of the supported subject_type values for this server. Valid types include pairwise and public."
                            value={formik.values.subject_type}
                            touched={formik.touched.subject_type}
                            errors={formik.errors.subject_type}
                            onChange={formik.handleChange}
                            options={[
                                {value: 'public', text: 'public'},
                                {value: 'pairwise', text: 'pairwise'},
                            ]}
                        />
                        <SimpleTextFormControl
                            id="jwks_uri"
                            title="JWK Url"
                            tooltip="URL for the Client's JSON Web Key Set [JWK] document. If the Client signs requests to the Server, it contains the signing key(s) the Server uses to validate signatures from the Client. The JWK Set MAY also contain the Client's encryption keys(s), which are used by the Server to encrypt responses to the Client. When both signing and encryption keys are made available, a use (Key Use) parameter value is REQUIRED for all keys in the referenced JWK Set to indicate each key's intended usage. Although some algorithms allow the same key to be used for both signatures and encryption, doing so is NOT RECOMMENDED, as it is less secure. The JWK x5c parameter MAY be used to provide X.509 representations of keys provided. When used, the bare key values MUST still be present and MUST match those in the certificate."
                            type="url"
                            value={formik.values.jwks_uri}
                            touched={formik.touched.jwks_uri}
                            errors={formik.errors.jwks_uri}
                            onChange={formik.handleChange}
                        />
                        <Grid container>
                            <Grid item xs={6}>
                                <SelectFormControl
                                    id="userinfo_signed_response_alg"
                                    title="UserInfo Signed Response Algorithm"
                                    tooltip="JWS alg algorithm [JWA] REQUIRED for signing UserInfo Responses. If this is specified, the response will be JWT [JWT] serialized, and signed using JWS. The default, if omitted, is for the UserInfo Response to return the Claims as a UTF-8 encoded JSON object using the application/json content-type."
                                    value={formik.values.userinfo_signed_response_alg ?? 'none'}
                                    touched={formik.touched.userinfo_signed_response_alg}
                                    errors={formik.errors.userinfo_signed_response_alg}
                                    onChange={formik.handleChange}
                                    options={supportedSigningAlgorithms.map((alg) => {
                                        return {value: alg, text: alg};
                                    })}
                                />
                            </Grid>
                            <Grid item xs={6}>
                                <SelectFormControl
                                    id="id_token_signed_response_alg"
                                    title="Id Token Signed Response Algorithm"
                                    tooltip="JWS alg algorithm [JWA] REQUIRED for signing the ID Token issued to this Client. The value none MUST NOT be used as the ID Token alg value unless the Client uses only Response Types that return no ID Token from the Authorization Endpoint (such as when only using the Authorization Code Flow). The default, if omitted, is RS256. The public key for validating the signature is provided by retrieving the JWK Set referenced by the jwks_uri element from OpenID Connect Discovery 1.0 [OpenID.Discovery]."
                                    value={formik.values.id_token_signed_response_alg ?? 'none'}
                                    touched={formik.touched.id_token_signed_response_alg}
                                    errors={formik.errors.id_token_signed_response_alg}
                                    onChange={formik.handleChange}
                                    options={supportedSigningAlgorithms.map((alg) => {
                                        return {value: alg, text: alg};
                                    })}
                                />
                            </Grid>
                            <Grid item xs={6}>
                                <SelectFormControl
                                    id="userinfo_encrypted_response_alg"
                                    title="UserInfo Encrypted Key Algorithm"
                                    tooltip=""
                                    value={formik.values.userinfo_encrypted_response_alg ?? 'none'}
                                    touched={formik.touched.userinfo_encrypted_response_alg}
                                    errors={formik.errors.userinfo_encrypted_response_alg}
                                    onChange={formik.handleChange}
                                    options={supportedKeyManagementAlgorithms.map((alg) => {
                                        return {value: alg, text: alg};
                                    })}
                                />
                            </Grid>
                            <Grid item xs={6}>
                                <SelectFormControl
                                    id="id_token_encrypted_response_alg"
                                    title="Id Token Encrypted Key Algorithm"
                                    tooltip=""
                                    value={formik.values.id_token_encrypted_response_alg ?? 'none'}
                                    touched={formik.touched.id_token_encrypted_response_alg}
                                    errors={formik.errors.id_token_encrypted_response_alg}
                                    onChange={formik.handleChange}
                                    options={supportedKeyManagementAlgorithms.map((alg) => {
                                        return {value: alg, text: alg};
                                    })}
                                />
                            </Grid>
                            <Grid item xs={6}>
                                <SelectFormControl
                                    id="userinfo_encrypted_response_enc"
                                    title="UserInfo Encrypted Content Algorithm"
                                    tooltip=""
                                    value={formik.values.userinfo_encrypted_response_enc ?? 'none'}
                                    touched={formik.touched.userinfo_encrypted_response_enc}
                                    errors={formik.errors.userinfo_encrypted_response_enc}
                                    onChange={formik.handleChange}
                                    options={supportedContentEncryptionAlgorithms.map((alg) => {
                                        return {value: alg, text: alg};
                                    })}
                                />
                            </Grid>
                            <Grid item xs={6}>
                                <SelectFormControl
                                    id="id_token_encrypted_content_alg"
                                    title="Id Token Encrypted Content Algorithm"
                                    tooltip=""
                                    value={formik.values.id_token_encrypted_content_alg ?? 'none'}
                                    touched={formik.touched.id_token_encrypted_content_alg}
                                    errors={formik.errors.id_token_encrypted_content_alg}
                                    onChange={formik.handleChange}
                                    options={supportedContentEncryptionAlgorithms.map((alg) => {
                                        return {value: alg, text: alg};
                                    })}
                                />
                            </Grid>
                        </Grid>
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
                    <Box component="div" whiteSpace="nowrap" height="20px"/>
                    <Divider/>
                    <Box component="div" whiteSpace="nowrap" height="20px"/>
                    <PublicKeysAdmin
                        initialValues={initialValues}
                        supportedSigningAlgorithms={supportedSigningAlgorithms}
                        publicKeys={publicKeys}
                    />
                    <Divider/>
                    <Box component="div" whiteSpace="nowrap" height="20px"/>
                </form>
            </Grid>
            <Grid item container>
                <form
                    onSubmit={logoutOptionsformik.handleSubmit}
                    method="post"
                    encType="multipart/form-data"
                    target="_self"
                    className={styles.main_container}
                >
                    <FormGroup>
                        <Typography variant="h6">Logout Options</Typography>
                        <Box component="div" whiteSpace="nowrap" height="20px"/>
                        <Divider/>
                        <SimpleTextFormControl
                            id="post_logout_redirect_uris"
                            title="Post Logout Uris (optional)"
                            tooltip=""
                            type="url"
                            value={logoutOptionsformik.values.post_logout_redirect_uris}
                            touched={logoutOptionsformik.touched.post_logout_redirect_uris}
                            errors={logoutOptionsformik.errors.post_logout_redirect_uris}
                            onChange={logoutOptionsformik.handleChange}
                        />
                        <SimpleTextFormControl
                            id="logout_uri"
                            title="Logout Uri (optional)"
                            tooltip=""
                            type="url"
                            value={logoutOptionsformik.values.logout_uri}
                            touched={logoutOptionsformik.touched.logout_uri}
                            errors={logoutOptionsformik.errors.logout_uri}
                            onChange={logoutOptionsformik.handleChange}
                        />
                        <CheckboxFormControl
                            id="logout_session_required"
                            title="Session Required (Optional)"
                            tooltip=""
                            value={logoutOptionsformik.values.logout_session_required}
                            onChange={logoutOptionsformik.handleChange}
                        />
                        <CheckboxFormControl
                            id="logout_use_iframe"
                            title="Use IFrame (Optional)"
                            tooltip=""
                            value={logoutOptionsformik.values.logout_use_iframe}
                            onChange={logoutOptionsformik.handleChange}
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
            </Grid>
        </Grid>
    );
}

export default SecuritySettingsPanel;