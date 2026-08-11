import {useState, useEffect} from 'react';

/**
 * Drives a 1-second expiry countdown for a single-use code.
 * Resets whenever a fresh code is issued (otpLifetime change or codeVersion bump).
 */
const useOtpCountdown = (otpLifetime, codeVersion) => {
    const [secondsLeft, setSecondsLeft] = useState(otpLifetime || 0);

    useEffect(() => {
        setSecondsLeft(otpLifetime || 0);
    }, [otpLifetime, codeVersion]);

    useEffect(() => {
        const timer = setInterval(
            () => setSecondsLeft(prev => (prev > 0 ? prev - 1 : 0)),
            1000
        );
        return () => clearInterval(timer);
    }, []);

    return {secondsLeft, expired: secondsLeft <= 0};
};

export default useOtpCountdown;
