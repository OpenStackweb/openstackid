import React, { useMemo } from "react";
import { Turnstile } from "@marsidev/react-turnstile";
import Button from "@material-ui/core/Button";
import Link from "@material-ui/core/Link";
import OtpInput from "react-otp-input";
import styles from "../login.module.scss";
import HTMLRender from "../../shared/HTMLRender";

const OTPInputForm = ({
  disableInput,
  formAction,
  onAuthenticate,
  otpCode,
  otpError,
  otpLength,
  onCodeChange,
  userNameValue,
  csrfToken,
  shouldShowCaptcha,
  captchaPublicKey,
  onChangeCaptchaProvider,
  onExpireCaptchaProvider,
  onErrorCaptchaProvider,
  onReset,
  loginAttempts,
}) => {
  const showCaptcha = shouldShowCaptcha();
  const handleSubmit = (ev) => {
    if (!onAuthenticate(ev.target))
    {
      ev.preventDefault();
    }
  }

  return (
    <form
      method="post"
      action={formAction}
      onSubmit={handleSubmit}
      target="_self"
      className={styles.otp_form}
    >
      <div className={styles.subtitle}>
        Enter the single-use code sent to your email:
      </div>
      <div className={styles.code_input}>
        <OtpInput
          id="otp_code"
          value={otpCode}
          onChange={onCodeChange}
          numInputs={otpLength}
          inputType="tel"
          renderInput={(props) => <input {...props} />}
          shouldAutoFocus={true}
          hasErrored={!!otpError}
          errorStyle={{ border: "1px solid #e5424d" }}
          data-testid="otp_code"
        />
      </div>
      {otpError && (
        <HTMLRender component="p" className={styles.error_label}>
          {otpError}
        </HTMLRender>
      )}
      <div>
        <Button
          variant="contained"
          disabled={disableInput}
          color="primary"
          type="submit"
          target="_self"
        >
          CONTINUE
        </Button>
      </div>
      <div className={styles.footer_instructions}>
        <p className={styles.otp_p}>
          <Link href="#" onClick={onReset} variant="body2" target="_self">
            Sign in using a different e-mail
          </Link>
        </p>
        <div className={styles.after_login_instructions}>
          <div>After you login you will be e-mailed a link to</div>
          <div>set a password and complete your account.</div>
        </div>
      </div>
      <input
        type="hidden"
        value={userNameValue}
        id="username"
        name="username"
      />
      <input type="hidden" value={csrfToken} id="_token" name="_token" />
      <input type="hidden" value="otp" id="flow" name="flow" />
      <input type="hidden" value={otpCode} id="password" name="password" />
      <input type="hidden" value="email" id="connection" name="connection" />
      <input
        type="hidden"
        value={loginAttempts}
        id="login_attempts"
        name="login_attempts"
      />
      {showCaptcha && captchaPublicKey && (
        <Turnstile
          className={styles.turnstile}
          siteKey={captchaPublicKey}
          options={{ responseFieldName: "cf-turnstile-response" }}
          onSuccess={onChangeCaptchaProvider}
          onExpire={onExpireCaptchaProvider}
          onError={onErrorCaptchaProvider}
        />
      )}
    </form>
  );
};

export default OTPInputForm;
