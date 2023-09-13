import React, {useState} from 'react';
import {withStyles} from '@material-ui/core/styles';
import MuiDialogTitle from '@material-ui/core/DialogTitle';
import MuiDialogContent from '@material-ui/core/DialogContent';
import MuiDialogActions from '@material-ui/core/DialogActions';
import CloseIcon from '@material-ui/icons/Close';
import DeleteIcon from "@material-ui/icons/Delete";
import FiberManualRecordIcon from '@material-ui/icons/FiberManualRecord';
import InfoOutlinedIcon from '@material-ui/icons/InfoOutlined';
import NotInterestedIcon from '@material-ui/icons/NotInterested';
import VpnKeyIcon from '@material-ui/icons/VpnKey';
import Swal from 'sweetalert2';
import {handleErrorResponse} from '../../../../utils';
import {CheckboxFormControl, SelectFormControl, SimpleTextFormControl} from './form_controls';
import {useFormik} from 'formik';
import {object, ref, string} from 'yup';
import Alert from '@material-ui/lab/Alert';
import DateRangePicker from '@wojtekmaj/react-daterange-picker'
import '../../../../../styles/date_range_picker.scss';
import 'react-calendar/dist/Calendar.css';

import {
    Box,
    Button,
    Chip,
    Dialog, Divider,
    FormControl,
    FormGroup,
    FormLabel,
    Grid,
    IconButton, Paper,
    TextField,
    Typography
} from '@material-ui/core';

import styles from './common.module.scss';

const classes = (theme) => ({
    root: {
        margin: 0,
        padding: theme.spacing(2),
    },
    closeButton: {
        position: 'absolute',
        right: theme.spacing(1),
        top: theme.spacing(1),
        color: theme.palette.grey[500],
    },
});

const DialogTitle = withStyles(classes)((props) => {
    const {children, classes, onClose, ...other} = props;
    return (
        <MuiDialogTitle disableTypography className={classes.root} {...other}>
            <Typography variant="h6">{children}</Typography>
            {onClose ? (
                <IconButton aria-label="close" className={classes.closeButton} onClick={onClose}>
                    <CloseIcon/>
                </IconButton>
            ) : null}
        </MuiDialogTitle>
    );
});

const PublicKeysAdmin = ({supportedSigningAlgorithms, publicKeys, onSave}) => {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const [dateRangeValue, onDateRangeChange] = useState([new Date(), new Date()]);

    const handleClickOpen = () => {
        setOpen(true);
    };

    const handleClose = () => {
        setOpen(false);
    };

    const PublicKeyItem = ({publicKey}) => (
        <Grid
            item
            container
            direction="row"
            spacing={2}>
            <Grid item container xs={1} justifyContent="center" alignItems="center">
                {
                    publicKey.active ? <FiberManualRecordIcon color="primary"/> : <NotInterestedIcon/>
                }
                <VpnKeyIcon/>
            </Grid>
            <Grid item xs={10}>
                <Grid item xs={12}>
                    {publicKey.kid}&nbsp;
                    <Chip label={publicKey.usage}/>&nbsp;
                    <Chip label={publicKey.type} color="primary"/>&nbsp;
                </Grid>
                <Grid item xs={12}>
                    {publicKey.sha_256_thumbprint}
                </Grid>
            </Grid>
            <Grid item xs={1}>
                <IconButton onClick={() => {
                }}>
                    <DeleteIcon fontSize="small"/>
                </IconButton>
            </Grid>
        </Grid>
    )

    const buildValidationSchema = () => {
        return object({
            app_name: string("The app name field is required.").required(
                "The app name field is required."
            ),
        });
    };

    const formik = useFormik({
        initialValues: initialValues,
        validationSchema: buildValidationSchema(),
        onSubmit: (values) => {
            setLoading(true);
            onSave(values).then(() => {
                setLoading(false);
                setOpen(false);
                Swal("Public key added", "The public key has been added successfully", "success");
            }).catch((err) => {
                //console.log(err);
                setLoading(false);
                setOpen(false);
                handleErrorResponse(err);
            });
        },
    });

    return (
        <div>
            <Paper variant="outlined">
                <Grid item container direction="row">
                    <Grid item xs={10}>
                        <Typography display="inline">Public keys</Typography>
                        <InfoOutlinedIcon fontSize="small"/>
                    </Grid>
                    <Grid item xs={2}>
                        <Button variant="outlined" color="primary" onClick={handleClickOpen}>
                            Add Public Key
                        </Button>
                    </Grid>
                </Grid>
            </Paper>
            <Box component="div" whiteSpace="nowrap" height="20px"/>
            <Typography>This is a list of Public Keys keys associated with your application. Remove any keys that
                you do not recognize.</Typography>
            <Divider/>
            <Box component="div" whiteSpace="nowrap" height="20px"/>
            {
                publicKeys?.length > 0 ?
                    <Grid container spacing={1}>
                        {publicKeys.map((publicKey) => (<PublicKeyItem key={publicKey.id} publicKey={publicKey}/>))}
                    </Grid>
                    :
                    <Alert severity="warning">There are no Public keys yet.</Alert>
            }
            <Dialog onClose={handleClose} aria-labelledby="customized-dialog-title" open={open}
                    className={styles.dialog}>
                <form
                    onSubmit={formik.handleSubmit}
                    method="post"
                    encType="multipart/form-data"
                    target="_self"
                >
                    <DialogTitle id="customized-dialog-title" onClose={handleClose}>
                        Add Public Key
                    </DialogTitle>
                    <MuiDialogContent dividers>
                        <FormGroup>
                            <SimpleTextFormControl
                                id="key_identifier"
                                title="Key Identifier"
                                tooltip=""
                                value={formik.values.key_identifier}
                                touched={formik.touched.key_identifier}
                                errors={formik.errors.key_identifier}
                                onChange={formik.handleChange}
                            />
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="key_validity_range">
                                    <Typography variant="subtitle2">Key validity range</Typography>
                                </FormLabel>
                                <DateRangePicker
                                    id="key_validity_range"
                                    name="key_validity_range"
                                    onChange={onDateRangeChange}
                                    value={dateRangeValue}/>
                            </FormControl>
                            <CheckboxFormControl
                                id="key_active"
                                title="Is Active?"
                                tooltip=""
                                value={formik.values.key_active}
                                onChange={formik.handleChange}
                            />
                            <SelectFormControl
                                id="usage"
                                title="Usage"
                                tooltip=""
                                value={formik.values.usage}
                                touched={formik.touched.usage}
                                errors={formik.errors.usage}
                                onChange={formik.handleChange}
                                options={[
                                    {value: 'sig', text: 'sig'},
                                    {value: 'enc', text: 'enc'},
                                ]}
                            />
                            <SelectFormControl
                                id="algorithm"
                                title="Algorithm"
                                tooltip="Identifies the algorithm intended for use with the key."
                                value={formik.values.algorithm}
                                touched={formik.touched.algorithm}
                                errors={formik.errors.algorithm}
                                onChange={formik.handleChange}
                                options={supportedSigningAlgorithms.map((alg) => {
                                    return {value: alg, text: alg};
                                })}
                            />
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="key">
                                    <Typography variant="subtitle2">Key</Typography>
                                </FormLabel>
                                <TextField
                                    id="key"
                                    name="key"
                                    variant="outlined"
                                    fullWidth
                                    multiline
                                    minRows={8}
                                    maxRows={8}
                                    size="small"
                                    autoFocus={true}
                                    value={formik.values.key}
                                    onChange={formik.handleChange}
                                    error={
                                        formik.touched.key &&
                                        Boolean(formik.errors.key)
                                    }
                                    helperText={
                                        formik.touched.key && formik.errors.key
                                    }
                                />
                            </FormControl>
                        </FormGroup>
                    </MuiDialogContent>
                    <MuiDialogActions>
                        <Button autoFocus onClick={handleClose} color="primary">
                            Close
                        </Button>
                        <Button
                            variant="contained"
                            disableElevation
                            type="submit"
                        >
                            Save changes
                        </Button>
                    </MuiDialogActions>
                </form>
            </Dialog>
        </div>
    );
}

export default PublicKeysAdmin;