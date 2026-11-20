import React, {useState} from "react";
import Box from "@material-ui/core/Box";
import Button from "@material-ui/core/Button";
import Grid from "@material-ui/core/Grid";
import IconButton from "@material-ui/core/IconButton";
import Link from "@material-ui/core/Link";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import CloseIcon from "@material-ui/icons/Close";
import {
    regenerateRecoveryCodes,
    enrollRecoveryCodes,
    acknowledgeRecoveryCodes,
} from "../profile/actions";
import {handleErrorResponse} from "../utils";
import RecoveryCodeModal from "./recovery_code_modal";
import {
    RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY,
    DEFAULT_RECOVERY_CODES_LOW_THRESHOLD,
} from "../shared/recovery_codes";

import styles from "./recovery_codes.module.scss";

const PRECONDITION_FAILED = 412;

const ACK_ERROR_MESSAGE = "We could not confirm your recovery codes. Please try again.";

/**
 * Standing of the user's recovery codes, as the server reports it
 * (RecoveryCodesStatus::toArray() keys). The panel renders what the server
 * returns and never derives a count locally.
 */
const statusFromResponse = (response, previous) => ({
    remaining: response.recovery_codes_remaining ?? previous.remaining,
    total: response.recovery_codes_total ?? previous.total,
    acknowledged: response.recovery_codes_acknowledged ?? previous.acknowledged,
    enrollmentRequired: response.recovery_codes_enrollment_required ?? previous.enrollmentRequired,
});

const RecoveryCodesPanel = ({
                                 recoveryCodesRemaining,
                                 recoveryCodesTotal,
                                 recoveryCodesAcknowledged = true,
                                 recoveryCodesEnrollmentRequired = false,
                                 recoveryCodesEnrollmentAvailable = false,
                                 recoveryCodesWindowOpen = false,
                                 hasPassword = true,
                                 lowCodeThreshold = DEFAULT_RECOVERY_CODES_LOW_THRESHOLD,
                                 email,
                                 appName,
                                 initialBatch = null
                             }) => {
    const [regenerating, setRegenerating] = useState(false);
    const [currentPassword, setCurrentPassword] = useState("");
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState({
        remaining: recoveryCodesRemaining,
        total: recoveryCodesTotal,
        acknowledged: recoveryCodesAcknowledged,
        enrollmentRequired: recoveryCodesEnrollmentRequired,
    });
    // Enrollment is reachable only inside the window of a recent 2FA challenge; once
    // an enroll fails or a batch is replaced the panel falls back to "not set up yet".
    const [enrollmentAvailable, setEnrollmentAvailable] = useState(recoveryCodesEnrollmentAvailable);
    // The batch shown in the (non-dismissable) modal: {codes, batchId}.
    const [batch, setBatch] = useState(initialBatch);
    const [ackBusy, setAckBusy] = useState(false);
    const [ackError, setAckError] = useState(null);
    const [warningDismissed, setWarningDismissed] = useState(
        sessionStorage.getItem(RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY) === "1"
    );

    const showBatch = (response) => {
        setBatch({codes: response.recovery_codes, batchId: response.batch_id});
        setStatus((prev) => statusFromResponse(response, prev));
        setAckError(null);
    };

    const isValidBatch = (response) =>
        !!response
        && Array.isArray(response.recovery_codes)
        && response.recovery_codes.length > 0
        && Number.isInteger(response.batch_id);

    const handleRegenerate = () => {
        setLoading(true);
        // A user without a password sends none: the server accepts the request only
        // inside the enrollment window.
        regenerateRecoveryCodes(hasPassword ? currentPassword : null).then(({response}) => {
            setLoading(false);
            setRegenerating(false);
            setCurrentPassword("");
            showBatch(response);
        }).catch((err) => {
            setLoading(false);
            handleErrorResponse(err);
        });
    };

    // Explicit user action, never on page load: every call replaces the batch and
    // counts against the enroll rate limit.
    const handleSetup = () => {
        setLoading(true);
        enrollRecoveryCodes().then(({response}) => {
            setLoading(false);
            if (!isValidBatch(response)) {
                setEnrollmentAvailable(false);
                return;
            }
            showBatch(response);
        }).catch(() => {
            setLoading(false);
            setEnrollmentAvailable(false);
        });
    };

    // "I've Saved These Codes": the panel changes state only after the server
    // confirmed this exact batch.
    const handleAcknowledge = () => {
        if (!batch || ackBusy) return;
        setAckBusy(true);
        setAckError(null);
        acknowledgeRecoveryCodes(batch.batchId).then(({response}) => {
            setAckBusy(false);
            setBatch(null);
            setStatus((prev) => statusFromResponse(response, prev));
        }).catch((err) => {
            setAckBusy(false);
            if (err && err.status === PRECONDITION_FAILED) {
                // The batch was replaced (another tab, another sign-in): the codes on
                // screen no longer verify. Fall back to "not set up yet".
                setBatch(null);
                setEnrollmentAvailable(false);
                setStatus((prev) => ({...prev, acknowledged: false, enrollmentRequired: true}));
                return;
            }
            setAckError(ACK_ERROR_MESSAGE);
        });
    };

    const dismissLowCodeWarning = () => {
        sessionStorage.setItem(RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY, "1");
        setWarningDismissed(true);
    };

    const regenerateControls = (canRegenerate) => {
        if (!canRegenerate) return null;
        return (
            <>
                {
                    !regenerating &&
                    <Link href="#" onClick={(e) => {
                        e.preventDefault();
                        // Without a password there is nothing to type: regenerate directly.
                        if (!hasPassword) {
                            if (!loading) handleRegenerate();
                            return;
                        }
                        setRegenerating(true);
                    }}>
                        Regenerate Codes
                    </Link>
                }
                {
                    regenerating && hasPassword &&
                    <Box mt={1}>
                        <TextField
                            id="recovery_codes_current_password"
                            name="recovery_codes_current_password"
                            type="password"
                            variant="outlined"
                            size="small"
                            label="Current Password"
                            value={currentPassword}
                            onChange={(e) => setCurrentPassword(e.target.value)}
                            onKeyDown={(e) => {
                                // This panel lives inside the profile page's own <form>;
                                // it must not let Enter bubble up and submit that form too.
                                if (e.key === "Enter") {
                                    e.preventDefault();
                                    if (currentPassword && !loading) handleRegenerate();
                                }
                            }}
                            // Detaches this input from the ancestor <form> (the profile page
                            // wraps everything in one big form) so the browser's native
                            // "Enter submits the enclosing form" / password-manager-driven
                            // auto-submit can never fire a GET on it, regardless of the
                            // onKeyDown handler above.
                            inputProps={{form: "recovery-codes-detached-form", autoComplete: "off"}}
                            data-testid="recovery-codes-current-password"
                        />
                        &nbsp;
                        <Button
                            variant="contained"
                            color="primary"
                            onClick={handleRegenerate}
                            disabled={loading || !currentPassword}
                            data-testid="confirm-regenerate-button"
                        >
                            Confirm
                        </Button>
                        &nbsp;
                        <Link href="#" onClick={(e) => {
                            e.preventDefault();
                            setRegenerating(false);
                            setCurrentPassword("");
                        }}>
                            Cancel
                        </Link>
                    </Box>
                }
            </>
        );
    };

    const renderBody = () => {
        // No usable batch (none, exhausted or unacknowledged): the user is challenged at
        // every sign-in until they confirm one. Never show "10 of 10 remaining" for a
        // batch the user did not confirm.
        if (status.enrollmentRequired) {
            if (enrollmentAvailable) {
                return (
                    <>
                        <Typography variant="subtitle2" data-testid="recovery-codes-not-set-up">
                            Your recovery codes are not set up yet.
                        </Typography>
                        <Button
                            variant="contained"
                            color="primary"
                            onClick={handleSetup}
                            disabled={loading}
                            data-testid="setup-recovery-codes-button"
                        >
                            Set up recovery codes
                        </Button>
                    </>
                );
            }
            if (hasPassword) {
                return (
                    <>
                        <Typography variant="subtitle2" data-testid="recovery-codes-not-set-up">
                            Your recovery codes are not set up yet. Regenerate them to get a set you can save.
                        </Typography>
                        {regenerateControls(true)}
                    </>
                );
            }
            return (
                <Typography variant="subtitle2" data-testid="recovery-codes-not-set-up">
                    Your recovery codes are not set up yet. Sign out and sign in again to set them up.
                </Typography>
            );
        }

        const showLowCodeWarning = !warningDismissed && status.remaining < lowCodeThreshold;
        // A user without a password regenerates only inside the enrollment window.
        const canRegenerate = hasPassword || recoveryCodesWindowOpen;

        return (
            <>
                <Typography variant="subtitle2" data-testid="recovery-codes-count">
                    Recovery Codes: {status.remaining} of {status.total} remaining
                </Typography>
                {
                    showLowCodeWarning &&
                    <Box className={styles.low_code_warning} data-testid="low-code-warning">
                        <Typography variant="body2">
                            You're running low on recovery codes. Regenerate them to avoid getting locked out.
                        </Typography>
                        <IconButton size="small" onClick={dismissLowCodeWarning} aria-label="dismiss">
                            <CloseIcon fontSize="small"/>
                        </IconButton>
                    </Box>
                }
                {regenerateControls(canRegenerate)}
                {
                    !canRegenerate &&
                    <Typography variant="body2" data-testid="recovery-codes-trusted-devices-note">
                        To regenerate your recovery codes, revoke this device in Trusted Devices, sign out and
                        sign in again, then regenerate right after the sign-in.
                    </Typography>
                }
            </>
        );
    };

    return (
        <>
            <Grid item xs={12} className={styles.recovery_codes_panel}>
                {renderBody()}
            </Grid>
            <RecoveryCodeModal
                open={!!batch}
                codes={batch ? batch.codes : null}
                email={email}
                appName={appName}
                busy={ackBusy}
                error={ackError}
                onAcknowledge={handleAcknowledge}
                onRetry={handleAcknowledge}/>
        </>
    );
};

export default RecoveryCodesPanel;
