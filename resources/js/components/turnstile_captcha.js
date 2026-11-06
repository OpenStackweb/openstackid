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
  // Turnstile is showing a challenge the user has to complete: not a load failure.
  INTERACTIVE: "interactive",
  SOLVED: "solved",
  FAILED: "failed",
};

export const CAPTCHA_MESSAGES = {
  VERIFYING: "Please wait while we verify you are human, then submit again.",
  INTERACTIVE: "Complete the security check, then submit again.",
  FAILED:
    "We couldn't load the security check. Check your connection or disable any content blocker, then retry.",
};

// Id of the <script> that @marsidev/react-turnstile injects for api.js (its default).
export const TURNSTILE_SCRIPT_ID = "cf-turnstile-script";

/**
 * State for a Turnstile-protected form. With an empty siteKey the captcha is disabled:
 * nothing renders and ensureToken() always lets the form through.
 */
export const useTurnstileCaptcha = (siteKey) => {
  const ref = useRef(null);
  const [status, setStatus] = useState(CAPTCHA_STATUS.VERIFYING);
  const [submitAttempted, setSubmitAttempted] = useState(false);
  // Bumped to remount the widget when api.js itself has to be loaded again.
  const [widgetKey, setWidgetKey] = useState(0);

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
    if (!window.turnstile) {
      // api.js never loaded (blocked or a network error), so there is no widget for reset()
      // to act on, and the library will not inject the script again while the failed
      // <script> element is still in the DOM. Drop it and remount the widget to reload it.
      document.getElementById(TURNSTILE_SCRIPT_ID)?.remove();
      setStatus(CAPTCHA_STATUS.VERIFYING);
      setWidgetKey((key) => key + 1);
      return;
    }
    restart();
  };

  const onBeforeInteractive = () => setStatus(CAPTCHA_STATUS.INTERACTIVE);

  const onError = () => setStatus(CAPTCHA_STATUS.FAILED);

  let message = null;
  if (status === CAPTCHA_STATUS.FAILED) {
    message = CAPTCHA_MESSAGES.FAILED;
  } else if (submitAttempted && status === CAPTCHA_STATUS.VERIFYING) {
    message = CAPTCHA_MESSAGES.VERIFYING;
  } else if (submitAttempted && status === CAPTCHA_STATUS.INTERACTIVE) {
    message = CAPTCHA_MESSAGES.INTERACTIVE;
  }

  return {
    siteKey,
    ref,
    widgetKey,
    status,
    message,
    ensureToken,
    onSuccess,
    onBeforeInteractive,
    restart,
    retry,
    onError,
  };
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
        key={captcha.widgetKey}
        ref={captcha.ref}
        className={className}
        siteKey={captcha.siteKey}
        options={{ responseFieldName: "cf-turnstile-response" }}
        onSuccess={captcha.onSuccess}
        onBeforeInteractive={captcha.onBeforeInteractive}
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
