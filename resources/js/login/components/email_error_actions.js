import React from "react";
import Grid from "@material-ui/core/Grid";
import Button from "@material-ui/core/Button";
import styles from "../login.module.scss";

const EmailErrorActions = ({
  emitOtpAction,
  createAccountAction,
  onValidateEmail,
  disableInput,
}) => {
  return (
    <Grid container spacing={1}>
      <Grid
        container
        item
        spacing={1}
        justifyContent="center"
        alignItems="center"
      >
        <Grid item>
          <Button
            variant="contained"
            onClick={emitOtpAction}
            type="button"
            className={styles.secondary_btn}
            color="primary"
          >
            Email me a one time use code
          </Button>
        </Grid>
        <Grid item>
          <Button
            variant="contained"
            href={createAccountAction}
            type="button"
            target="_self"
            className={styles.secondary_btn}
            color="primary"
          >
            Register and set a password
          </Button>
        </Grid>
        <Grid item>
          <Button
            variant="text"
            onClick={onValidateEmail}
            disabled={disableInput}
            className={styles.secondary_btn}
            color="primary"
          >
            Adjust email above and try again
          </Button>
        </Grid>
      </Grid>
    </Grid>
  );
};

export default EmailErrorActions;
