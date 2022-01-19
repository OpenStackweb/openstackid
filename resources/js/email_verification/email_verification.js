import React from 'react'
import ReactDOM from 'react-dom'
import ReCAPTCHA from 'react-google-recaptcha'
import Button from '@material-ui/core/Button'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'
import CardContent from '@material-ui/core/CardContent'
import Container from '@material-ui/core/Container'
import CssBaseline from '@material-ui/core/CssBaseline'
import Grid from '@material-ui/core/Grid'
import TextField from '@material-ui/core/TextField'
import { MuiThemeProvider, createMuiTheme } from '@material-ui/core/styles'
import { useFormik } from 'formik'
import * as yup from 'yup'
import Banner from '../components/banner/banner'

import styles from './email_verification.module.scss'

const validationSchema = yup.object({
  email: yup
    .string('Enter your email')
    .email('Enter a valid email')
    .required('Email is required'),
})

const EmailVerificationPage = ({
  appLogo,
  captchaPublicKey,
  infoBannerContent,
  showInfoBanner,
}) => {
  const formik = useFormik({
    initialValues: {
      email: '',
    },
    validationSchema: validationSchema,
    onSubmit: (values) => {
      console.log(signUpAction)
      console.log(JSON.stringify(values, null, 2))
    },
  })

  const onChangeRecaptcha = () => {}

  return (
    <Container component="main" maxWidth="xs" className={styles.main_container}>
      <CssBaseline />
      {showInfoBanner && <Banner infoBannerContent={infoBannerContent} />}
      <div className={styles.title_container}>
        <a href={window.location.href}>
          <img className={styles.app_logo} alt="appLogo" src={appLogo} />
        </a>
      </div>
      <form onSubmit={formik.handleSubmit}>
        <Card
          className={styles.email_verification_container}
          variant="outlined"
        >
          <CardHeader title="Email Verification" />
          <CardContent>
            <Grid
              container
              direction="column"
              spacing={2}
              justifyContent="center"
            >
              <Grid item>
                <TextField
                  id="email"
                  name="email"
                  autoComplete="email"
                  variant="outlined"
                  fullWidth
                  size="small"
                  label="Email Address"
                  value={formik.values.email}
                  onChange={formik.handleChange}
                  error={formik.touched.email && Boolean(formik.errors.email)}
                  helperText={formik.touched.email && formik.errors.email}
                />
              </Grid>
              <Grid item container alignItems="center" justifyContent="center">
                <Grid item>
                  <ReCAPTCHA
                    className={styles.recaptcha}
                    sitekey={captchaPublicKey}
                    onChange={onChangeRecaptcha}
                  />
                </Grid>
                <Grid item>
                  <Button
                    variant="contained"
                    size="large"
                    className={styles.button}
                    disableElevation
                    fullWidth
                    type="submit"
                  >
                    Resend Verification Email
                  </Button>
                </Grid>
              </Grid>
            </Grid>
          </CardContent>
        </Card>
      </form>
    </Container>
  )
}

// Or Create your Own theme:
const theme = createMuiTheme({
  palette: {
    primary: {
      main: '#3fa2f7',
    },
  },
  overrides: {
    MuiButton: {
      containedPrimary: {
        color: 'white',
      },
    },
  },
})

ReactDOM.render(
  <MuiThemeProvider theme={theme}>
    <EmailVerificationPage {...config} />
  </MuiThemeProvider>,
  document.querySelector('#root'),
)
