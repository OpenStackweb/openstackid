import React from "react";
import Grid from "@material-ui/core/Grid";
import Button from "@material-ui/core/Button";
import Link from "@material-ui/core/Link";
import styles from "../login.module.scss";

const ExistingAccountActions = ({
  emitOtpAction,
  forgotPasswordAction,
  userName,
  disableInput,
}) => {
  let forgotPasswordActionHref = forgotPasswordAction;

  if (userName) {
    forgotPasswordActionHref = `${forgotPasswordAction}?email=${encodeURIComponent(userName)}`;
  }

  return (
    <Grid container spacing={1} style={{ marginTop: "30px" }}>
      <Grid item xs={12}>
        <Button
          variant="contained"
          onClick={emitOtpAction}
          type="button"
          disabled={disableInput}
          className={styles.secondary_btn}
          color="primary"
        >
          Sign in by emailing me a single-use code
        </Button>
      </Grid>
      <Grid item xs={12}>
        <Link
          onClick={disableInput ? (e) => e.preventDefault() : undefined}
          href={forgotPasswordActionHref}
          target="_self"
          variant="body2"
        >
          Reset your password
        </Link>
      </Grid>
    </Grid>
  );
};

export default ExistingAccountActions;
