import React, {useState, useEffect} from 'react';
import Button from '@material-ui/core/Button';
import Link from '@material-ui/core/Link';
import FormControlLabel from '@material-ui/core/FormControlLabel';
import Checkbox from '@material-ui/core/Checkbox';
import OtpInput from 'react-otp-input';
import {formatTime} from '../../utils';
import styles from '../login.module.scss';
import HTMLRender from '../../shared/HTMLRender';

// Cooldown applied to the resend action to avoid hammering the resend endpoint
// (the backend also rate-limits server-side).
const RESEND_COOLDOWN_SECONDS = 30;

const TwoFactorForm = ({
                           otpCode,
                           otpError,
                           otpLength,
                           otpLifetime,
                           codeVersion,
                           onCodeChange,
                           onVerify,
                           trustDevice,
                           onTrustDeviceChange,
                           onResend,
                           onUseRecovery,
                           onCancel,
                           disableInput
                       }) => {

    const [secondsLeft, setSecondsLeft] = useState(otpLifetime || 0);
    const [cooldown, setCooldown] = useState(0);

    // Reset the expiry countdown whenever a fresh code is issued (initial render or resend).
    useEffect(() => {
        setSecondsLeft(otpLifetime || 0);
    }, [otpLifetime, codeVersion]);

    // Single 1s ticker drives both the code-expiry countdown and the resend cooldown.
    useEffect(() => {
        const timer = setInterval(() => {
            setSecondsLeft(prev => (prev > 0 ? prev - 1 : 0));
            setCooldown(prev => (prev > 0 ? prev - 1 : 0));
        }, 1000);
        return () => clearInterval(timer);
    }, []);

    const expired = secondsLeft <= 0;

    const handleSubmit = (ev) => {
        ev.preventDefault();
        onVerify();
    };

    const handleResend = (ev) => {
        ev.preventDefault();
        if (cooldown > 0 || disableInput) return;
        setCooldown(RESEND_COOLDOWN_SECONDS);
        const result = onResend();
        if (result && typeof result.then === 'function') {
            result.then(() => setSecondsLeft(otpLifetime || 0)).catch(() => {});
        }
    };

    const handleRecovery = (ev) => {
        ev.preventDefault();
        onUseRecovery();
    };

    const handleCancel = (ev) => {
        ev.preventDefault();
        onCancel();
    };

    return (
        <form onSubmit={handleSubmit} target="_self" className={styles.otp_form} data-testid="two-factor-form">
            <div className={styles.subtitle}>Enter the single-use code sent to your email:</div>
            <div className={styles.code_input}>
                <OtpInput
                    id="two_factor_code"
                    value={otpCode}
                    onChange={onCodeChange}
                    numInputs={otpLength}
                    inputType="tel"
                    renderInput={(props) => <input {...props} />}
                    shouldAutoFocus={true}
                    hasErrored={!!otpError}
                    errorStyle={{border: '1px solid #e5424d'}}
                    data-testid="two_factor_code"
                />
            </div>
            {otpError &&
                <HTMLRender component="p" className={styles.error_label} data-testid="error-label">
                    {otpError}
                </HTMLRender>
            }
            <p className={styles.countdown}>
                {expired
                    ? 'Your verification code has expired. Please request a new one.'
                    : `Code expires in ${formatTime(secondsLeft)}.`}
            </p>
            <div className={styles.trust_device_row}>
                <FormControlLabel
                    disabled={disableInput}
                    control={
                        <Checkbox
                            checked={trustDevice}
                            onChange={onTrustDeviceChange}
                            name="trust_device"
                            id="trust_device"
                            color="primary"
                        />
                    }
                    label="Trust this device for 30 days"
                />
            </div>
            <div>
                <Button variant="contained"
                        disabled={disableInput || otpCode === ''}
                        color="primary"
                        type="submit"
                        target="_self"
                        data-testid="verify-button">
                    VERIFY
                </Button>
            </div>
            <div className={styles.footer_instructions}>
                <p className={styles.otp_p}>
                    Didn't receive it? Check your spam folder or{" "}
                    <Link href="#" onClick={handleResend} variant="body2" target="_self"
                          className={(cooldown > 0 || disableInput) ? styles.disabled_link : ''}
                          data-testid="resend-link">
                        {cooldown > 0 ? `resend code (${cooldown}s)` : 'resend code'}
                    </Link>.
                </p>
                {/* "Use a different method" is intentionally hidden in Phase I (email_otp only). */}
                <hr className={styles.separator}/>
                <div className={styles.box}>
                    <Link href="#" onClick={handleCancel} variant="body2" target="_self" data-testid="cancel-link">
                        Cancel
                    </Link>
                    <Link href="#" onClick={handleRecovery} variant="body2" target="_self" data-testid="use-recovery-link">
                        Use a recovery code instead
                    </Link>
                </div>
            </div>
        </form>
    );
}

export default TwoFactorForm;
