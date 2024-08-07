import React, { useEffect, useRef, useState } from "react";
import ReactDOM from "react-dom";
import ReCAPTCHA from "react-google-recaptcha";
import Button from "@material-ui/core/Button";
import Card from "@material-ui/core/Card";
import CardHeader from "@material-ui/core/CardHeader";
import CardContent from "@material-ui/core/CardContent";
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import Checkbox from "@material-ui/core/Checkbox";
import Grid from "@material-ui/core/Grid";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import MenuItem from "@material-ui/core/MenuItem";
import PasswordStrengthBar from "react-password-strength-bar";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import Select from "@material-ui/core/Select";
import Swal from "sweetalert2";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";
import {useFormik} from "formik";
import { object, string, ref } from "yup";
import Banner from "../components/banner/banner";
import {buildPasswordValidationSchema} from "../validator";

import styles from "./signup.module.scss";

const SignUpPage = ({
  appLogo,
  captchaPublicKey,
  clientId,
  codeOfConductUrl,
  countries,
  csrfToken,
  infoBannerContent,
  initialValues,
  passwordPolicy,
  redirectUri,
  showInfoBanner,
  signInAction,
  signUpAction,
  signUpError,
  tenantName,
}) => {
  const formEl = useRef(null);
  const captcha = useRef(null);
  const [captchaConfirmation, setCaptchaConfirmation] = useState(null);

  useEffect(() => {
    if (signUpError) {
      Swal("Something went wrong!", signUpError, "error");
    }
  }, [signUpError]);

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
      email: string("Enter your email")
        .email("Enter a valid email")
        .required("Email is required"),
      country_iso_code: string("Select a country").required(
        "Country is required"
      ),
      ...buildPasswordValidationSchema(passwordPolicy, true)
    });

  const formik = useFormik({
    initialValues: initialValues,
    validationSchema: buildValidationSchema(passwordPolicy),
    validate: (values) => {
      const errors = {};
      if (codeOfConductUrl && !values.agree_code_of_conduct) {
        errors.agree_code_of_conduct =
          "You must agree to the Community Code of Conduct";
      }
      return errors;
    },
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
        <a href='/auth/login' target='_self'>
          <img className={styles.app_logo} alt="appLogo" src={appLogo} />
        </a>
      </div>
      <form
        onSubmit={formik.handleSubmit}
        ref={formEl}
        method="post"
        action={signUpAction}
        target="_self"
      >
        <Card className={styles.signup_container} variant="outlined">
          <CardHeader
            title="Register"
            subheader="Create your account. It's free and only takes a minute."
          />
          <CardContent>
            <Grid
              container
              direction="column"
              spacing={2}
              justifyContent="center"
            >
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
                  id="email"
                  name="email"
                  autoComplete="email"
                  variant="outlined"
                  fullWidth
                  size="small"
                  label="Email Address"
                  inputProps={{ maxLength: 255 }}
                  value={formik.values.email}
                  onChange={formik.handleChange}
                  error={formik.touched.email && Boolean(formik.errors.email)}
                  helperText={formik.touched.email && formik.errors.email}
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
                  {`The Password must be ${passwordPolicy.min_length}–${passwordPolicy.max_length} characters, and ${passwordPolicy.shape_warning}`}
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
                {codeOfConductUrl && (
                  <Grid container item justify='center'>
                    <Checkbox
                      id="agree_code_of_conduct"
                      name="agree_code_of_conduct"
                      value={formik.values.agree_code_of_conduct}
                      onChange={formik.handleChange}
                      color="default"
                    />
                    <p>
                      I&nbsp;agree&nbsp;to&nbsp;the&nbsp;
                      <a href={codeOfConductUrl} target="_blank">
                        {tenantName} Community Code of Conduct
                      </a>&nbsp;?
                    </p>
                    <div className={styles.error_label}>
                      {formik.errors.agree_code_of_conduct}
                    </div>
                  </Grid>
                )}
                <Grid container item justify='center'>
                  <Button
                    variant="contained"
                    size="large"
                    className={styles.button}
                    disableElevation
                    fullWidth
                    type="submit"
                  >
                    Register Now
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
        {redirectUri && (
          <input
            type="hidden"
            id="redirect_uri"
            name="redirect_uri"
            value={redirectUri}
          />
        )}
      </form>
      <div className={styles.footer}>
        <Typography variant="body2">
          Already have an account?{" "}
          <a target="_self" href={signInAction}>
            Sign in
          </a>
        </Typography>
      </div>
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
    <SignUpPage {...config} />
  </MuiThemeProvider>,
  document.querySelector("#root")
);
