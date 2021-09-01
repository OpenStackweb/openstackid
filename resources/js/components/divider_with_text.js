import React from "react";
import { makeStyles } from "@material-ui/core";

const useStyles = makeStyles(theme => ({
    container: {
        display: "flex",
        alignItems: "center"
    },
    border: {
        borderBottom: "1px solid #3fa2f7",
        width: "100%"
    },
    content: {
        paddingTop: theme.spacing(0.5),
        paddingBottom: theme.spacing(0.5),
        paddingRight: theme.spacing(2),
        paddingLeft: theme.spacing(2),
        fontWeight: 500,
        fontSize: 16,
        color: "#3fa2f7"
    }
}));

const DividerWithText = ({ children }) => {
    const classes = useStyles();
    return (
        <div className={classes.container}>
            <div className={classes.border} />
            <span className={classes.content}>{children}</span>
            <div className={classes.border} />
        </div>
    );
};
export default DividerWithText;