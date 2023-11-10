import React, {useEffect, useState} from "react";
import Bloodhound from 'bloodhound-js';
import TextField from '@material-ui/core/TextField';
import Autocomplete from '@material-ui/lab/Autocomplete';
import CircularProgress from '@material-ui/core/CircularProgress';

const UsersSelector = ({fetchUsersURL, id, name, onChange, initialValue, disabled}) => {
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
                            return {full_name: `${u.first_name} ${u.last_name}`, id: u.id};
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
            getOptionSelected={(option, value) => option.full_name === value.fullName}
            getOptionLabel={(option) => option.full_name}
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
    );
}

export default UsersSelector;