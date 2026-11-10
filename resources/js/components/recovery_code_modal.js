import React, {useEffect, useState} from "react";
import Box from "@material-ui/core/Box";
import Button from "@material-ui/core/Button";
import Dialog from "@material-ui/core/Dialog";
import DialogActions from "@material-ui/core/DialogActions";
import DialogContent from "@material-ui/core/DialogContent";
import DialogTitle from "@material-ui/core/DialogTitle";
import Typography from "@material-ui/core/Typography";
import WarningRoundedIcon from "@material-ui/icons/WarningRounded";
import RecoveryCodeDisplay from "./recovery_code_display";

import styles from "./recovery_codes.module.scss";

const ACK_DELAY_SECONDS = 5;

const RecoveryCodeModal = ({open, codes, email, appName, onAcknowledge, error = null, busy = false, onRetry}) => {
    const [secondsLeft, setSecondsLeft] = useState(ACK_DELAY_SECONDS);
    // Identity of the displayed batch. A different batch shown while the modal stays open
    // (the login page re-enrolls after a 412) must get its own full delay: keyed by the
    // codes' content, so a parent re-rendering with an equal array does not restart it.
    const batchKey = codes ? codes.join("|") : "";

    useEffect(() => {
        if (!open) return undefined;

        setSecondsLeft(ACK_DELAY_SECONDS);
        const interval = setInterval(() => {
            setSecondsLeft((prev) => (prev > 0 ? prev - 1 : 0));
        }, 1000);

        return () => clearInterval(interval);
    }, [open, batchKey]);

    return (
        <Dialog
            open={open}
            disableEscapeKeyDown
            disableBackdropClick
            maxWidth="sm"
            fullWidth
            data-testid="recovery-code-modal"
        >
            <DialogTitle>Save Your Recovery Codes</DialogTitle>
            <DialogContent>
                <Box className={styles.warning_banner} data-testid="recovery-code-warning">
                    <WarningRoundedIcon fontSize="small" className={styles.warning_icon}/>
                    <Typography variant="body2">
                        These codes will not be shown again. Copy or download them now and store them somewhere safe.
                    </Typography>
                </Box>
                {codes && <RecoveryCodeDisplay codes={codes} email={email} appName={appName}/>}
                {error && (
                    <Typography variant="body2" color="error" role="alert" data-testid="recovery-code-modal-error">
                        {error}
                    </Typography>
                )}
            </DialogContent>
            <DialogActions>
                {error && onRetry && (
                    <Button
                        variant="outlined"
                        color="primary"
                        disabled={busy}
                        onClick={onRetry}
                        data-testid="retry-acknowledge-button"
                    >
                        Retry
                    </Button>
                )}
                <Button
                    variant="contained"
                    color="primary"
                    disabled={secondsLeft > 0 || busy}
                    onClick={onAcknowledge}
                    data-testid="acknowledge-codes-button"
                >
                    {secondsLeft > 0 ? `I've Saved These Codes (${secondsLeft})` : "I've Saved These Codes"}
                </Button>
            </DialogActions>
        </Dialog>
    );
};

export default RecoveryCodeModal;
