import React, {useEffect, useState} from "react";
import Bloodhound from 'bloodhound-js';
import Autocomplete from '@material-ui/lab/Autocomplete';
import {
    CircularProgress,
    TextField,
    Tooltip
} from '@material-ui/core';

const ConditionalTooltip = ({children, title}) => {
    if (!title) return children;
    return (<Tooltip title={title}>
        {children}
    </Tooltip>);
}

const UsersSelector = ({fetchUsersURL, id, name, onChange, initialValue, disabled, tooltip}) => {
    const [open, setOpen] = useState(false);
    const [options, setOptions] = useState([]);
    const [typeAheadEngine, setTypeAheadEngine] = useState(null);
    const [term, setTerm] = useState(null);
    const [value, setValue] = useState(initialValue ?? []);
    const loading = open && options.length === 0;

    const getTypeAheadEngine = () => {
        return new Bloodhound({
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: fetchUsersURL,
                wildcard: '%QUERY%',
                prepare: (query, settings) => {
                    settings.url = fetchUsersURL + '?filter=first_name=@' + query + ',last_name=@' + query + ',email=@' + query;
                    return settings;
                },
                transform: (input) => input.data
            }
        });
    }

    useEffect(() => {
        setTypeAheadEngine(getTypeAheadEngine());
    }, []);

    useEffect(() => {
        if (term) {
            //typeAheadEngine.clear();
            const promise = typeAheadEngine.initialize();

            promise.then(() => {
                typeAheadEngine.search(
                    term,
                    function (d) {
                    },
                    function (d) {
                        setOptions(d.map((u) => {
                            return {full_name: `${u.first_name} ${u.last_name}`, email: u.email, id: u.id};
                        }));
                    }
                );
            });
        }
    }, [term]);

    const searchUsers = (term) => {
        setTerm(term);
    }

    return (
        <ConditionalTooltip title={tooltip}>
            <Autocomplete
                id={id}
                name={name}
                size="small"
                disabled={disabled ?? false}
                multiple
                value={value}
                open={open}
                onClose={() => {
                    setOpen(false);
                }}
                freeSolo
                getOptionSelected={(option, value) => option.email === value.email}
                getOptionLabel={(option) => `${option.full_name} (${option.email})`}
                options={options}
                loading={loading}
                onChange={(event, newValue) => {
                    setValue([...newValue]);
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
                }}
                onInputChange={(event, newInputValue) => {
                    setOpen(true);
                    searchUsers(newInputValue);
                }}
                renderInput={(params) => (
                    <TextField
                        {...params}
                        variant="outlined"
                        InputProps={{
                            ...params.InputProps,
                            endAdornment: <>{loading ? <CircularProgress color="inherit" size={20}/> : null}</>,
                        }}
                    />
                )}
            />
        </ConditionalTooltip>
    );
}

export default UsersSelector;