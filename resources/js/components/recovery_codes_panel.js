import React, {useState} from "react";
import Box from "@material-ui/core/Box";
import Button from "@material-ui/core/Button";
import Grid from "@material-ui/core/Grid";
import IconButton from "@material-ui/core/IconButton";
import Link from "@material-ui/core/Link";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import CloseIcon from "@material-ui/icons/Close";
import {regenerateRecoveryCodes} from "../profile/actions";
import {handleErrorResponse} from "../utils";
import RecoveryCodeModal from "./recovery_code_modal";
import {
    RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY,
    DEFAULT_RECOVERY_CODES_LOW_THRESHOLD,
} from "../shared/recovery_codes";

import styles from "./recovery_codes.module.scss";

const RecoveryCodesPanel = ({
                                 recoveryCodesRemaining,
                                 recoveryCodesTotal,
                                 lowCodeThreshold = DEFAULT_RECOVERY_CODES_LOW_THRESHOLD,
                                 email,
                                 appName,
                                 initialCodes = null
                             }) => {
    const [regenerating, setRegenerating] = useState(false);
    const [currentPassword, setCurrentPassword] = useState("");
    const [loading, setLoading] = useState(false);
    const [remaining, setRemaining] = useState(recoveryCodesRemaining);
    const [total, setTotal] = useState(recoveryCodesTotal);
    const [codes, setCodes] = useState(initialCodes);
    const [warningDismissed, setWarningDismissed] = useState(
        sessionStorage.getItem(RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY) === "1"
    );

    const handleRegenerate = () => {
        setLoading(true);
        regenerateRecoveryCodes(currentPassword).then(({response}) => {
            setLoading(false);
            setRegenerating(false);
            setCurrentPassword("");
            setCodes(response.recovery_codes);
            setRemaining(response.recovery_codes.length);
            setTotal(response.recovery_codes.length);
        }).catch((err) => {
            setLoading(false);
            handleErrorResponse(err);
        });
    };

    const handleAcknowledge = () => {
        setCodes(null);
    };

    const dismissLowCodeWarning = () => {
        sessionStorage.setItem(RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY, "1");
        setWarningDismissed(true);
    };

    const showLowCodeWarning = !warningDismissed && remaining < lowCodeThreshold;

    return (
        <>
            <Grid item xs={12} className={styles.recovery_codes_panel}>
                <Typography variant="subtitle2" data-testid="recovery-codes-count">
                    Recovery Codes: {remaining} of {total} remaining
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
                {
                    !regenerating &&
                    <Link href="#" onClick={(e) => {
                        e.preventDefault();
                        setRegenerating(true);
                    }}>
                        Regenerate Codes
                    </Link>
                }
                {
                    regenerating &&
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
            </Grid>
            <RecoveryCodeModal open={!!codes} codes={codes} email={email} appName={appName} onAcknowledge={handleAcknowledge}/>
        </>
    );
};

export default RecoveryCodesPanel;
