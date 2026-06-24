import React from "react";
import Paper from "@material-ui/core/Paper";
import TextField from "@material-ui/core/TextField";
import Button from "@material-ui/core/Button";
import styles from "../login.module.scss";
import HTMLRender from "../../shared/HTMLRender";

const EmailInputForm = ({
  value,
  onValidateEmail,
  onHandleUserNameChange,
  disableInput,
  emailError,
}) => {
  return (
    <>
      <Paper
        elevation={0}
        component="form"
        target="_self"
        className={styles.paper_root}
        onSubmit={onValidateEmail}
      >
        <TextField
          id="email"
          name="email"
          value={value}
          autoComplete="email"
          variant="outlined"
          margin="normal"
          required
          fullWidth
          disabled={disableInput}
          label="Email Address"
          autoFocus={true}
          onChange={onHandleUserNameChange}
          error={emailError != ""}
        />
        {emailError == "" && (
          <Button
            variant="contained"
            color="primary"
            title="Continue"
            className={styles.apply_button}
            disabled={disableInput}
            onClick={onValidateEmail}
          >
            &gt;
          </Button>
        )}
      </Paper>
      {emailError != "" && (
        <HTMLRender component="p" className={styles.error_label}>
          {emailError}
        </HTMLRender>
      )}
    </>
  );
};

export default EmailInputForm;
