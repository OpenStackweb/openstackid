import React, {useState, useEffect} from 'react';
import Button from '@material-ui/core/Button';
import Link from '@material-ui/core/Link';
import FormControlLabel from '@material-ui/core/FormControlLabel';
import Checkbox from '@material-ui/core/Checkbox';
import OtpCodeInput from './otp_code_input';
import useOtpCountdown from './use_otp_countdown';
import styles from '../login.module.scss';

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

    const {secondsLeft, expired} = useOtpCountdown(otpLifetime, codeVersion);
    const [cooldown, setCooldown] = useState(0);

    // 1s ticker for the resend cooldown (the expiry countdown lives in the hook).
    useEffect(() => {
        const timer = setInterval(() => {
            setCooldown(prev => (prev > 0 ? prev - 1 : 0));
        }, 1000);
        return () => clearInterval(timer);
    }, []);

    const handleSubmit = (ev) => {
        ev.preventDefault();
        if (expired) return;
        onVerify();
    };

    const handleResend = (ev) => {
        ev.preventDefault();
        if (cooldown > 0 || disableInput) return;
        setCooldown(RESEND_COOLDOWN_SECONDS);
        // A successful resend resets the expiry countdown through the parent's
        // codeVersion bump; failures are surfaced by the parent as well.
        const result = onResend();
        if (result && typeof result.catch === 'function') {
            result.catch(() => {});
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
        <form onSubmit={handleSubmit} target="_self" className={styles.otp_form}>
            <OtpCodeInput
                id="two_factor_code"
                otpCode={otpCode}
                otpError={otpError}
                otpLength={otpLength}
                onCodeChange={onCodeChange}
                countdownActive={true}
                secondsLeft={secondsLeft}
                expired={expired}
            />
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
                        disabled={disableInput || otpCode === '' || expired}
                        color="primary"
                        type="submit"
                        target="_self">
                    VERIFY
                </Button>
            </div>
            <div className={styles.footer_instructions}>
                <p className={styles.otp_p}>
                    Didn't receive it? Check your spam folder or{" "}
                    <Link href="#" onClick={handleResend} variant="body2" target="_self"
                          className={(cooldown > 0 || disableInput) ? styles.disabled_link : ''}>
                        {cooldown > 0 ? `resend code (${cooldown}s)` : 'resend code'}
                    </Link>.
                </p>
                {/* "Use a different method" is intentionally hidden in Phase I (email_otp only). */}
                <hr className={styles.separator}/>
                <div className={styles.box}>
                    <Link href="#" onClick={handleCancel} variant="body2" target="_self">
                        Cancel
                    </Link>
                    <Link href="#" onClick={handleRecovery} variant="body2" target="_self">
                        Use a recovery code instead
                    </Link>
                </div>
            </div>
        </form>
    );
}

export default TwoFactorForm;
