import React, {useEffect, useState} from "react";
import PropTypes from "prop-types";
import Chip from "@material-ui/core/Chip";
import {makeStyles} from "@material-ui/core/styles";
import TextField from "@material-ui/core/TextField";
import Autocomplete from "@material-ui/lab/Autocomplete";

const useStyles = makeStyles(theme => ({
    chip: {
        margin: theme.spacing(0.5, 0.25)
    }
}));

export const getTags = (value) => Array.isArray(value) ? value : value?.split(',');

const TagsInput = ({...props}) => {
    const classes = useStyles();
    const {id, name, selectedTags, isValid, placeholder, onChange, tags, type, ...other} = props;
    const [selectedItems, setSelectedItems] = useState(tags ?? []);

    useEffect(() => {
        setSelectedItems(tags);
    }, [tags]);

    useEffect(() => {
        if (selectedTags) selectedTags(selectedItems);
    }, [selectedItems, selectedTags]);

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
        const inputValue = event.target.value.trim();

        if (event.key === "Enter") {
            const newSelectedItems = [...selectedItems];
            const duplicatedValues = newSelectedItems.indexOf(inputValue);

            if (duplicatedValues !== -1) {
                return;
            }
            if (!inputValue.replace(/\s/g, "").length) return;

            if ((isValid && !isValid(inputValue)) ||
                (type === "url" && !isValidHttpUrl(inputValue)) ||
                (type === "email" && !isValidEmail(inputValue))) {
                return;
            }
            newSelectedItems.push(inputValue);
            setSelectedItems(newSelectedItems);
            notifyChange(newSelectedItems);
        } else if (event.key === "Backspace" && selectedItems.length > 0 && !inputValue.length) {
            const newSelectedItems = selectedItems.slice(0, selectedItems.length - 1);
            setSelectedItems(newSelectedItems);
            notifyChange(newSelectedItems);
        }
    }

    const handleDelete = item => () => {
        const newSelectedItems = [...selectedItems];
        newSelectedItems.splice(newSelectedItems.indexOf(item), 1);
        setSelectedItems(newSelectedItems);
        notifyChange(newSelectedItems);
    };

    return (
        <Autocomplete
            id={id}
            name={name}
            size="small"
            multiple
            disableClearable={true}
            value={selectedItems}
            freeSolo
            options={[]}
            renderTags={(value, getTagProps) =>
                value.map(item => {
                    if (!item) return null;
                    return <Chip
                        key={item}
                        tabIndex={-1}
                        label={item}
                        className={classes.chip}
                        size="small"
                        onDelete={handleDelete(item)}
                    />
                })
            }
            renderInput={(params) => (
                <TextField
                    {...other}
                    {...params}
                    placeholder={placeholder}
                    variant="outlined"
                    onKeyDown={handleKeyDown}
                />
            )}
        />
    );
}

TagsInput.defaultProps = {
    tags: []
};

TagsInput.propTypes = {
    tags: PropTypes.arrayOf(PropTypes.string)
};

export default TagsInput;