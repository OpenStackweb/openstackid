import React from "react";
import Grid from "@material-ui/core/Grid";
import Paper from "@material-ui/core/Paper";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";

import styles from "./banner.module.scss";

const Banner = ({ infoBannerContent }) => {
  return (
    <Grid container className={styles.banner} component={Paper} elevation={0}>
      <Grid
        item
        xs={1}
        className={styles.prepend_grid_item}
        container
        justifyContent="center"
        alignItems="center"
      >
        <InfoOutlinedIcon />
      </Grid>
      <Grid item xs={11} className={styles.append_grid_item} container>
        <Grid item xs container direction="column" spacing={1}>
          <Grid item xs>
            <div dangerouslySetInnerHTML={{ __html: infoBannerContent }} />
          </Grid>
        </Grid>
      </Grid>
    </Grid>
  );
};

export default Banner;
