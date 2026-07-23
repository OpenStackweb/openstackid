import React from "react";
import { Turnstile } from "@marsidev/react-turnstile";
import Button from "@material-ui/core/Button";
import Link from "@material-ui/core/Link";
import OtpCodeInput from "./otp_code_input";
import useOtpCountdown from "./use_otp_countdown";
import styles from "../login.module.scss";

const OTPInputForm = ({
  disableInput,
  formAction,
  onAuthenticate,
  otpCode,
  otpError,
  otpLength,
  otpLifetime,
  codeVersion,
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
  const { secondsLeft, expired } = useOtpCountdown(otpLifetime ?? 0, codeVersion);
  // The countdown only renders when this page view knows when the code was
  // issued (a fresh emitOTP in this session). After a failed-submit page
  // reload the issuance time is unknown - showing a fresh full countdown
  // would overstate the code's validity, so none is shown.
  const countdownActive = otpLifetime != null && otpLifetime > 0;
  const blockExpired = countdownActive && expired;

  const handleSubmit = (ev) => {
    if (blockExpired || !onAuthenticate(ev.target))
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
      <OtpCodeInput
        id="otp_code"
        otpCode={otpCode}
        otpError={otpError}
        otpLength={otpLength}
        onCodeChange={onCodeChange}
        countdownActive={countdownActive}
        secondsLeft={secondsLeft}
        expired={expired}
      />
      <div>
        <Button
          variant="contained"
          disabled={disableInput || blockExpired}
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
