import React, {useState} from "react";
import {object} from "yup";
import {useFormik} from "formik";
import {Box, Button, Divider, FormControl, FormGroup, Typography} from "@material-ui/core";
import {CheckboxFormControl, SimpleTextFormControl} from "./form_controls";
import Swal from "sweetalert2";
import {handleErrorResponse} from "../../../../utils";
import LoadingIndicator from "../../../../components/loading_indicator";

import styles from "./common.module.scss";

const LogoutOptions = ({initialValues, onSavePromise}) => {
    const [loading, setLoading] = useState(false);

    const buildValidationSchema = () => {
        return object({});
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

    return (
        <>
            <form
                onSubmit={formik.handleSubmit}
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
                        value={formik.values.post_logout_redirect_uris}
                        touched={formik.touched.post_logout_redirect_uris}
                        errors={formik.errors.post_logout_redirect_uris}
                        onChange={formik.handleChange}
                    />
                    <SimpleTextFormControl
                        id="logout_uri"
                        title="Logout Uri (optional)"
                        tooltip=""
                        type="url"
                        value={formik.values.logout_uri}
                        touched={formik.touched.logout_uri}
                        errors={formik.errors.logout_uri}
                        onChange={formik.handleChange}
                    />
                    <CheckboxFormControl
                        id="logout_session_required"
                        title="Session Required (Optional)"
                        tooltip=""
                        value={formik.values.logout_session_required}
                        onChange={formik.handleChange}
                    />
                    <CheckboxFormControl
                        id="logout_use_iframe"
                        title="Use IFrame (Optional)"
                        tooltip=""
                        value={formik.values.logout_use_iframe}
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