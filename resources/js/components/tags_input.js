import React, {useEffect, useState} from "react";
import PropTypes from "prop-types";
import Chip from "@material-ui/core/Chip";
import {makeStyles} from "@material-ui/core/styles";
import TextField from "@material-ui/core/TextField";
import Downshift from "downshift";

const useStyles = makeStyles(theme => ({
    chip: {
        margin: theme.spacing(0.5, 0.25)
    }
}));

export const getTags = (value) => Array.isArray(value) ? value : value?.split(',');

const TagsInput = ({...props}) => {
    const classes = useStyles();
    const {id, name, selectedTags, isValid, placeholder, onChange, tags, type, ...other} = props;
    const [inputValue, setInputValue] = useState("");
    const [selectedItem, setSelectedItem] = useState([]);

    useEffect(() => {
        setSelectedItem(tags);
    }, [tags]);

    useEffect(() => {
        if (selectedTags) selectedTags(selectedItem);
    }, [selectedItem, selectedTags]);

    function isValidHttpUrl(string) {
        try {
            const newUrl = new URL(string);
            return newUrl.protocol === 'http:' || newUrl.protocol === 'https:';
        } catch (err) {
            return false;
        }
    }

    function isValidEmail(email) {
        return String(email)
            .toLowerCase()
            .match(
                /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
            );
    }

    function notifyChange(newValue) {
        const ev = {
            persist: () => {
            },
            target: {
                type: "change",
                id: id,
                name: name,
                value: [...newValue]
            }
        };
        onChange(ev);
    }

    function handleKeyDown(event) {
        if (event.key === "Enter") {
            const value = event.target.value.trim();
            const newSelectedItem = [...selectedItem];
            const duplicatedValues = newSelectedItem.indexOf(value);

            if (duplicatedValues !== -1) {
                setInputValue("");
                return;
            }
            if (!value.replace(/\s/g, "").length) return;

            if ((isValid && !isValid(value)) ||
                (type === "url" && !isValidHttpUrl(value)) ||
                (type === "email" && !isValidEmail(value))) {
                setInputValue("");
                return;
            }

            newSelectedItem.push(value);
            setSelectedItem(newSelectedItem);
            setInputValue("");

            notifyChange(newSelectedItem);
        }
        if (
            selectedItem.length &&
            !inputValue.length &&
            event.key === "Backspace"
        ) {
            setSelectedItem(selectedItem.slice(0, selectedItem.length - 1));
        }
    }

    function handleChange(item) {
        let newSelectedItem = [...selectedItem];
        if (newSelectedItem.indexOf(item) === -1) {
            newSelectedItem = [...newSelectedItem, item];
        }
        setInputValue("");
        setSelectedItem(newSelectedItem);
    }

    const handleDelete = item => () => {
        const newSelectedItem = [...selectedItem];
        newSelectedItem.splice(newSelectedItem.indexOf(item), 1);
        setSelectedItem(newSelectedItem);
        notifyChange(newSelectedItem);
    };

    function handleInputChange(event) {
        setInputValue(event.target.value);
    }

    return (
        <Downshift
            id="downshift-multiple"
            inputValue={inputValue}
            onChange={handleChange}
            selectedItem={selectedItem}
        >
            {({getInputProps}) => {
                const {onBlur, onChange, onFocus, ...inputProps} = getInputProps({
                    onKeyDown: handleKeyDown,
                    placeholder
                });
                return (
                    <div>
                        <TextField
                            InputProps={{
                                startAdornment: selectedItem.map(item => {
                                    if (!item) return null;
                                    return <Chip
                                        key={item}
                                        tabIndex={-1}
                                        label={item}
                                        className={classes.chip}
                                        size="small"
                                        onDelete={handleDelete(item)}
                                    />
                                }),
                                onBlur,
                                onChange: event => {
                                    handleInputChange(event);
                                    onChange(event);
                                },
                                onFocus
                            }}
                            {...other}
                            {...inputProps}
                        />
                    </div>
                );
            }}
        </Downshift>
    );
}

TagsInput.defaultProps = {
    tags: []
};

TagsInput.propTypes = {
    tags: PropTypes.arrayOf(PropTypes.string)
};

export default TagsInput;