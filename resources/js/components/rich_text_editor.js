import React, {useCallback} from 'react';
import debounce from 'lodash.debounce';
import SimpleMDE from "react-simplemde-editor";
import "easymde/dist/easymde.min.css";


const RichTextEditor = ({rteRef, rteProps, value, onChange}) => {
    let handleChangeDebounce;

    const handleChange = useCallback((value) => {
        if (handleChangeDebounce) handleChangeDebounce.cancel()
        handleChangeDebounce = debounce(() => {
            onChange(value);
        }, 300);
        handleChangeDebounce();
    }, []);

    return (
        // <MUIRichTextEditor
        //     controls={[
        //         'bold',
        //         'italic',
        //         'underline',
        //         'bulletList',
        //         'numberList',
        //         'undo',
        //         'redo',
        //         'clear'
        //     ]}
        //     value={value}
        //     {...rteProps}
        //     ref={rteRef}
        //     onChange={handleChange}
        // />
        <SimpleMDE
            value={value}
            onChange={handleChange}
        />
    )
}
export default RichTextEditor;
