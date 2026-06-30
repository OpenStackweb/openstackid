import React, { useRef } from "react";
import { Turnstile } from "@marsidev/react-turnstile";
import TextField from "@material-ui/core/TextField";
import Button from "@material-ui/core/Button";
import Grid from "@material-ui/core/Grid";
import FormControlLabel from "@material-ui/core/FormControlLabel";
import Checkbox from "@material-ui/core/Checkbox";
import Visibility from "@material-ui/icons/Visibility";
import VisibilityOff from "@material-ui/icons/VisibilityOff";
import InputAdornment from "@material-ui/core/InputAdornment";
import IconButton from "@material-ui/core/IconButton";
import ExistingAccountActions from "./existing_account_actions";
import styles from "../login.module.scss";
import HTMLRender from "../../shared/HTMLRender";

const PasswordInputForm = ({
  formAction,
  onAuthenticate,
  disableInput,
  showPassword,
  passwordValue,
  passwordError,
  onUserPasswordChange,
  handleClickShowPassword,
  handleMouseDownPassword,
  userNameValue,
  csrfToken,
  shouldShowCaptcha,
  captchaPublicKey,
  onChangeCaptchaProvider,
  onExpireCaptchaProvider,
  onErrorCaptchaProvider,
  handleEmitOtpAction,
  forgotPasswordAction,
  loginAttempts,
  maxLoginFailedAttempts,
  userIsActive,
  helpAction,
}) => {
  const formRef = useRef(null);
  const handleContinue = () => onAuthenticate(formRef.current);
  const onEnterSubmit = (ev) => {
    if (ev.key === "Enter") {
      ev.preventDefault();
      handleContinue();
    }
  }

  const ErrorMessage = () => {
    const attempts = parseInt(loginAttempts, 10);
    const maxAttempts = parseInt(maxLoginFailedAttempts, 10);
    const attemptsLeft = maxAttempts - attempts;

    if (!passwordError) return null;

    if (attempts > 0 && attempts < maxAttempts && userIsActive) {
      return (
        <p className={styles.error_label} data-testid="error-label">
          Incorrect password. You have {attemptsLeft} more attempt
          {attemptsLeft !== 1 ? "s" : ""} before your account is locked.
        </p>
      );
    }

    if (attempts > 0 && attempts === maxAttempts && userIsActive) {
      return (
        <p className={styles.error_label} data-testid="error-label">
          Incorrect password. You have reached the maximum ({maxAttempts})
          login attempts. Your account will be locked after another failed
          login.
        </p>
      );
    }

    if (attempts > 0 && attempts === maxAttempts && !userIsActive) {
      return (
        <p className={styles.error_label} data-testid="error-label">
          Your account has been locked due to multiple failed login
          attempts. Please <a href={helpAction}>contact support</a> to
          unlock it.
        </p>
      );
    }

    return (
      <HTMLRender component="p" className={styles.error_label} data-testid="error-label">
        {passwordError}
      </HTMLRender>
    );
  };

  return (
    <form
      method="post"
      action={formAction}
      ref={formRef}
      onSubmit={(ev) => ev.preventDefault()}
      target="_self"
    >
      <TextField
        id="password"
        name="password"
        type={showPassword ? "text" : "password"}
        value={passwordValue}
        variant="outlined"
        margin="normal"
        required
        fullWidth
        autoFocus={true}
        label="Enter Your Password"
        autoComplete="current-password"
        onChange={onUserPasswordChange}
        onKeyDown={onEnterSubmit}
        disabled={disableInput}
        InputProps={{
          endAdornment: (
            <InputAdornment position="end">
              <IconButton
                aria-label="toggle password visibility"
                onClick={handleClickShowPassword}
                onMouseDown={handleMouseDownPassword}
                edge="end"
              >
                {showPassword ? <Visibility /> : <VisibilityOff />}
              </IconButton>
            </InputAdornment>
          ),
        }}
      />
      <ErrorMessage />
      <Grid container spacing={1}>
        <Grid item xs={12}>
          <Button
            variant="contained"
            disabled={disableInput}
            onClick={handleContinue}
            type="button"
            color="primary"
          >
            Continue
          </Button>
        </Grid>
        <Grid item xs={12}>
          <FormControlLabel
            disabled={disableInput}
            control={
              <Checkbox
                value="remember"
                name="remember"
                id="remember"
                color="primary"
              />
            }
            label="Remember me"
          />
        </Grid>
      </Grid>

      <input
        type="hidden"
        value={userNameValue}
        id="username"
        name="username"
      />
      <input type="hidden" value={csrfToken} id="_token" name="_token" />
      <input type="hidden" value="password" id="flow" name="flow" />
      <input
        type="hidden"
        value={loginAttempts}
        id="login_attempts"
        name="login_attempts"
      />
      {shouldShowCaptcha() && captchaPublicKey && (
        <Turnstile
          className={styles.turnstile}
          siteKey={captchaPublicKey}
          options={{ responseFieldName: "cf-turnstile-response" }}
          onSuccess={onChangeCaptchaProvider}
          onExpire={onExpireCaptchaProvider}
          onError={onErrorCaptchaProvider}
        />
      )}
      <ExistingAccountActions
        emitOtpAction={handleEmitOtpAction}
        userName={userNameValue}
        disableInput={disableInput}
        forgotPasswordAction={forgotPasswordAction}
      />
    </form>
  );
};

export default PasswordInputForm;
