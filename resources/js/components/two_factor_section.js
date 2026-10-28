import React, {useState} from "react";
import Button from "@material-ui/core/Button";
import Typography from "@material-ui/core/Typography";
import {enableTwoFactor} from "../profile/actions";
import {handleErrorResponse} from "../utils";
import RecoveryCodesPanel from "./recovery_codes_panel";

const DEFAULT_METHOD = "email_otp";

const TwoFactorSection = ({
                              twoFactorEnabled,
                              recoveryCodesRemaining,
                              recoveryCodesTotal,
                              recoveryCodesLowThreshold,
                              email,
                              appName
                          }) => {
    const [enabled, setEnabled] = useState(twoFactorEnabled);
    const [loading, setLoading] = useState(false);
    const [remaining, setRemaining] = useState(recoveryCodesRemaining);
    const [total, setTotal] = useState(recoveryCodesTotal);
    const [enrollmentCodes, setEnrollmentCodes] = useState(null);

    const handleEnable = () => {
        setLoading(true);
        enableTwoFactor(DEFAULT_METHOD).then(({response}) => {
            setLoading(false);
            setRemaining(response.recovery_codes.length);
            setTotal(response.recovery_codes.length);
            setEnrollmentCodes(response.recovery_codes);
            setEnabled(true);
        }).catch((err) => {
            setLoading(false);
            handleErrorResponse(err);
        });
    };

    if (!enabled) {
        return (
            <>
                <Typography variant="body2" data-testid="two-factor-disabled-message">
                    Two-factor authentication is not enabled for your account.
                </Typography>
                <Button
                    variant="contained"
                    color="primary"
                    onClick={handleEnable}
                    disabled={loading}
                    data-testid="enable-two-factor-button"
                >
                    Enable Two-Factor Authentication (Email OTP)
                </Button>
            </>
        );
    }

    return (
        <RecoveryCodesPanel
            recoveryCodesRemaining={remaining}
            recoveryCodesTotal={total}
            lowCodeThreshold={recoveryCodesLowThreshold}
            email={email}
            appName={appName}
            initialCodes={enrollmentCodes}/>
    );
};

export default TwoFactorSection;
