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
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import Swal from "sweetalert2";
import { MuiThemeProvider, createMuiTheme } from "@material-ui/core/styles";
import { useFormik } from "formik";
import { object, string, ref } from "yup";
import Banner from "../components/banner/banner";

import styles from "./forgot_password.module.scss";

const validationSchema = object({
  email: string("Email")
    .email("Enter a valid email")
    .required("Email is required"),
});

const ForgotPasswordPage = ({
  appLogo,
  captchaPublicKey,
  clientId,
  csrfToken,
  forgotPasswordAction,
  forgotPasswordError,
  infoBannerContent,
  initialValues,
  redirectUri,
  sessionStatus,
  showInfoBanner,
  submitButtonText,
}) => {
  const formEl = useRef(null);
  const captcha = useRef(null);
  const [captchaConfirmation, setCaptchaConfirmation] = useState(null);

  useEffect(() => {
    if (forgotPasswordError) {
      Swal("Something went wrong!", forgotPasswordError, "error");
    } else if (sessionStatus) {
      Swal(sessionStatus);
    }
  }, [forgotPasswordError, sessionStatus]);

  const doHtmlFormPost = (values) => {
    formEl.current.submit();
  };

  const formik = useFormik({
    initialValues: initialValues,
    validationSchema: validationSchema,
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
        <a href={window.location.href}>
          <img className={styles.app_logo} alt="appLogo" src={appLogo} />
        </a>
      </div>
      <form
        onSubmit={formik.handleSubmit}
        ref={formEl}
        method="post"
        action={forgotPasswordAction}
        target="_self"
      >
        <Card className={styles.forgot_password_container} variant="outlined">
          <CardHeader
            title="Forgot Password?"
            subheader="You can reset your password here."
          />
          <CardContent>
            <Grid
              container
              direction="column"
              spacing={2}
              justifyContent="center"
            >
              {sessionStatus && (
                <Grid item>
                  <Typography variant="body2">{sessionStatus}</Typography>
                  {redirectUri && (
                    <Typography variant="body2">
                      Now you will be redirected to{" "}
                      <a
                        target="_self"
                        id="redirect_url"
                        name="redirect_url"
                        href={redirectUri}
                      >
                        {redirectUri}
                      </a>
                    </Typography>
                  )}
                </Grid>
              )}
              <Grid item>
                <TextField
                  id="email"
                  name="email"
                  autoComplete="email"
                  variant="outlined"
                  fullWidth
                  size="small"
                  label="Email"
                  inputProps={{ maxLength: 255 }}
                  value={formik.values.email}
                  onChange={formik.handleChange}
                  error={formik.touched.email && Boolean(formik.errors.email)}
                  helperText={formik.touched.email && formik.errors.email}
                />
              </Grid>
              <Grid item container alignItems="center" justifyContent="center">
                <Grid item>
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
                <Grid item>
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
        {clientId && (
          <input
            type="hidden"
            id="client_id"
            name="client_id"
            value={clientId}
          />
        )}
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
    <ForgotPasswordPage {...config} />
  </MuiThemeProvider>,
  document.querySelector("#root")
);
