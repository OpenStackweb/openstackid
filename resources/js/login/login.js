import React from "react";
import { Turnstile } from "@marsidev/react-turnstile";
import ReactDOM from "react-dom";
import Avatar from "@material-ui/core/Avatar";
import Button from "@material-ui/core/Button";
import CssBaseline from "@material-ui/core/CssBaseline";
import Typography from "@material-ui/core/Typography";
import Container from "@material-ui/core/Container";
import Chip from "@material-ui/core/Chip";
import { MuiThemeProvider, createTheme } from "@material-ui/core/styles";
import {
  verifyAccount,
  emitOTP,
  resendVerificationEmail,
  verify2FA,
  resend2FA,
  verifyRecoveryCode,
  cancelLogin,
} from "./actions";
import { emailValidator } from "../validator";
import CustomSnackbar from "../components/custom_snackbar";
import Banner from "../components/banner/banner";
import { handleErrorResponse } from "../utils";

import EmailInputForm from "./components/email_input_form";
import PasswordInputForm from "./components/password_input_form";
import OTPInputForm from "./components/otp_input_form";
import HelpLinks from "./components/help_links";
import OTPHelpLinks from "./components/otp_help_links";
import EmailErrorActions from "./components/email_error_actions";
import ThirdPartyIdentityProviders from "./components/third_party_identity_providers";
import TwoFactorForm from "./components/two_factor_form";
import RecoveryCodeForm from "./components/recovery_code_form";

import styles from "./login.module.scss";
import "./third_party_identity_providers.scss";
import {
  FLOW,
  HTTP_CODES,
  MFA_ERROR_CODE,
  OTP_LENGTH_DEFAULT,
  OTP_TTL_DEFAULT,
  MFA_METHOD_DEFAULT,
} from "./constants";

class LoginPage extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      user_name: props.userName,
      user_password: "",
      otpCode: "",
      user_pic: props.user_pic ?? null,
      user_fullname: props.user_fullname ?? null,
      user_verified: props.user_verified ?? false,
      user_active: props.user_active ?? null,
      email_verified: props.email_verified ?? null,
      errors: {
        email: "",
        otp: props.authError ?? "",
        password: props.authError ?? "",
        twofactor: "",
        recovery: "",
      },
      notification: {
        message: null,
        severity: "info",
      },
      captcha_value: "",
      showPassword: false,
      disableInput: false,
      authFlow: props.flow,
      allowNativeAuth: props.allowNativeAuth,
      showInfoBanner: props.showInfoBanner,
      infoBannerContent: props.infoBannerContent,
      // Two-factor state (populated from the flash redirect when a challenge is required).
      otpLength: props.otpLength ?? OTP_LENGTH_DEFAULT,
      otpLifetime: props.otpLifetime ?? OTP_TTL_DEFAULT,
      mfaMethod: props.mfaMethod ?? MFA_METHOD_DEFAULT,
      trustDevice: false,
      twoFactorCode: "",
      recoveryCode: "",
      codeVersion: 0,
    };

    if (props.authError != "" && !this.state.user_fullname) {
      this.state.user_fullname = props.userName;
    }

    if (
      this.state.errors.password &&
      this.state.errors.password.includes("is not yet verified")
    ) {
      this.state.errors.password =
        this.state.errors.password +
        `Or <a target='_self' href='${this.props.verifyEmailAction}?email=${encodeURIComponent(this.props.userName)}'>have another verification email sent to you.</a>`;
    }

    this.onHandleUserNameChange = this.onHandleUserNameChange.bind(this);
    this.onValidateEmail = this.onValidateEmail.bind(this);
    this.handleDelete = this.handleDelete.bind(this);
    this.onAuthenticate = this.onAuthenticate.bind(this);
    this.onChangeCaptchaProvider = this.onChangeCaptchaProvider.bind(this);
    this.onExpireCaptchaProvider = this.onExpireCaptchaProvider.bind(this);
    this.onErrorCaptchaProvider = this.onErrorCaptchaProvider.bind(this);
    this.onUserPasswordChange = this.onUserPasswordChange.bind(this);
    this.onOTPCodeChange = this.onOTPCodeChange.bind(this);
    this.shouldShowCaptcha = this.shouldShowCaptcha.bind(this);
    this.handleClickShowPassword = this.handleClickShowPassword.bind(this);
    this.handleMouseDownPassword = this.handleMouseDownPassword.bind(this);
    this.handleEmitOtpAction = this.handleEmitOtpAction.bind(this);
    this.resendVerificationEmail = this.resendVerificationEmail.bind(this);
    this.handleSnackbarClose = this.handleSnackbarClose.bind(this);
    this.showAlert = this.showAlert.bind(this);
    this.onTwoFactorCodeChange = this.onTwoFactorCodeChange.bind(this);
    this.onRecoveryCodeChange = this.onRecoveryCodeChange.bind(this);
    this.onTrustDeviceChange = this.onTrustDeviceChange.bind(this);
    this.onVerify2FA = this.onVerify2FA.bind(this);
    this.onResend2FA = this.onResend2FA.bind(this);
    this.onVerifyRecovery = this.onVerifyRecovery.bind(this);
    this.onUseRecovery = this.onUseRecovery.bind(this);
    this.onBackToOtp = this.onBackToOtp.bind(this);
    this.resetToPasswordFlow = this.resetToPasswordFlow.bind(this);
  }

  showAlert(message, severity) {
    this.setState({
      ...this.state,
      notification: {
        message: message,
        severity: severity,
      },
    });
  }

  emitOtpAction() {
    let user_fullname = this.state.user_fullname
      ? this.state.user_fullname
      : this.state.user_name;

    emitOTP(this.state.user_name, this.props.token).then(
      (payload) => {
        let { response } = payload;
        this.setState({
          ...this.state,
          authFlow: FLOW.OTP,
          errors: {
            email: "",
            otp: "",
            password: "",
          },
          user_verified: true,
          user_fullname: user_fullname,
        });
      },
      (error) => {
        let { response, status, message } = error;
        if (status == 412) {
          const { message, errors } = response.body;
          this.showAlert(errors[0], "error");
          return;
        }
        this.showAlert("Oops... Something went wrong!", "error");
      },
    );
    return false;
  }

  handleEmitOtpAction(ev) {
    ev.preventDefault();
    return this.emitOtpAction();
  }

  shouldShowCaptcha() {
    return (
      typeof this.props.maxLoginAttempts2ShowCaptcha !== "undefined" &&
      typeof this.props.loginAttempts !== "undefined" &&
      this.props.loginAttempts >= this.props.maxLoginAttempts2ShowCaptcha
    );
  }

  handleAuthenticateValidation() {

    switch (this.state.authFlow) {
      case FLOW.OTP:
        if (this.state.otpCode == "") {
          this.setState({
            ...this.state,
            disableInput: false,
            errors: { ...this.state.errors, otp: "Single-use code is empty" },
          });
          return false;
        }
        break;
      default:
        if (this.state.user_password == "") {
          this.setState({
            ...this.state,
            disableInput: false,
            errors: { ...this.state.errors, password: "Password is empty" },
          });
          return false;
        }

        if (this.state.captcha_value == "" && this.shouldShowCaptcha()) {
          this.setState({
            ...this.state,
            disableInput: false,
            errors: { ...this.state.errors, password: "you must check CAPTCHA" },
          });
          return false;
        }
    }

    return true;
  }

  // Password and OTP flows submit as a native form POST: the backend login
  // strategies answer with a redirect plus flashed/persisted session state
  // (auth errors, login_attempts, the mfa_required '2fa' flow), which only a
  // top-level navigation renders correctly. The 2FA screen is rehydrated from
  // session by the blade on the post-redirect GET.
  onAuthenticate() {

    if (!this.handleAuthenticateValidation()) {
      return false;
    }

    this.setState({ ...this.state, disableInput: true });

    return true;
  }

  onChangeCaptchaProvider(value) {
    this.setState({ ...this.state, captcha_value: value });
  }

  onExpireCaptchaProvider() {
    this.setState({ ...this.state, captcha_value: "" });
  }

  onErrorCaptchaProvider() {
    this.setState({ ...this.state, captcha_value: "" });
  }

  onHandleUserNameChange(ev) {
    let { value, id } = ev.target;
    this.setState({ ...this.state, user_name: value });
  }

  onUserPasswordChange(ev) {
    let { errors } = this.state;
    let { value, id } = ev.target;
    if (value == "")
      // clean error
      errors[id] = "";
    this.setState({
      ...this.state,
      user_password: value,
      errors: { ...errors },
    });
  }

  onOTPCodeChange(value) {
    this.setState({ ...this.state, otpCode: value });
  }

  onTwoFactorCodeChange(value) {
    this.setState({
      ...this.state,
      twoFactorCode: value,
      errors: { ...this.state.errors, twofactor: "" },
    });
  }

  onRecoveryCodeChange(ev) {
    let { value } = ev.target;
    this.setState({
      ...this.state,
      recoveryCode: value,
      errors: { ...this.state.errors, recovery: "" },
    });
  }

  onTrustDeviceChange(ev) {
    this.setState({ ...this.state, trustDevice: ev.target.checked });
  }

  /**
   * Resets client-side MFA state and returns the user to the password screen.
   */
  resetToPasswordFlow() {
    this.setState({
      ...this.state,
      authFlow: FLOW.PASSWORD,
      disableInput: false,
      twoFactorCode: "",
      user_name: "",
      user_password: "",
      user_pic: "",
      user_fullname: "",
      user_verified: false,
      recoveryCode: "",
      trustDevice: false,
      errors: {
        ...this.state.errors,
        twofactor: "",
        recovery: "",
        email: "",
        otp: "",
        password: "",
      },
    });
    cancelLogin(this.props.token);
  }

  /**
   * Shared error handling for the 2FA verify / recovery AJAX calls.
   * @param {*} error superagent error
   * @param {string} field 'twofactor' | 'recovery'
   */
  handleMfaError(error, field) {
    const status = error ? error.status : undefined;
    const body = error && error.response ? error.response.body : null;
    const code = body ? body.error_code : null;

    if (
      status === HTTP_CODES.UNAUTHORIZED &&
      code === MFA_ERROR_CODE.MFA_SESSION_EXPIRED
    ) {
      this.resetToPasswordFlow();
      this.showAlert(
        "Your verification session has expired. Please sign in again.",
        "warning",
      );
      return;
    }

    if (status === HTTP_CODES.TOO_MANY_REQUESTS) {
      const msg =
        body && body.error_message
          ? body.error_message
          : "Too many attempts. Please try again later.";
      this.setState({
        ...this.state,
        disableInput: false,
        errors: { ...this.state.errors, [field]: msg },
      });
      return;
    }

    if (status === HTTP_CODES.UNAUTHORIZED) {
      const msg =
        field === "recovery"
          ? "Invalid recovery code. Please try again."
          : "Invalid or expired verification code. Please try again.";
      this.setState({
        ...this.state,
        disableInput: false,
        errors: { ...this.state.errors, [field]: msg },
      });
      return;
    }

    if (status === HTTP_CODES.PRECONDITION_FAILED) {
      this.setState({
        ...this.state,
        disableInput: false,
        errors: { ...this.state.errors, [field]: "Please enter a valid code." },
      });
      return;
    }

    /**
     * No HTTP status: the XHR likely followed a (possibly cross-origin) success redirect
     * it could not read. The IDP session may already be established, so reload and let
     * the server route us to the right place; a genuine network error just re-shows login.
     */
    if (typeof status === "undefined" || status === 0) {
      window.location.reload();
      return;
    }

    this.setState({ ...this.state, disableInput: false });
    this.showAlert("Oops... Something went wrong!", "error");
  }

  onVerify2FA() {
    if (this.state.disableInput) return;
    const { twoFactorCode, trustDevice, mfaMethod } = this.state;
    if (twoFactorCode === "") {
      this.setState({
        ...this.state,
        errors: {
          ...this.state.errors,
          twofactor: "Verification code is empty",
        },
      });
      return;
    }
    this.setState({
      ...this.state,
      disableInput: true,
      errors: { ...this.state.errors, twofactor: "" },
    });

    verify2FA(twoFactorCode, mfaMethod, trustDevice, this.props.token).then(
      (payload) => {
        // Success: the backend redirected (302) and the XHR followed it; navigate the top
        // window to the final destination to resume the normal redirect / OIDC flow.
        window.location.href = payload.finalUrl || window.location.href;
      },
      (error) => {
        this.handleMfaError(error, "twofactor");
      },
    );
  }

  onResend2FA() {
    const promise = resend2FA(this.state.mfaMethod, this.props.token);

    promise.then(
      (payload) => {
        const { response } = payload;
        this.setState({
          ...this.state,
          otpLength:
            response && response.otp_length
              ? response.otp_length
              : this.state.otpLength,
          otpLifetime:
            response && response.otp_lifetime
              ? response.otp_lifetime
              : this.state.otpLifetime,
          codeVersion: this.state.codeVersion + 1,
          errors: { ...this.state.errors, twofactor: "" },
        });
        this.showAlert(
          "A new verification code has been sent to your email.",
          "success",
        );
      },
      (error) => {
        const status = error ? error.status : undefined;
        const body = error && error.response ? error.response.body : null;
        const code = body ? body.error_code : null;

        if (
          status === HTTP_CODES.UNAUTHORIZED &&
          code === MFA_ERROR_CODE.MFA_SESSION_EXPIRED
        ) {
          this.resetToPasswordFlow();
          this.showAlert(
            "Your verification session has expired. Please sign in again.",
            "warning",
          );
          return;
        }
        if (status === HTTP_CODES.TOO_MANY_REQUESTS) {
          const msg =
            body && body.error_message
              ? body.error_message
              : "Too many attempts. Please try again later.";
          this.showAlert(msg, "warning");
          return;
        }
        this.showAlert(
          "Oops... Something went wrong while resending the code.",
          "error",
        );
      },
    );

    // Returned so the form can reset its expiry countdown once the resend resolves.
    return promise;
  }

  onVerifyRecovery() {
    if (this.state.disableInput) return;
    const { recoveryCode } = this.state;
    if (recoveryCode === "") {
      this.setState({
        ...this.state,
        errors: { ...this.state.errors, recovery: "Recovery code is empty" },
      });
      return;
    }
    this.setState({
      ...this.state,
      disableInput: true,
      errors: { ...this.state.errors, recovery: "" },
    });

    verifyRecoveryCode(recoveryCode, this.props.token).then(
      (payload) => {
        window.location.href = payload.finalUrl || window.location.href;
      },
      (error) => {
        this.handleMfaError(error, "recovery");
      },
    );
  }

  onUseRecovery() {
    this.setState({
      ...this.state,
      authFlow: FLOW.RECOVERY,
      errors: { ...this.state.errors, recovery: "" },
    });
  }

  onBackToOtp() {
    this.setState({
      ...this.state,
      authFlow: FLOW.MFA,
      errors: { ...this.state.errors, twofactor: "" },
    });
  }

  onValidateEmail(ev) {
    ev.preventDefault();
    let { user_name } = this.state;
    user_name = user_name?.trim();

    if (user_name == "") {
      return false;
    }
    if (!emailValidator(user_name)) {
      return false;
    }
    this.setState({ ...this.state, disableInput: true });

    verifyAccount(user_name, this.props.token).then(
      (payload) => {
        let { response } = payload;

        let error = "";
        if (response.is_active === false) {
          error = `Your user account is currently locked. Please <a href="${this.props.helpAction}">contact support</a> for further assistance.`;
        } else if (
          response.is_active === true &&
          response.is_verified === false
        ) {
          error =
            "Your email has not been verified. Please check your inbox or resend the verification email.";
        }

        this.setState(
          {
            ...this.state,
            user_pic: response.pic,
            user_fullname: response.full_name,
            user_verified: true,
            user_active: response.is_active,
            email_verified: response.is_verified,
            authFlow: response.has_password_set ? FLOW.PASSWORD : FLOW.OTP,
            errors: {
              email: error,
              otp: "",
              password: "",
            },
            disableInput: false,
          },
          function () {
            //Once the state is updated, it's now possible to trigger emitOtpAction.
            //No need to wait for the component to update.
            if (!response.has_password_set && response.is_verified !== false) {
              this.emitOtpAction();
            }
          },
        );
      },
      (error) => {
        let { response, status, message } = error;

        let newErrors = {};

        newErrors["password"] = "";
        newErrors["email"] = " ";

        if (status == HTTP_CODES.TOO_MANY_REQUESTS) {
          newErrors["email"] = "Too many requests. Try it later.";
        }

        this.setState({
          ...this.state,
          user_pic: null,
          user_fullname: null,
          user_verified: false,
          errors: newErrors,
          disableInput: false,
        });
      },
    );
    return true;
  }

  resendVerificationEmail(ev) {
    ev.preventDefault();
    let { user_name } = this.state;
    user_name = user_name?.trim();

    if (!user_name) {
      this.showAlert(
        "Something went wrong while trying to resend the verification email. Please try again later.",
        "error",
      );
      return;
    }

    resendVerificationEmail(user_name, this.props.token).then(
      (payload) => {
        this.showAlert(
          "We've sent you a verification email. Please check your inbox and click the link to verify your account.",
          "success",
        );
      },
      (error) => {
        handleErrorResponse(error, (title, messageLines, type) => {
          const message = (messageLines ?? []).join(", ");
          this.showAlert(`${title}: ${message}`, type);
        });
      },
    );
  }

  handleDelete(ev) {
    ev.preventDefault();
    this.setState({
      ...this.state,
      user_name: null,
      user_pic: null,
      user_fullname: null,
      user_verified: false,
      user_active: null,
      email_verified: null,
      authFlow: "password",
      errors: {
        email: "",
        otp: "",
        password: "",
      },
    });
    return false;
  }

  handleClickShowPassword(ev) {
    ev.preventDefault();
    this.setState({ ...this.state, showPassword: !this.state.showPassword });
  }

  handleMouseDownPassword(ev) {
    ev.preventDefault();
  }

  existingUserCanContinue() {
    const { user_active, email_verified } = this.state;
    return user_active !== false && email_verified !== false;
  }

  isMfaFlow() {
    return (
      this.state.authFlow === FLOW.MFA || this.state.authFlow === FLOW.RECOVERY
    );
  }

  getSignUpSignInTitle() {
    const { errors, user_active } = this.state;

    if (errors.email && this.existingUserCanContinue()) {
      return "Create an account for:";
    }
    return "Sign in";
  }

  handleSnackbarClose() {
    this.setState({
      ...this.state,
      notification: {
        message: null,
        severity: "info",
      },
    });
  }

  componentDidUpdate(prevProps, prevState) {
    if (
      this.state.user_verified &&
      this.existingUserCanContinue() &&
      prevState.authFlow !== this.state.authFlow
    ) {
      this.setState({
        ...this.state,
        captcha_value: "",
      });
    }
  }

  render() {
    const showTwoFactorForm = this.state.authFlow === FLOW.MFA;
    const showRecoveryForm = this.state.authFlow === FLOW.RECOVERY;
    const isPasswordFlow =
      !showTwoFactorForm &&
      !showRecoveryForm &&
      !this.isMfaFlow() &&
      this.state.user_verified &&
      this.existingUserCanContinue() &&
      this.state.authFlow === FLOW.PASSWORD;
    const isOtpFlow =
      !showTwoFactorForm &&
      !showRecoveryForm &&
      !this.isMfaFlow() &&
      this.state.user_verified &&
      this.existingUserCanContinue() &&
      this.state.authFlow === FLOW.OTP;
    const showDefaultFlow = !showTwoFactorForm && !showRecoveryForm && !isPasswordFlow && !isOtpFlow;
    const createAccountAction = this.props.createAccountAction +
      (this.state.user_name ? `?email=${encodeURIComponent(this.state.user_name)}` : "");

    return (
      <Container
        component="main"
        maxWidth="xs"
        className={styles.main_container}
      >
        <CssBaseline />
        {this.state.showInfoBanner && (
          <Banner infoBannerContent={this.state.infoBannerContent} />
        )}
        <Container className={styles.login_container}>
          <div className={styles.inner_container}>
            <Typography component="h1" className={styles.app_logo_container}>
              <a href={window.location.href} target="_self">
                <img
                  className={styles.app_logo}
                  alt={this.props.appName}
                  src={this.props.appLogo}
                />
              </a>
            </Typography>
            <Typography component="h1" variant="h5">
              {this.getSignUpSignInTitle()}
              {this.state.user_fullname && (
                <Chip
                  avatar={
                    <Avatar
                      alt={this.state.user_name}
                      src={this.state.user_pic}
                    />
                  }
                  variant="outlined"
                  className={styles.valid_user_name_chip}
                  label={this.state.user_name}
                  onDelete={this.handleDelete}
                />
              )}
            </Typography>
            {showTwoFactorForm && (
              <TwoFactorForm
                otpCode={this.state.twoFactorCode}
                otpError={this.state.errors.twofactor}
                otpLength={this.state.otpLength}
                otpLifetime={this.state.otpLifetime}
                codeVersion={this.state.codeVersion}
                trustDevice={this.state.trustDevice}
                disableInput={this.state.disableInput}
                onTrustDeviceChange={this.onTrustDeviceChange}
                onResend={this.onResend2FA}
                onUseRecovery={this.onUseRecovery}
                onCancel={this.resetToPasswordFlow}
                onCodeChange={this.onTwoFactorCodeChange}
                onVerify={this.onVerify2FA}
              />
            )}
            {showRecoveryForm && (
              <RecoveryCodeForm
                recoveryCode={this.state.recoveryCode}
                recoveryError={this.state.errors.recovery}
                disableInput={this.state.disableInput}
                onRecoveryCodeChange={this.onRecoveryCodeChange}
                onVerify={this.onVerifyRecovery}
                onBackToOtp={this.onBackToOtp}
                onCancel={this.resetToPasswordFlow}
              />
            )}
            {isPasswordFlow && (
              // proceed to ask for password ( 2nd step )
              <>
                <PasswordInputForm
                  formAction={this.props.formAction}
                  disableInput={this.state.disableInput}
                  showPassword={this.state.showPassword}
                  passwordValue={this.state.user_password}
                  passwordError={this.state.errors.password}
                  userNameValue={this.state.user_name}
                  csrfToken={this.props.token}
                  captchaPublicKey={this.props.captchaPublicKey}
                  forgotPasswordAction={this.props.forgotPasswordAction}
                  loginAttempts={this.props?.loginAttempts}
                  maxLoginFailedAttempts={this.props?.maxLoginFailedAttempts}
                  userIsActive={this.props?.user_is_active}
                  helpAction={this.props.helpAction}
                  onAuthenticate={this.onAuthenticate}
                  onUserPasswordChange={this.onUserPasswordChange}
                  handleClickShowPassword={this.handleClickShowPassword}
                  handleMouseDownPassword={this.handleMouseDownPassword}
                  shouldShowCaptcha={this.shouldShowCaptcha}
                  onChangeCaptchaProvider={this.onChangeCaptchaProvider}
                  onExpireCaptchaProvider={this.onExpireCaptchaProvider}
                  onErrorCaptchaProvider={this.onErrorCaptchaProvider}
                  handleEmitOtpAction={this.handleEmitOtpAction}
                />
                <HelpLinks
                  userName={this.state.user_name}
                  appName={this.props.appName}
                  forgotPasswordAction={this.props.forgotPasswordAction}
                  verifyEmailAction={this.props.verifyEmailAction}
                  helpAction={this.props.helpAction}
                  showEmitOtpAction={false}
                  showForgotPasswordAction={false}
                  showVerifyEmailAction={false}
                  showHelpAction={true}
                  emitOtpAction={this.handleEmitOtpAction}
                />
              </>
            )}
            {isOtpFlow && (
              // proceed to ask for password ( 2nd step )
              <>
                <OTPInputForm
                  disableInput={this.state.disableInput}
                  formAction={this.props.formAction}
                  otpCode={this.state.otpCode}
                  otpError={this.state.errors.otp}
                  otpLength={this.props.otpLength}
                  userNameValue={this.state.user_name}
                  csrfToken={this.props.token}
                  captchaPublicKey={this.props.captchaPublicKey}
                  loginAttempts={this.props?.loginAttempts}
                  onCodeChange={this.onOTPCodeChange}
                  shouldShowCaptcha={this.shouldShowCaptcha}
                  onChangeCaptchaProvider={this.onChangeCaptchaProvider}
                  onExpireCaptchaProvider={this.onExpireCaptchaProvider}
                  onErrorCaptchaProvider={this.onErrorCaptchaProvider}
                  onReset={this.handleDelete}
                  onAuthenticate={this.onAuthenticate}
                />
                <OTPHelpLinks emitOtpAction={this.handleEmitOtpAction} />
              </>
            )}
            {showDefaultFlow && (
              <>
                {this.state.allowNativeAuth && (
                  <EmailInputForm
                    value={this.state.user_name ?? ""}
                    disableInput={this.state.disableInput}
                    emailError={this.state.errors.email}
                    onValidateEmail={this.onValidateEmail}
                    onHandleUserNameChange={this.onHandleUserNameChange}
                  />
                )}
                {this.state.errors.email === "" &&
                  this.props.thirdPartyProviders.length > 0 && (
                    <ThirdPartyIdentityProviders
                      thirdPartyProviders={this.props.thirdPartyProviders}
                      formAction={this.props.formAction}
                      disableInput={this.state.disableInput}
                      allowNativeAuth={this.state.allowNativeAuth}
                    />
                  )}
                {
                  // we already had an interaction and got an user error...
                  this.state.errors.email !== "" && (
                    <>
                      {this.existingUserCanContinue() && (
                        <EmailErrorActions
                          disableInput={this.state.disableInput}
                          createAccountAction={createAccountAction}
                          emitOtpAction={this.handleEmitOtpAction}
                          onValidateEmail={this.onValidateEmail}
                        />
                      )}
                      {this.state.user_active === true &&
                        this.state.email_verified === false && (
                          <Button
                            variant="contained"
                            color="primary"
                            title="Resend verification email"
                            className={styles.apply_button}
                            onClick={this.resendVerificationEmail}
                          >
                            Resend verification email
                          </Button>
                        )}
                      <HelpLinks
                        userName={this.state.user_name}
                        appName={this.props.appName}
                        forgotPasswordAction={this.props.forgotPasswordAction}
                        verifyEmailAction={this.props.verifyEmailAction}
                        helpAction={this.props.helpAction}
                        showEmitOtpAction={false}
                        showForgotPasswordAction={false}
                        showVerifyEmailAction={false}
                        showHelpAction={true}
                        emitOtpAction={this.handleEmitOtpAction}
                      />
                    </>
                  )
                }
              </>
            )}
            <CustomSnackbar
              message={this.state.notification.message}
              severity={this.state.notification.severity}
              onClose={this.handleSnackbarClose}
            />
          </div>
        </Container>
      </Container>
    );
  }
}

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
        textTransform: "none",
      },
    },
  },
});

ReactDOM.render(
  <MuiThemeProvider theme={theme}>
    <LoginPage {...config} />
  </MuiThemeProvider>,
  document.querySelector("#root"),
);
