import React, { useEffect, useRef, useState } from "react";
import ReactDOM from "react-dom";
import { Turnstile } from "@marsidev/react-turnstile";
import Button from "@material-ui/core/Button";
import Card from "@material-ui/core/Card";
import CardHeader from "@material-ui/core/CardHeader";
import CardContent from "@material-ui/core/CardContent";
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import Grid from "@material-ui/core/Grid";
import IconButton from "@material-ui/core/IconButton";
import InputAdornment from "@material-ui/core/InputAdornment";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import Visibility from "@material-ui/icons/Visibility";
import VisibilityOff from "@material-ui/icons/VisibilityOff";
import CheckCircle from "@material-ui/icons/CheckCircle";
import Cancel from "@material-ui/icons/Cancel";
import RadioButtonUnchecked from "@material-ui/icons/RadioButtonUnchecked";
import PasswordStrengthBar from "react-password-strength-bar";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import Swal from "sweetalert2";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";
import {useFormik} from "formik";
import {object, string} from "yup";
import Banner from "../components/banner/banner";

import styles from "./reset_password.module.scss";
import {buildPasswordValidationSchema} from "../validator";

const ResetPasswordPage = ({
  appLogo,
  captchaPublicKey,
  csrfToken,
  token,
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
  const [showPassword, setShowPassword] = useState(false);

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
        ...buildPasswordValidationSchema(passwordPolicy, true)
      });

  const formik = useFormik({
    initialValues: initialValues,
    validationSchema: buildValidationSchema(passwordPolicy),
    onSubmit: (values) => {
      const turnstileResponse = captcha.current?.getResponse();
      if (!turnstileResponse) {
        setCaptchaConfirmation("Remember to check the captcha");
        return;
      }
      doHtmlFormPost();
    },
  });

  const onChangeCaptchaProvider = () => {
    if (captcha.current?.getResponse()) {
      setCaptchaConfirmation(null);
    }
  };

  const passwordRequirementItems = passwordPolicy.shape_list.split(",").map(s => s.trim());

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
          action={resetPasswordAction}
          target="_self"
      >
        <Card className={styles.reset_password_container} variant="outlined">
          <CardHeader
              title="Set a new password."
              subheader="Resetting the password for the FNid below."
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
                    className={styles.email_field}
                    id="email"
                    name="email"
                    autoComplete="email"
                    variant="filled"
                    fullWidth
                    size="small"
                    hiddenLabel
                    value={formik.values.email}
                    disabled={true}
                    InputProps={{ disableUnderline: true }}
                />
              </Grid>
              <Grid item xs={12}>
                <TextField
                    id="password"
                    name="password"
                    type={showPassword ? "text" : "password"}
                    variant="outlined"
                    fullWidth
                    size="small"
                    label="New password"
                    inputProps={{maxLength: passwordPolicy.max_length}}
                    value={formik.values.password}
                    onChange={formik.handleChange}
                    error={
                        formik.touched.password && Boolean(formik.errors.password)
                    }
                    helperText={formik.touched.password && formik.errors.password}
                    InputProps={{
                        endAdornment: (
                            <InputAdornment position="end">
                                <IconButton
                                    onClick={() => setShowPassword(!showPassword)}
                                    edge="end"
                                    size="small"
                                    aria-label={showPassword ? "Hide password" : "Show password"}
                                >
                                    {showPassword ? <VisibilityOff /> : <Visibility />}
                                </IconButton>
                            </InputAdornment>
                        )
                    }}
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
                    label="Confirm password"
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
                    InputProps={{
                        endAdornment: (
                            <InputAdornment position="end">
                                {!formik.values.password_confirmation
                                    ? <RadioButtonUnchecked style={{ color: '#ccc' }} />
                                    : formik.values.password_confirmation === formik.values.password && formik.values.password
                                        ? <CheckCircle style={{ color: '#2e7d32' }} />
                                        : <Cancel style={{ color: '#c62828', opacity: 0.6 }} />
                                }
                                <span className={styles.sr_only} aria-live="polite">
                                    {!formik.values.password_confirmation
                                        ? ""
                                        : formik.values.password_confirmation === formik.values.password && formik.values.password
                                            ? "Passwords match"
                                            : "Passwords do not match"
                                    }
                                </span>
                            </InputAdornment>
                        )
                    }}
                />
              </Grid>
              <Grid item className={styles.password_hint}>
                <p>Your password must include:</p>
                <ul>
                  <li key="length">{passwordPolicy.min_length}–{passwordPolicy.max_length} characters</li>
                  {passwordRequirementItems.map((item, index) => (
                    <li key={index}>{item}</li>
                  ))}
                </ul>
                <p className={styles.password_characters}>Allowed: <span>{passwordPolicy.allowed_special_characters_text}</span></p>
              </Grid>
              <Grid item container alignItems="center" justifyContent="center">
                <Grid container item justify='center'>
                  <Turnstile
                    ref={captcha}
                    className={styles.turnstile}
                    siteKey={captchaPublicKey}
                    options={{ responseFieldName: "cf-turnstile-response" }}
                    onSuccess={onChangeCaptchaProvider}
                    onExpire={() => { captcha.current?.reset(); }}
                    onError={() => setCaptchaConfirmation('The security check encountered an error. Please refresh the page and try again.')}
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
                      disabled={
                          !!formik.errors.password ||
                          !formik.values.password_confirmation ||
                          formik.values.password_confirmation !== formik.values.password
                      }
                  >
                    {submitButtonText}
                  </Button>
                </Grid>
              </Grid>
            </Grid>
          </CardContent>
        </Card>
        <input type="hidden" value={csrfToken} id="_token" name="_token"/>
        <input type="hidden" value={initialValues.email} id="email" name="email"/>
        <input type="hidden" name="token" value={token}/>
      </form>
      <p className={styles.help_link}>Need help? <a href="mailto:support@fntech.com">support@fntech.com</a></p>
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

ReactDOM.render(
  <MuiThemeProvider theme={theme}>
    <ResetPasswordPage {...config} />
  </MuiThemeProvider>,
  document.querySelector("#root")
);
