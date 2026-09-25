import React, {useEffect, useState} from "react";
import Box from "@material-ui/core/Box";
import Button from "@material-ui/core/Button";
import Chip from "@material-ui/core/Chip";
import Table from "@material-ui/core/Table";
import TableBody from "@material-ui/core/TableBody";
import TableCell from "@material-ui/core/TableCell";
import TableHead from "@material-ui/core/TableHead";
import TableRow from "@material-ui/core/TableRow";
import Typography from "@material-ui/core/Typography";
import moment from "moment";
import Swal from "sweetalert2";
import {getTrustedDevices, revokeAllTrustedDevices, revokeTrustedDevice} from "../profile/actions";
import {handleErrorResponse} from "../utils";

const formatEpoch = (value) => value ? moment.utc(value * 1000).format("DD/MM/YYYY hh:mm A") : "";

const TrustedDevicesSection = () => {
    const [devices, setDevices] = useState([]);
    const [loaded, setLoaded] = useState(false);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        getTrustedDevices().then(({response}) => {
            setDevices(response?.data ?? []);
            setLoaded(true);
        }).catch((err) => {
            setLoaded(true);
            handleErrorResponse(err);
        });
    }, []);

    // Buttons are plain onClick handlers: the whole profile page is one <form>,
    // so anything of type="submit" here would submit the profile instead.
    const handleRevoke = (id) => {
        setBusy(true);
        revokeTrustedDevice(id).then(() => {
            setBusy(false);
            setDevices((current) => current.filter((device) => device.id !== id));
        }).catch((err) => {
            setBusy(false);
            handleErrorResponse(err);
        });
    };

    const handleRevokeAll = () => {
        Swal({
            title: 'Revoke all trusted devices?',
            text: 'Every device will be asked for a verification code on its next login. Your current session stays active.',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, revoke all'
        }).then((result) => {
            if (!result.value) return;
            setBusy(true);
            revokeAllTrustedDevices().then(() => {
                setBusy(false);
                setDevices([]);
            }).catch((err) => {
                setBusy(false);
                handleErrorResponse(err);
            });
        });
    };

    if (!loaded) return null;

    return (
        <Box data-testid="trusted-devices-section">
            {devices.length === 0 ? (
                <Typography variant="body2" data-testid="trusted-devices-empty">
                    You have no trusted devices. Devices you mark as trusted when completing a
                    two-factor challenge will appear here.
                </Typography>
            ) : (
                <>
                    <Table size="small" data-testid="trusted-devices-table">
                        <TableHead>
                            <TableRow>
                                <TableCell>Device</TableCell>
                                <TableCell>IP Address</TableCell>
                                <TableCell>Trusted</TableCell>
                                <TableCell>Last Seen</TableCell>
                                <TableCell>Expires</TableCell>
                                <TableCell/>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {devices.map((device) => (
                                <TableRow key={device.id} data-testid={`trusted-device-row-${device.id}`}>
                                    <TableCell>
                                        {device.device_name}
                                        {device.is_current && (
                                            <Chip size="small" label="This device" style={{marginLeft: 8}}
                                                  data-testid="trusted-device-current"/>
                                        )}
                                    </TableCell>
                                    <TableCell>{device.ip_address}</TableCell>
                                    <TableCell>{formatEpoch(device.trusted_at)}</TableCell>
                                    <TableCell>{formatEpoch(device.last_seen_at)}</TableCell>
                                    <TableCell>{formatEpoch(device.expires_at)}</TableCell>
                                    <TableCell align="right">
                                        <Button
                                            size="small"
                                            color="secondary"
                                            onClick={() => handleRevoke(device.id)}
                                            disabled={busy}
                                            data-testid={`revoke-trusted-device-${device.id}`}
                                        >
                                            Revoke
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <Box mt={2}>
                        <Button
                            variant="outlined"
                            color="secondary"
                            onClick={handleRevokeAll}
                            disabled={busy}
                            data-testid="revoke-all-trusted-devices"
                        >
                            Revoke all devices
                        </Button>
                        <Typography variant="caption" display="block" style={{marginTop: 8}}>
                            Revoking a device only removes its two-factor bypass: your current session
                            stays active, and the next login from that device will ask for a code.
                        </Typography>
                    </Box>
                </>
            )}
        </Box>
    );
};

export default TrustedDevicesSection;
