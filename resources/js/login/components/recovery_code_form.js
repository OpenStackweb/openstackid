import React from 'react';
import TextField from '@material-ui/core/TextField';
import Button from '@material-ui/core/Button';
import Link from '@material-ui/core/Link';
import styles from '../login.module.scss';
import HTMLRender from '../../shared/HTMLRender';

const RecoveryCodeForm = ({
                              recoveryCode,
                              recoveryError,
                              onRecoveryCodeChange,
                              onVerify,
                              onBackToOtp,
                              onCancel,
                              disableInput
                          }) => {

    const handleSubmit = (ev) => {
        ev.preventDefault();
        onVerify();
    };

    const handleBack = (ev) => {
        ev.preventDefault();
        onBackToOtp();
    };

    const handleCancel = (ev) => {
        ev.preventDefault();
        onCancel();
    };

    return (
        <form onSubmit={handleSubmit} target="_self" className={styles.otp_form} data-testid="recovery-form">
            <div className={styles.subtitle}>Enter a recovery code</div>
            <p className={styles.info_message}>
                This is not the code we e-mailed you. Enter one of the recovery codes you saved
                when you enabled two-step verification.
            </p>
            <TextField
                id="recovery_code"
                name="recovery_code"
                value={recoveryCode}
                variant="outlined"
                margin="normal"
                required
                fullWidth
                autoFocus={true}
                label="Recovery code"
                helperText="8 characters, shown as ABCD-1234. The dash is optional."
                // Deliberately not "one-time-code": that hint makes the OS offer the
                // e-mailed OTP here, which is the wrong credential for this field.
                autoComplete="off"
                disabled={disableInput}
                onChange={onRecoveryCodeChange}
                error={!!recoveryError}
            />
            {recoveryError && (
                <HTMLRender component="p" className={styles.error_label} data-testid="error-label">
                {recoveryError}
                </HTMLRender>
            )}
            <div>
                <Button variant="contained"
                        disabled={disableInput || recoveryCode === ''}
                        color="primary"
                        type="submit"
                        target="_self"
                        data-testid="verify-button">
                    VERIFY
                </Button>
            </div>
            <div className={styles.footer_instructions}>
                <hr className={styles.separator}/>
                <div className={styles.box}>
                    <Link href="#" onClick={handleBack} variant="body2" target="_self"
                          data-testid="back-to-otp-link">
                        Back to verification code
                    </Link>
                    {" · "}
                    <Link href="#" onClick={handleCancel} variant="body2" target="_self" data-testid="cancel-link">
                        Cancel
                    </Link>
                </div>
            </div>
        </form>
    );
}

export default RecoveryCodeForm;
