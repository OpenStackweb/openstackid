import React from 'react';
import OtpInput from 'react-otp-input';
import {formatTime} from '../../utils';
import styles from '../login.module.scss';
import HTMLRender from '../../shared/HTMLRender';

/**
 * Shared single-use-code entry block: subtitle, code boxes, error message and
 * optional expiry countdown. Used by both the passwordless OTP form and the
 * MFA verification form; the owning form keeps the submit mechanics.
 */
const OtpCodeInput = ({
                          id,
                          otpCode,
                          otpError,
                          otpLength,
                          onCodeChange,
                          countdownActive,
                          secondsLeft,
                          expired,
                          subtitle = 'Enter the single-use code sent to your email:'
                      }) => {
    return (
        <>
            <div className={styles.subtitle}>{subtitle}</div>
            <div className={styles.code_input}>
                <OtpInput
                    id={id}
                    value={otpCode}
                    onChange={onCodeChange}
                    numInputs={otpLength}
                    inputType="tel"
                    renderInput={(props) => <input {...props} />}
                    shouldAutoFocus={true}
                    hasErrored={!!otpError}
                    errorStyle={{border: '1px solid #e5424d'}}
                    data-testid={id}
                />
            </div>
            {otpError &&
                <HTMLRender component="p" className={styles.error_label} data-testid="error-label">
                    {otpError}
                </HTMLRender>
            }
            {countdownActive &&
                <p className={styles.countdown}>
                    {expired
                        ? 'Your verification code has expired. Please request a new one.'
                        : `Code expires in ${formatTime(secondsLeft)}.`}
                </p>
            }
        </>
    );
};

export default OtpCodeInput;
