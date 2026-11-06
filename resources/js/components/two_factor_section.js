import React, {useState} from "react";
import Box from "@material-ui/core/Box";
import Button from "@material-ui/core/Button";
import Dialog from "@material-ui/core/Dialog";
import DialogActions from "@material-ui/core/DialogActions";
import DialogContent from "@material-ui/core/DialogContent";
import DialogTitle from "@material-ui/core/DialogTitle";
import FormControlLabel from "@material-ui/core/FormControlLabel";
import Switch from "@material-ui/core/Switch";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import {enableTwoFactor, disableTwoFactor} from "../profile/actions";
import {handleErrorResponse} from "../utils";
import RecoveryCodesPanel from "./recovery_codes_panel";

const DEFAULT_METHOD = "email_otp";

export const ENFORCED_MESSAGE =
    "Two-factor authentication is required for members of an administrator group and cannot be turned off.";

const TwoFactorSection = ({
                              twoFactorEnabled,
                              twoFactorEnforced = false,
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
    const [enabled, setEnabled] = useState(twoFactorEnabled || twoFactorEnforced);
    const [loading, setLoading] = useState(false);
    // Standing and first batch returned by "Enable": the panel is mounted with them and
    // asks the user to confirm the batch before it counts as usable.
    const [enrollment, setEnrollment] = useState(null);

    const [confirmingDisable, setConfirmingDisable] = useState(false);
    const [currentPassword, setCurrentPassword] = useState("");
    // Shown inside the dialog: a SweetAlert opens below a MUI Dialog (z-index 1060 vs 1300),
    // so the usual error popup would be hidden behind it.
    const [disableError, setDisableError] = useState(null);

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

    const closeDisableDialog = () => {
        setConfirmingDisable(false);
        setCurrentPassword("");
        setDisableError(null);
    };

    // A user without a password sends none: the server accepts the request only inside
    // the recovery enrollment window.
    const handleDisable = () => {
        setLoading(true);
        setDisableError(null);
        disableTwoFactor(hasPassword ? currentPassword : null).then(() => {
            setLoading(false);
            closeDisableDialog();
            setEnrollment(null);
            setEnabled(false);
        }).catch((err) => {
            setLoading(false);
            // the dialog stays open so a mistyped password can be retried
            handleErrorResponse(err, (title, message) => {
                setDisableError(Array.isArray(message) ? message.join(" ") : (message || title));
            });
        });
    };

    const handleToggle = (e) => {
        if (twoFactorEnforced || loading) return;
        if (e.target.checked) {
            handleEnable();
        } else {
            setConfirmingDisable(true);
        }
    };

    const toggle = (
        <Box mb={1}>
            <FormControlLabel
                control={
                    <Switch
                        color="primary"
                        checked={enabled}
                        disabled={loading || twoFactorEnforced}
                        onChange={handleToggle}
                        inputProps={{"data-testid": "two-factor-switch"}}
                    />
                }
                label="Two-Factor Authentication (Email OTP)"
            />
            {
                twoFactorEnforced &&
                <Typography variant="body2" data-testid="two-factor-enforced-message">
                    {ENFORCED_MESSAGE}
                </Typography>
            }
        </Box>
    );

    // Rendered in a portal and driven by onClick only: the profile page wraps everything
    // in one <form>, so this dialog must never submit it.
    const disableDialog = (
        <Dialog open={confirmingDisable} onClose={closeDisableDialog} data-testid="disable-two-factor-dialog">
            <DialogTitle>Turn off two-factor authentication?</DialogTitle>
            <DialogContent>
                <Typography variant="body2" gutterBottom>
                    Your recovery codes will be deleted and every trusted device will be removed.
                    {hasPassword ? " Enter your current password to confirm." : ""}
                </Typography>
                {
                    hasPassword &&
                    <TextField
                        id="disable_two_factor_current_password"
                        name="disable_two_factor_current_password"
                        type="password"
                        variant="outlined"
                        size="small"
                        fullWidth
                        autoFocus
                        label="Current Password"
                        value={currentPassword}
                        onChange={(e) => {
                            setCurrentPassword(e.target.value);
                            setDisableError(null);
                        }}
                        onKeyDown={(e) => {
                            if (e.key === "Enter") {
                                e.preventDefault();
                                if (currentPassword && !loading) handleDisable();
                            }
                        }}
                        inputProps={{form: "disable-two-factor-detached-form", autoComplete: "off"}}
                        data-testid="disable-two-factor-current-password"
                    />
                }
                {
                    disableError &&
                    <Typography variant="body2" color="error" role="alert" data-testid="disable-two-factor-error">
                        {disableError}
                    </Typography>
                }
            </DialogContent>
            <DialogActions>
                <Button onClick={closeDisableDialog} disabled={loading} data-testid="cancel-disable-two-factor-button">
                    Cancel
                </Button>
                <Button
                    variant="contained"
                    color="primary"
                    onClick={handleDisable}
                    disabled={loading || (hasPassword && !currentPassword)}
                    data-testid="confirm-disable-two-factor-button"
                >
                    Turn off
                </Button>
            </DialogActions>
        </Dialog>
    );

    if (!enabled) {
        return (
            <>
                {toggle}
                <Typography variant="body2" data-testid="two-factor-disabled-message">
                    Two-factor authentication is not enabled for your account.
                </Typography>
            </>
        );
    }

    if (enrollment) {
        return (
            <>
            {toggle}
            {disableDialog}
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
            </>
        );
    }

    return (
        <>
        {toggle}
        {disableDialog}
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
        </>
    );
};

export default TwoFactorSection;
