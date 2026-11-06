import React, { useEffect, useRef, useState } from "react";
import { Turnstile } from "@marsidev/react-turnstile";
import Button from "@material-ui/core/Button";
import Grid from "@material-ui/core/Grid";

// Turnstile does not always invoke onError when its iframe cannot be loaded (e.g. a network
// error reaching challenges.cloudflare.com): the widget just never renders and never issues a
// token. Without a token the backend rejects the form, so after this long without one we treat
// the security check as failed and let the user retry it.
export const CAPTCHA_LOAD_TIMEOUT_MS = 15000;

export const CAPTCHA_STATUS = {
  VERIFYING: "verifying",
  SOLVED: "solved",
  FAILED: "failed",
};

export const CAPTCHA_MESSAGES = {
  VERIFYING: "Please wait while we verify you are human, then submit again.",
  FAILED:
    "We couldn't load the security check. Check your connection or disable any content blocker, then retry.",
};

/**
 * State for a Turnstile-protected form. With an empty siteKey the captcha is disabled:
 * nothing renders and ensureToken() always lets the form through.
 */
export const useTurnstileCaptcha = (siteKey) => {
  const ref = useRef(null);
  const [status, setStatus] = useState(CAPTCHA_STATUS.VERIFYING);
  const [submitAttempted, setSubmitAttempted] = useState(false);

  useEffect(() => {
    if (!siteKey || status !== CAPTCHA_STATUS.VERIFYING) {
      return undefined;
    }
    const timer = setTimeout(
      () => setStatus(CAPTCHA_STATUS.FAILED),
      CAPTCHA_LOAD_TIMEOUT_MS
    );
    return () => clearTimeout(timer);
  }, [siteKey, status]);

  // Call before posting the form: true when it may be posted, otherwise it surfaces why not.
  const ensureToken = () => {
    if (!siteKey || ref.current?.getResponse()) {
      return true;
    }
    setSubmitAttempted(true);
    return false;
  };

  const onSuccess = () => {
    setStatus(CAPTCHA_STATUS.SOLVED);
    setSubmitAttempted(false);
  };

  const restart = () => {
    setStatus(CAPTCHA_STATUS.VERIFYING);
    ref.current?.reset();
  };

  const retry = () => {
    setSubmitAttempted(false);
    restart();
  };

  const onError = () => setStatus(CAPTCHA_STATUS.FAILED);

  const message =
    status === CAPTCHA_STATUS.FAILED
      ? CAPTCHA_MESSAGES.FAILED
      : status === CAPTCHA_STATUS.VERIFYING && submitAttempted
        ? CAPTCHA_MESSAGES.VERIFYING
        : null;

  return { siteKey, ref, status, message, ensureToken, onSuccess, restart, retry, onError };
};

/**
 * Renders the Turnstile widget plus its status message and retry action.
 * `captcha` is the object returned by useTurnstileCaptcha().
 */
export const TurnstileCaptcha = ({ captcha, className, errorClassName }) => {
  if (!captcha.siteKey) {
    return null;
  }
  return (
    <Grid container item justifyContent="center">
      <Turnstile
        ref={captcha.ref}
        className={className}
        siteKey={captcha.siteKey}
        options={{ responseFieldName: "cf-turnstile-response" }}
        onSuccess={captcha.onSuccess}
        onExpire={captcha.restart}
        onTimeout={captcha.restart}
        onError={captcha.onError}
      />
      {captcha.message && (
        <div className={errorClassName} role="alert">
          {captcha.message}
        </div>
      )}
      {captcha.status === CAPTCHA_STATUS.FAILED && (
        <Button type="button" size="small" color="primary" onClick={captcha.retry}>
          Retry security check
        </Button>
      )}
    </Grid>
  );
};
