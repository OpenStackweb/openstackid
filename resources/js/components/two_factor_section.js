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
                              recoveryCodesAcknowledged = true,
                              recoveryCodesEnrollmentRequired = false,
                              recoveryCodesEnrollmentAvailable = false,
                              recoveryCodesWindowOpen = false,
                              hasPassword = true,
                              email,
                              appName
                          }) => {
    const [enabled, setEnabled] = useState(twoFactorEnabled);
    const [loading, setLoading] = useState(false);
    // Standing and first batch returned by "Enable": the panel is mounted with them and
    // asks the user to confirm the batch before it counts as usable.
    const [enrollment, setEnrollment] = useState(null);

    const handleEnable = () => {
        setLoading(true);
        enableTwoFactor(DEFAULT_METHOD).then(({response}) => {
            setLoading(false);
            setEnrollment(response);
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

    if (enrollment) {
        return (
            <RecoveryCodesPanel
                recoveryCodesRemaining={enrollment.recovery_codes_remaining}
                recoveryCodesTotal={enrollment.recovery_codes_total}
                recoveryCodesAcknowledged={enrollment.recovery_codes_acknowledged}
                recoveryCodesEnrollmentRequired={enrollment.recovery_codes_enrollment_required}
                recoveryCodesEnrollmentAvailable={false}
                recoveryCodesWindowOpen={false}
                hasPassword={hasPassword}
                lowCodeThreshold={enrollment.recovery_codes_low_threshold ?? recoveryCodesLowThreshold}
                email={email}
                appName={appName}
                initialBatch={{codes: enrollment.recovery_codes, batchId: enrollment.batch_id}}/>
        );
    }

    return (
        <RecoveryCodesPanel
            recoveryCodesRemaining={recoveryCodesRemaining}
            recoveryCodesTotal={recoveryCodesTotal}
            recoveryCodesAcknowledged={recoveryCodesAcknowledged}
            recoveryCodesEnrollmentRequired={recoveryCodesEnrollmentRequired}
            recoveryCodesEnrollmentAvailable={recoveryCodesEnrollmentAvailable}
            recoveryCodesWindowOpen={recoveryCodesWindowOpen}
            hasPassword={hasPassword}
            lowCodeThreshold={recoveryCodesLowThreshold}
            email={email}
            appName={appName}/>
    );
};

export default TwoFactorSection;
