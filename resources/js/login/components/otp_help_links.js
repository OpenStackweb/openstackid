import React from "react";
import Link from "@material-ui/core/Link";
import styles from "../login.module.scss";

const OTPHelpLinks = ({ emitOtpAction }) => {
  return (
    <>
      <hr className={styles.separator} />
      <p className={styles.otp_p}>Didn't receive it ?</p>
      <p className={styles.otp_p}>
        Check your spam folder or{" "}
        <Link href="#" onClick={emitOtpAction} variant="body2" target="_self">
          resend email.
        </Link>
      </p>
    </>
  );
};

export default OTPHelpLinks;
