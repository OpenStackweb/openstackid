import React, {useCallback} from 'react';
import debounce from 'lodash.debounce';
import SimpleMDE from "react-simplemde-editor";
import "easymde/dist/easymde.min.css";

const RichTextEditor = ({value, onChange}) => {
    let handleChangeDebounce;

    const handleChange = useCallback((value) => {
        if (handleChangeDebounce) handleChangeDebounce.cancel()
        handleChangeDebounce = debounce(() => {
            onChange(value);
        }, 300);
        handleChangeDebounce();
    }, []);

    return (
        <SimpleMDE
            value={value}
            onChange={handleChange}
        />
    )
}
export default RichTextEditor;
