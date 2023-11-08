import React, {useEffect, useState} from 'react';
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
import moment from "moment";
import {addPublicKey, removePublicKey, getPublicKeys} from '../actions';

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

const PublicKeysAdmin = ({
                             initialValues,
                             clientId,
                             supportedSigningAlgorithms,
                             supportedJSONWebKeyTypes
                         }) => {
    const [open, setOpen] = useState(false);
    const [publicKeys, setPublicKeys] = useState([]);
    const [dateRangeValue, setDateRangeChange] = useState([new Date(), new Date()]);

    useEffect(() => {
        reloadPublicKeys();
    }, []);

    const reloadPublicKeys = () => {
        getPublicKeys(clientId, 1, 100).then((res) => {
            setPublicKeys([...res.data]);
        });
    }

    const handleClickOpen = () => {
        setOpen(true);
    };

    const handleClose = () => {
        setOpen(false);
    };

    const handleDateRangeChange = (dateRange) => {
        setDateRangeChange(dateRange);
    };

    const handlePKDelete = (id) => {
        Swal({
            title: 'Are you sure to delete this public key?',
            text: 'This is an non reversible process!',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.value) {
                removePublicKey(clientId, id).then(() => {
                    Swal("Public key deleted", "The public key has been deleted successfully", "success");
                    reloadPublicKeys();
                }).catch((err) => {
                    handleErrorResponse(err);
                });
            }
        });
    };

    const PublicKeyItem = ({publicKey, onDelete}) => (
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
                    {publicKey.sha_256}
                </Grid>
            </Grid>
            <Grid item xs={1}>
                <IconButton onClick={() => {
                    if (onDelete) onDelete(publicKey.id);
                }}>
                    <DeleteIcon fontSize="small"/>
                </IconButton>
            </Grid>
        </Grid>
    )

    const buildValidationSchema = () => {
        return object({
            kid: string("The Key Identifier field is required.").required(
                "The Key Identifier field is required."
            ),
            type: string("The Key Type field is required.").required(
                "The Key Type field is required."
            ),
            pem_content: string("The Key (PEM content) field is required.").required(
                "The Key (PEM content) field is required."
            ),
        });
    };

    const formik = useFormik({
        initialValues: initialValues,
        validationSchema: buildValidationSchema(),
        onSubmit: (values) => {
            values.valid_from = dateRangeValue[0];
            values.valid_to = dateRangeValue[1];

            if (values.valid_from) values.valid_from = moment(values.valid_from).format('MM/DD/YYYY');
            if (values.valid_to) values.valid_to = moment(values.valid_to).format('MM/DD/YYYY');

            console.log('public_key_saved', values)

            addPublicKey(clientId, values).then(() => {
                setOpen(false);
                Swal("Public key added", "The public key has been added successfully", "success");
            }).catch((err) => {
                //console.log(err);
                setOpen(false);
                handleErrorResponse(err);
            });
        },
    });

    return (
        <div>
            <Paper variant="outlined" className={styles.paper}>
                <Grid item container direction="row">
                    <Grid item xs={10} container alignItems="center">
                        <Typography display="inline">Public keys</Typography>
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
                        {publicKeys.map((publicKey) => (
                            <PublicKeyItem key={publicKey.id} publicKey={publicKey} onDelete={handlePKDelete}/>
                        ))}
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
                    <MuiDialogContent dividers className={styles.main_container}>
                        <FormGroup>
                            <SimpleTextFormControl
                                id="kid"
                                title="Key Identifier *"
                                tooltip=""
                                value={formik.values.kid}
                                touched={formik.touched.kid}
                                errors={formik.errors.kid}
                                onChange={formik.handleChange}
                            />
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="key_validity_range">
                                    <Typography variant="subtitle2">Key validity range</Typography>
                                </FormLabel>
                                <DateRangePicker
                                    id="key_validity_range"
                                    name="key_validity_range"
                                    onChange={handleDateRangeChange}
                                    value={dateRangeValue}/>
                            </FormControl>
                            <CheckboxFormControl
                                id="active"
                                title="Is Active?"
                                tooltip=""
                                value={formik.values.active}
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
                                id="alg"
                                title="Algorithm"
                                tooltip="Identifies the algorithm intended for use with the key."
                                value={formik.values.alg}
                                touched={formik.touched.alg}
                                errors={formik.errors.alg}
                                onChange={formik.handleChange}
                                options={supportedSigningAlgorithms.map((alg) => {
                                    return {value: alg, text: alg};
                                })}
                            />
                            <SelectFormControl
                                id="type"
                                title="Type"
                                tooltip=""
                                value={formik.values.type}
                                touched={formik.touched.type}
                                errors={formik.errors.type}
                                onChange={formik.handleChange}
                                options={supportedJSONWebKeyTypes.map((type) => {
                                    return {value: type, text: type};
                                })}
                            />
                            <FormControl variant="outlined" className={styles.form_control}>
                                <FormLabel htmlFor="pem_content">
                                    <Typography variant="subtitle2">Key *</Typography>
                                </FormLabel>
                                <TextField
                                    id="pem_content"
                                    name="pem_content"
                                    variant="outlined"
                                    fullWidth
                                    multiline
                                    minRows={7}
                                    maxRows={7}
                                    size="small"
                                    autoFocus={true}
                                    value={formik.values.pem_content}
                                    onChange={formik.handleChange}
                                    error={
                                        formik.touched.pem_content &&
                                        Boolean(formik.errors.pem_content)
                                    }
                                    helperText={
                                        formik.touched.pem_content && formik.errors.pem_content
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