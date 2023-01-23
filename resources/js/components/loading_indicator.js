import React from 'react';
import Backdrop from '@material-ui/core/Backdrop';
import CircularProgress from '@material-ui/core/CircularProgress';

export default function LoadingIndicator({open}) {
    return (
        <div>
            <Backdrop
                style={{color: '#fff', zIndex: 1}}
                open={open}
            >
                <CircularProgress color="inherit"/>
            </Backdrop>
        </div>
    );
}