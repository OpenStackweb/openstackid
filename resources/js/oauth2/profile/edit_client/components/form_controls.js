import React from "react";
import {makeStyles, withStyles} from '@material-ui/core/styles';
import {
    Checkbox,
    FormControl,
    FormControlLabel,
    FormLabel,
    InputBase,
    Select,
    TextField,
    Tooltip,
    Typography
} from "@material-ui/core";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";

import styles from "./common.module.scss";

const BootstrapInput = withStyles((theme) => ({
    root: {
        'label + &': {
            marginTop: theme.spacing(0.5),
        },
    },
    input: {
        borderRadius: 4,
        position: 'relative',
        backgroundColor: theme.palette.background.paper,
        border: '1px solid #ced4da',
        fontSize: 16,
        minWidth: 400,
        padding: '10px 26px 10px 12px',
        '&:focus': {
            borderRadius: 4,
        },
    },
}))(InputBase);

export const SimpleTextFormControl = ({id, title, tooltip, type, value, touched, errors, onChange}) => (
    <FormControl variant="outlined" className={styles.form_control}>
        <FormLabel htmlFor={id}>
            <Typography variant="subtitle2" display="inline">{title}</Typography>
            {tooltip && <Tooltip title={tooltip}>
                <InfoOutlinedIcon fontSize="small"/>
            </Tooltip>}
        </FormLabel>
        <TextField
            id={id}
            name={id}
            variant="outlined"
            fullWidth
            size="small"
            inputProps={{maxLength: 100}}
            autoFocus={true}
            value={value}
            onChange={onChange}
            type={type}
            error={
                touched &&
                Boolean(errors)
            }
            helperText={touched && errors}
        />
    </FormControl>
);

export const SelectFormControl = ({id, title, tooltip, value, touched, errors, onChange, options}) => (
    <FormControl variant="outlined" className={styles.form_control}>
        <FormLabel htmlFor={id}>
            <Typography variant="subtitle2" display="inline">{title}</Typography>
            {tooltip && <Tooltip title={tooltip}>
                <InfoOutlinedIcon fontSize="small"/>
            </Tooltip>}
        </FormLabel>
        <Select
            id={id}
            name={id}
            native
            size="small"
            value={value}
            displayEmpty
            onChange={onChange}
            input={<BootstrapInput/>}
            error={
                touched &&
                Boolean(errors)
            }
        >
            {options.map(({value, text}) => (<option key={value} value={value}>{text}</option>))}
        </Select>
        {touched && errors && (
            <div className={styles.error_label}>
                {errors}
            </div>
        )
        }
    </FormControl>
);

export const CheckboxFormControl = ({id, title, tooltip, value, onChange}) => (
    <FormControl variant="outlined" className={styles.form_control}>
        <FormControlLabel
            id={id}
            control={<Checkbox
                color="primary"
                id={id}
                checked={value}
                onChange={onChange}
            />}
            label={<>
                <Typography display="inline">{title}</Typography>
                {tooltip && <Tooltip title={tooltip}>
                    <InfoOutlinedIcon fontSize="small"/>
                </Tooltip>}
            </>}
            labelPlacement="end"
        />
    </FormControl>
);

