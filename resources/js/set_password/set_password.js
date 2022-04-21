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
import MenuItem from "@material-ui/core/MenuItem";
import PasswordStrengthBar from "react-password-strength-bar";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import Select from "@material-ui/core/Select";
import Swal from "sweetalert2";
import { MuiThemeProvider, createMuiTheme } from "@material-ui/core/styles";
import { useFormik } from "formik";
import { object, string, ref } from "yup";
import Banner from "../components/banner/banner";

import styles from "./set_password.module.scss";

const SetPasswordPage = ({
  appLogo,
  captchaPublicKey,
  clientId,
  countries,
  csrfToken,
  infoBannerContent,
  initialValues,
  passwordPolicy,
  redirectUri,
  showInfoBanner,
  setPasswordAction,
  setPasswordError,
  sessionStatus,
  submitButtonText,
  token
}) => {
  const formEl = useRef(null);
  const captcha = useRef(null);
  const [captchaConfirmation, setCaptchaConfirmation] = useState(null);

  useEffect(() => {
    if (setPasswordError) {
      Swal("Something went wrong!", setPasswordError, "error");
    } else if (sessionStatus) {
      Swal(sessionStatus);
    }
  }, [setPasswordError, sessionStatus]);

  const doHtmlFormPost = (values) => {
    formEl.current.submit();
  };

  const buildValidationSchema = (passwordPolicy) =>
    object({
      first_name: string("Enter your first name").required(
        "First name is required"
      ),
      last_name: string("Enter your last name").required(
        "Last name is required"
      ),
      company: string("Enter your company").required("Company is required"),
      country_iso_code: string("Select a country").required(
        "Country is required"
      ),
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
        action={setPasswordAction}
        target="_self"
      >
        <Card className={styles.set_password_container} variant="outlined">
          <CardHeader
            title="Set your Password"
            subheader="You can set your password here."
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
              <Grid item spacing={2} container direction="row">
                <Grid item xs={6}>
                  <TextField
                    id="first_name"
                    name="first_name"
                    variant="outlined"
                    fullWidth
                    size="small"
                    label="First Name"
                    inputProps={{ maxLength: 100 }}
                    autoFocus={true}
                    value={formik.values.first_name}
                    onChange={formik.handleChange}
                    error={
                      formik.touched.first_name &&
                      Boolean(formik.errors.first_name)
                    }
                    helperText={
                      formik.touched.first_name && formik.errors.first_name
                    }
                  />
                </Grid>
                <Grid item xs={6}>
                  <TextField
                    id="last_name"
                    name="last_name"
                    variant="outlined"
                    fullWidth
                    size="small"
                    label="Last Name"
                    inputProps={{ maxLength: 100 }}
                    value={formik.values.last_name}
                    onChange={formik.handleChange}
                    error={
                      formik.touched.last_name &&
                      Boolean(formik.errors.last_name)
                    }
                    helperText={
                      formik.touched.last_name && formik.errors.last_name
                    }
                  />
                </Grid>
              </Grid>
              <Grid item>
                <TextField
                  id="company"
                  name="company"
                  variant="outlined"
                  fullWidth
                  size="small"
                  label="Company"
                  inputProps={{ maxLength: 100 }}
                  autoFocus={true}
                  value={formik.values.company}
                  onChange={formik.handleChange}
                  error={
                    formik.touched.company && Boolean(formik.errors.company)
                  }
                  helperText={formik.touched.company && formik.errors.company}
                />
              </Grid>
              <Grid item>
                <Select
                  id="country_iso_code"
                  name="country_iso_code"
                  variant="outlined"
                  fullWidth
                  size="small"
                  value={formik.values.country_iso_code}
                  displayEmpty
                  onChange={formik.handleChange}
                  className={styles.countries}
                  error={
                    formik.touched.country_iso_code &&
                    Boolean(formik.errors.country_iso_code)
                  }
                >
                  <MenuItem value="">
                    <span className={styles.countries_empty_text}>
                      Select a country
                    </span>
                  </MenuItem>
                  {countries.map((country) => (
                    <MenuItem key={country.value} value={country.value}>
                      {country.text}
                    </MenuItem>
                  ))}
                </Select>
                {formik.touched.country_iso_code &&
                  formik.errors.country_iso_code && (
                    <div className={styles.error_label}>
                      {formik.errors.country_iso_code}
                    </div>
                  )}
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
        <input type="hidden" value={token} name="token" />
        {clientId && (
          <input
            type="hidden"
            id="client_id"
            name="client_id"
            value={clientId}
          />
        )}
        {redirectUri && (
          <input
            type="hidden"
            id="redirect_uri"
            name="redirect_uri"
            value={redirectUri}
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
    <SetPasswordPage {...config} />
  </MuiThemeProvider>,
  document.querySelector("#root")
);
