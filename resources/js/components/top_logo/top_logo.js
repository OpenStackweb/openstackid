import React from "react";

import styles from "./top_logo.module.scss";

const TopLogo = ({appLogo}) => {
    return (
        <div className={styles.title_container}>
            <a href="/" target='_self'>
                <img className={styles.app_logo} alt="appLogo" src={appLogo}/>
            </a>
        </div>
    );
};

export default TopLogo;