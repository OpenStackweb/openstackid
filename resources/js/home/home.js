import React from "react";
import ReactDOM from "react-dom";
import Button from "@material-ui/core/Button";
import Card from "@material-ui/core/Card";
import CardHeader from "@material-ui/core/CardHeader";
import CardContent from "@material-ui/core/CardContent";
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import Grid from "@material-ui/core/Grid";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";
import Typography from "@material-ui/core/Typography";
import { MuiThemeProvider, createMuiTheme } from "@material-ui/core/styles";
import Banner from "../components/banner/banner";

import styles from "./home.module.scss";

const HomePage = ({
  appName,
  appLogo,
  infoBannerContent,
  showInfoBanner,
  signInUrl,
  signUpUrl,
  tenantName,
}) => {
  return (
    <Container component="main" maxWidth="xs" className={styles.main_container}>
      <CssBaseline />
      {showInfoBanner && <Banner infoBannerContent={infoBannerContent} />}
      <div className={styles.title_container}>
        <a href={window.location.href}>
          <img className={styles.app_logo} alt="appLogo" src={appLogo} />
        </a>
        <h1>{appName} Identity Provider</h1>
      </div>
      <Card className={styles.home_container} variant="outlined">
        <CardHeader title={`Log in to ${tenantName}`} />
        <CardContent>
          <Grid
            container
            direction="column"
            spacing={2}
            justifyContent="center"
            alignItems="center"
          >
            <Grid item>
              <Button
                variant="outlined"
                size="large"
                href={signInUrl}
                className={styles.button}
              >
                Sign in to your account
              </Button>
            </Grid>
            <Grid item>
              <Button
                variant="outlined"
                size="large"
                href={signUpUrl}
                className={styles.button}
              >
                Register for an OpenInfraID
              </Button>
            </Grid>
            <Grid item className={styles.footer}>
              <InfoOutlinedIcon fontSize="small" />
              &nbsp;
              <Typography variant="body2">
                Once you're signed in, you can manage your trusted sites, change
                your settings and more.
              </Typography>
            </Grid>
          </Grid>
        </CardContent>
      </Card>
    </Container>
  );
};

// Or Create your Own theme:
const theme = createMuiTheme({
  palette: {
    primary: {
      main: "#3fa2f7",
    },
  },
  overrides: {
    MuiButton: {
      containedPrimary: {
        color: "white",
      },
    },
  },
});

ReactDOM.render(
  <MuiThemeProvider theme={theme}>
    <HomePage {...config} />
  </MuiThemeProvider>,
  document.querySelector("#root")
);
