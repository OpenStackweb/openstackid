import React, { useEffect, useRef, useState } from "react";
import ReactDOM from "react-dom";
import { Turnstile } from "@marsidev/react-turnstile";
import Button from "@material-ui/core/Button";
import Card from "@material-ui/core/Card";
import CardHeader from "@material-ui/core/CardHeader";
import CardContent from "@material-ui/core/CardContent";
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import Checkbox from "@material-ui/core/Checkbox";
import FormControlLabel from "@material-ui/core/FormControlLabel";
import Grid from "@material-ui/core/Grid";
import IconButton from "@material-ui/core/IconButton";
import InputAdornment from "@material-ui/core/InputAdornment";
import Visibility from "@material-ui/icons/Visibility";
import VisibilityOff from "@material-ui/icons/VisibilityOff";
import CheckCircle from "@material-ui/icons/CheckCircle";
import Cancel from "@material-ui/icons/Cancel";
import RadioButtonUnchecked from "@material-ui/icons/RadioButtonUnchecked";
import PasswordStrengthBar from "react-password-strength-bar";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import Autocomplete from "@material-ui/lab/Autocomplete";
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
  const [showPassword, setShowPassword] = useState(false);

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
        action={signUpAction}
        target="_self"
      >
        <Card className={styles.signup_container} variant="outlined">
          <CardHeader
            title="Create your FNid."
            subheader="One identity for every FNTECH event you attend."
          />
          <CardContent>
            <Grid
              container
              direction="column"
              spacing={1}
              justifyContent="center"
            >
              <Grid item spacing={2} container direction="row">
                <Grid item xs={6}>
                  <label htmlFor="first_name" className={styles.field_label}>First Name</label>
                  <TextField
                    id="first_name"
                    name="first_name"
                    variant="outlined"
                    fullWidth
                    size="small"
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
                  <label htmlFor="last_name" className={styles.field_label}>Last Name</label>
                  <TextField
                    id="last_name"
                    name="last_name"
                    variant="outlined"
                    fullWidth
                    size="small"
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
                <label htmlFor="email" className={styles.field_label}>Email</label>
                <TextField
                  id="email"
                  name="email"
                  autoComplete="email"
                  variant="outlined"
                  fullWidth
                  size="small"
                  inputProps={{ maxLength: 255 }}
                  value={formik.values.email}
                  onChange={formik.handleChange}
                  error={formik.touched.email && Boolean(formik.errors.email)}
                  helperText={formik.touched.email && formik.errors.email}
                />
              </Grid>
              <Grid item>
                <label htmlFor="country_iso_code" className={styles.field_label}>Country</label>
                <Autocomplete
                  id="country_iso_code"
                  options={countries}
                  getOptionLabel={(option) => option.text || ""}
                  getOptionSelected={(option, value) => option.value === value.value}
                  value={countries.find(c => c.value === formik.values.country_iso_code) || null}
                  onChange={(_, selected) => {
                    formik.setFieldValue("country_iso_code", selected ? selected.value : "");
                  }}
                  onBlur={() => formik.setFieldTouched("country_iso_code", true)}
                  renderInput={(params) => (
                    <TextField
                      {...params}
                      variant="outlined"
                      size="small"
                      fullWidth
                      error={formik.touched.country_iso_code && Boolean(formik.errors.country_iso_code)}
                      helperText={formik.touched.country_iso_code && formik.errors.country_iso_code}
                    />
                  )}
                />
                <input type="hidden" name="country_iso_code" value={formik.values.country_iso_code} />
              </Grid>
              <Grid item xs={12}>
                <label htmlFor="password" className={styles.field_label}>Password</label>
                <TextField
                  id="password"
                  name="password"
                  type={showPassword ? "text" : "password"}
                  variant="outlined"
                  fullWidth
                  size="small"
                  inputProps={{ maxLength: passwordPolicy.max_length }}
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
                <label htmlFor="password_confirmation" className={styles.field_label}>Confirm password</label>
                <TextField
                  id="password_confirmation"
                  name="password_confirmation"
                  type="password"
                  variant="outlined"
                  fullWidth
                  size="small"
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
                {codeOfConductUrl && (
                  <Grid container item justify='center'>
                    <FormControlLabel
                      control={
                        <Checkbox
                          id="agree_code_of_conduct"
                          name="agree_code_of_conduct"
                          value={formik.values.agree_code_of_conduct}
                          onChange={formik.handleChange}
                          color="default"
                        />
                      }
                      label={
                        <span>
                          I&nbsp;agree&nbsp;to&nbsp;the&nbsp;
                          <a href={codeOfConductUrl} target="_blank">
                            {tenantName} Community Code of Conduct
                          </a>
                        </span>
                      }
                    />
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
                    disabled={
                        !!formik.errors.password ||
                        !formik.values.password_confirmation ||
                        formik.values.password_confirmation !== formik.values.password
                    }
                  >
                    Create FNid
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
          Already have an FNid?{" "}
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
