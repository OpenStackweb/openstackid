import React, {useState, useEffect} from "react";
import Link from "@material-ui/core/Link";
import styles from "../login.module.scss";
import {RESEND_COOLDOWN_SECONDS} from "../constants";

const OTPHelpLinks = ({ emitOtpAction, disableInput }) => {
  const [cooldown, setCooldown] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setCooldown((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const handleResend = (ev) => {
    ev.preventDefault();
    if (cooldown > 0 || disableInput) return;
    setCooldown(RESEND_COOLDOWN_SECONDS);
    emitOtpAction(ev);
  };

  return (
    <>
      <hr className={styles.separator} />
      <p className={styles.otp_p}>Didn't receive it ?</p>
      <p className={styles.otp_p}>
        Check your spam folder or{" "}
        <Link
          href="#"
          onClick={handleResend}
          variant="body2"
          target="_self"
          className={(cooldown > 0 || disableInput) ? styles.disabled_link : ''}
        >
          {cooldown > 0 ? `resend email (${cooldown}s)` : 'resend email.'}
        </Link>
      </p>
    </>
  );
};

export default OTPHelpLinks;
