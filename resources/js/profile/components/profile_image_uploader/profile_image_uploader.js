import React, {useState} from "react";
import Avatar from "@material-ui/core/Avatar";
import {makeStyles} from "@material-ui/core";

import styles from "./profile_image_uploader.module.scss";

const useStyles = makeStyles((theme) => ({
    root: {
        display: 'flex',
        '& > *': {
            margin: theme.spacing(1),
        },
    },
    large: {
        width: theme.spacing(7),
        height: theme.spacing(7),
    },
}));

const ImgUpload = ({onChange, src}) => {
    const classes = useStyles();
    return (
        <label htmlFor="pic" className={styles.custom_file_upload}>
            <Avatar alt="" src={src} className={classes.large}/>
            <input id="pic" name="pic" type="file" accept=".jpg,.jpeg,.png" onChange={onChange}/>
        </label>
    );
}

const ProfileImageUploader = ({userPicURL, onFileSelected}) => {

    const [file, setFile] = useState('');
    const [imagePreviewUrl, setImagePreviewUrl] = useState(userPicURL);

    const photoUpload = e => {
        e.preventDefault();
        const reader = new FileReader();
        const file = e.target.files[0];
        reader.onloadend = () => {
            setFile(file);
            setImagePreviewUrl(reader.result);
        }
        reader.readAsDataURL(file);
        if (onFileSelected) onFileSelected(file);
    }

    return (
        <ImgUpload onChange={photoUpload} src={imagePreviewUrl}/>
    );
}

export default ProfileImageUploader;