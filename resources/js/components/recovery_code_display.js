import React, {useState} from "react";
import Box from "@material-ui/core/Box";
import Button from "@material-ui/core/Button";
import Grid from "@material-ui/core/Grid";
import Typography from "@material-ui/core/Typography";
import AssignmentIcon from "@material-ui/icons/Assignment";
import CheckCircleIcon from "@material-ui/icons/CheckCircle";
import {downloadTextFile} from "../utils";

import styles from "./recovery_codes.module.scss";

const DEFAULT_APP_NAME = "OpenStackID";

const buildFileContent = (codes, email, appName) => {
    const date = new Date().toISOString().slice(0, 10);
    return [
        `${appName} Recovery Codes`,
        `Generated: ${date}`,
        `Account: ${email}`,
        "",
        "Keep these codes somewhere safe. Each code can only be used once to sign in, and they will not be shown again.",
        "",
        ...codes,
    ].join("\n");
};

const RecoveryCodeDisplay = ({codes, email, appName = DEFAULT_APP_NAME}) => {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        navigator.clipboard.writeText(codes.join("\n")).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    const handleDownload = () => {
        const date = new Date().toISOString().slice(0, 10);
        const appSlug = appName.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
        downloadTextFile(`${appSlug}-recovery-codes-${date}.txt`, buildFileContent(codes, email, appName));
    };

    return (
        <Box data-testid="recovery-code-display">
            <Grid container spacing={1} className={styles.codes_grid} data-testid="recovery-codes-grid">
                {codes.map((code, idx) => (
                    <Grid item xs={6} key={idx}>
                        <Typography component="span" className={styles.code}>{code}</Typography>
                    </Grid>
                ))}
            </Grid>
            <Box mt={1}>
                <Button
                    variant="outlined"
                    color="primary"
                    startIcon={copied ? <CheckCircleIcon/> : <AssignmentIcon/>}
                    onClick={handleCopy}
                    data-testid="copy-codes-button"
                >
                    {copied ? "Copied" : "Copy to Clipboard"}
                </Button>
                &nbsp;
                <Button
                    variant="outlined"
                    color="primary"
                    onClick={handleDownload}
                    data-testid="download-codes-button"
                >
                    Download as Text File
                </Button>
            </Box>
        </Box>
    );
};

export default RecoveryCodeDisplay;
