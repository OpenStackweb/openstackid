import React, {useState} from "react";
import Grid from "@material-ui/core/Grid";
import Link from "@material-ui/core/Link";
import TextField from "@material-ui/core/TextField";
import PasswordStrengthBar from "react-password-strength-bar";

const PasswordChangePanel = ({formik, passwordPolicy}) => {
    const [changingPwd, setChangingPwd] = useState(false);

    return (
        <>
            {
                !changingPwd &&
                <Grid item xs={12}>
                    <Link href="#" onClick={(e) => {
                        e.preventDefault();
                        setChangingPwd(true);
                    }}>
                        Change Password
                    </Link>
                </Grid>
            }
            {
                changingPwd &&
                <>
                    <Grid item xs={12}>
                        <TextField
                            id="password"
                            name="password"
                            type="password"
                            variant="outlined"
                            fullWidth
                            size="small"
                            label="New Password"
                            inputProps={passwordPolicy}
                            value={formik.values.password}
                            onChange={formik.handleChange}
                            error={
                                formik.touched.password && Boolean(formik.errors.password)
                            }
                            helperText={formik.touched.password && formik.errors.password}
                        />
                        {formik.values.password && (
                            <PasswordStrengthBar
                                password={formik.values.password}
                                minLength={passwordPolicy.min_length}
                            />
                        )}
                    </Grid>
                    <Grid item xs={12}>
                        <TextField
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            variant="outlined"
                            fullWidth
                            size="small"
                            label="Confirm Password"
                            inputProps={{maxLength: passwordPolicy.max_length}}
                            value={formik.values.password_confirmation}
                            onChange={formik.handleChange}
                            error={
                                formik.touched.password_confirmation &&
                                Boolean(formik.errors.password_confirmation)
                            }
                            helperText={
                                formik.touched.password_confirmation &&
                                formik.errors.password_confirmation
                            }
                        />
                        {formik.values.password && (
                            <PasswordStrengthBar
                                password={formik.values.password_confirmation}
                                minLength={passwordPolicy.min_length}
                            />
                        )}
                    </Grid>
                </>
            }
        </>
    );
}

export default PasswordChangePanel;