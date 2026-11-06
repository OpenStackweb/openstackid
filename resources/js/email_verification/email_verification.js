import React, { useEffect, useRef } from "react";
import ReactDOM from "react-dom";
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
import { MuiThemeProvider, createTheme } from "@material-ui/core/styles";
import { useFormik } from "formik";
import { object, string, ref } from "yup";
import Banner from "../components/banner/banner";
import { TurnstileCaptcha, useTurnstileCaptcha } from "../components/turnstile_captcha";

import styles from "./email_verification.module.scss";

const validationSchema = object({
  email: string("Email")
    .email("Enter a valid email")
    .required("Email is required"),
});

const EmailVerificationPage = ({
  appLogo,
  captchaPublicKey,
  csrfToken,
  emailVerificationAction,
  emailVerificationError,
  infoBannerContent,
  initialValues,
  sessionStatus,
  showInfoBanner,
  submitButtonText,
}) => {
  const formEl = useRef(null);
  const captcha = useTurnstileCaptcha(captchaPublicKey);

  useEffect(() => {
    if (emailVerificationError) {
      Swal("Something went wrong!", emailVerificationError, "error");
    } else if (sessionStatus) {
      Swal(sessionStatus);
    }
  }, [emailVerificationError, sessionStatus]);

  const doHtmlFormPost = () => {
    formEl.current.submit();
  };

  const formik = useFormik({
    initialValues: initialValues,
    validationSchema: validationSchema,
    onSubmit: () => {
      if (!captcha.ensureToken()) {
        return;
      }
      doHtmlFormPost();
    },
  });

  return (
    <Container component="main" maxWidth="xs" className={styles.main_container}>
      <CssBaseline />
      {showInfoBanner && <Banner infoBannerContent={infoBannerContent} />}
      <div className={styles.title_container}>
        <a href='/auth/login' target='_self'>
          <img className={styles.app_logo} alt="appLogo" src={appLogo} />
        </a>
      </div>
      <form
        onSubmit={formik.handleSubmit}
        ref={formEl}
        method="post"
        action={emailVerificationAction}
        target="_self"
      >
        <Card
          className={styles.email_verification_container}
          variant="outlined"
        >
          <CardHeader title="Email Verification" />
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
                <TurnstileCaptcha
                  captcha={captcha}
                  className={styles.turnstile}
                  errorClassName={styles.error_label}
                />
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
const theme = createTheme({
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

export { EmailVerificationPage };

const root = document.querySelector("#root");
if (root) {
  ReactDOM.render(
    <MuiThemeProvider theme={theme}>
      <EmailVerificationPage {...config} />
    </MuiThemeProvider>,
    root
  );
}
