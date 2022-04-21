import React, { useEffect, useRef, useState } from "react";
import ReactDOM from "react-dom";
import ReCAPTCHA from "react-google-recaptcha";
import Button from "@material-ui/core/Button";
import Card from "@material-ui/core/Card";
import CardHeader from "@material-ui/core/CardHeader";
import CardContent from "@material-ui/core/CardContent";
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import Grid from "@material-ui/core/Grid";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import PasswordStrengthBar from "react-password-strength-bar";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import Swal from "sweetalert2";
import { MuiThemeProvider, createMuiTheme } from "@material-ui/core/styles";
import { useFormik } from "formik";
import { object, string, ref } from "yup";
import Banner from "../components/banner/banner";

import styles from "./reset_password.module.scss";

const ResetPasswordPage = ({
  appLogo,
  captchaPublicKey,
  csrfToken,
  infoBannerContent,
  initialValues,
  passwordPolicy,
  resetPasswordAction,
  resetPasswordError,
  sessionStatus,
  showInfoBanner,
  submitButtonText,
}) => {
  const formEl = useRef(null);
  const captcha = useRef(null);
  const [captchaConfirmation, setCaptchaConfirmation] = useState(null);

  useEffect(() => {
    if (resetPasswordError) {
      Swal("Something went wrong!", resetPasswordError, "error");
    } else if (sessionStatus) {
      Swal(sessionStatus);
    }
  }, [resetPasswordError, sessionStatus]);

  const doHtmlFormPost = (values) => {
    formEl.current.submit();
  };

  const buildValidationSchema = (passwordPolicy) =>
    object({
      password: string("Enter your password")
        .min(
          passwordPolicy.min_length,
          `Password should be of minimum ${passwordPolicy.min_length} characters length`
        )
        .required("Password is required"),
      password_confirmation: string("Confirm your password")
        .required("Password confirmation is required")
        .oneOf([ref("password")], "Passwords do not match"),
    });

  const formik = useFormik({
    initialValues: initialValues,
    validationSchema: buildValidationSchema(passwordPolicy),
    onSubmit: (values) => {
      const recaptchaResponse = captcha.current.getValue();
      if (!recaptchaResponse) {
        setCaptchaConfirmation("Remember to check the captcha");
        return;
      }
      doHtmlFormPost();
    },
  });

  const onChangeRecaptcha = () => {
    if (captcha.current.getValue()) {
      setCaptchaConfirmation(null);
    }
  };

  return (
    <Container component="main" maxWidth="xs" className={styles.main_container}>
      <CssBaseline />
      {showInfoBanner && <Banner infoBannerContent={infoBannerContent} />}
      <div className={styles.title_container}>
        <a href={window.location.href} target='_self'>
          <img className={styles.app_logo} alt="appLogo" src={appLogo} />
        </a>
      </div>
      <form
        onSubmit={formik.handleSubmit}
        ref={formEl}
        method="post"
        action={resetPasswordAction}
        target="_self"
      >
        <Card className={styles.reset_password_container} variant="outlined">
          <CardHeader
            title="Reset your Password"
            subheader="You can reset your password here."
          />
          <CardContent>
            <Grid
              container
              direction="column"
              spacing={2}
              justifyContent="center"
            >
              <Grid item>
                <TextField
                  id="email"
                  name="email"
                  autoComplete="email"
                  variant="outlined"
                  fullWidth
                  size="small"
                  label="Email Address"
                  value={formik.values.email}
                  disabled={true}
                />
              </Grid>
              <Grid item xs={12}>
                <TextField
                  id="password"
                  name="password"
                  type="password"
                  variant="outlined"
                  fullWidth
                  size="small"
                  label="Password"
                  inputProps={{ maxLength: passwordPolicy.max_length }}
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
                  inputProps={{ maxLength: passwordPolicy.max_length }}
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
              </Grid>
              <Grid item className={styles.password_hint}>
                <InfoOutlinedIcon fontSize="small" />
                &nbsp;
                <Typography variant="body2">
                  {`The password must be ${passwordPolicy.min_length}–${passwordPolicy.max_length} characters, and must include a
                  special character.`}
                </Typography>
              </Grid>
              <Grid item container alignItems="center" justifyContent="center">
                <Grid container item justify='center'>
                  <ReCAPTCHA
                    ref={captcha}
                    className={styles.recaptcha}
                    sitekey={captchaPublicKey}
                    onChange={onChangeRecaptcha}
                  />
                  {captchaConfirmation && (
                    <div className={styles.error_label}>
                      {captchaConfirmation}
                    </div>
                  )}
                </Grid>
                <Grid container item justify='center'>
                  <Button
                    variant="contained"
                    size="large"
                    className={styles.button}
                    disableElevation
                    fullWidth
                    type="submit"
                  >
                    {submitButtonText}
                  </Button>
                </Grid>
              </Grid>
            </Grid>
          </CardContent>
        </Card>
        <input type="hidden" value={csrfToken} id="_token" name="_token" />
      </form>
    </Container>
  );
};

// Or Create your Own theme:
const theme = createMuiTheme({
  palette: {
    primary: {
      main: "#3fa2f7",
    },
  },
  overrides: {
    MuiButton: {
      containedPrimary: {
        color: "white",
      },
    },
  },
});

ReactDOM.render(
  <MuiThemeProvider theme={theme}>
    <ResetPasswordPage {...config} />
  </MuiThemeProvider>,
  document.querySelector("#root")
);
