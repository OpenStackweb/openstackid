import React, {useState} from 'react';
import Chip from '@material-ui/core/Chip';
import Autocomplete from '@material-ui/lab/Autocomplete';
import TextField from '@material-ui/core/TextField';
import debounce from "lodash.debounce";
import {fetchGroups} from "../actions";

const GroupsInput = ({url, defaultValues, ...others}) => {
    let handleChangeDebounce;
    const [groups, setGroups] = useState([]);

    const handleChange = (query) => {
        if (handleChangeDebounce) handleChangeDebounce.cancel()
        handleChangeDebounce = debounce(async () => {
            const res = await fetchGroups(url, query);
            setGroups([...defaultValues, ...res, ...groups].filter((v, i, a) => a.findIndex(v2 => (v2.id === v.id)) === i));
        }, 500);
        handleChangeDebounce();
    }

    return (
        <Autocomplete
            {...others}
            multiple
            id="groups"
            onKeyDown={(e) => handleChange(e.target.value)}
            name="groups"
            size="small"
            options={groups}
            defaultValue={defaultValues}
            getOptionLabel={(option) => option.name}
            getOptionSelected={(option, value) => option.id === value.id}
            renderTags={(value, getTagProps) =>
                value.map((option, index) => (
                    <Chip
                        variant="outlined"
                        label={option.name}
                        size="small"
                        {...getTagProps({index})}
                    />
                ))
            }
            renderInput={(params) => (
                <TextField {...params} variant="outlined" label="Groups" placeholder="Groups"/>
            )}
        />
    )
}

export default GroupsInput;
