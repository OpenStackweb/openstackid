import React from 'react';
import MUIRichTextEditor from 'mui-rte';
import {stateToHTML} from 'draft-js-export-html';
import debounce from 'lodash.debounce';

const RichTextEditor = ({rteRef, rteProps, value, onChange}) => {
    let handleChangeDebounce;

    const handleChange = (state) => {
        if (handleChangeDebounce) handleChangeDebounce.cancel()
        handleChangeDebounce = debounce(() => {
            const html = stateToHTML(state.getCurrentContent());
            onChange(html);
        }, 300);
        handleChangeDebounce();
    }

    return (
        <MUIRichTextEditor
            controls={[
                'bold',
                'italic',
                'underline',
                'bulletList',
                'numberList',
                'undo',
                'redo',
                'clear'
            ]}
            value={value}
            {...rteProps}
            ref={rteRef}
            onChange={handleChange}
        />
    )
}
export default RichTextEditor;
