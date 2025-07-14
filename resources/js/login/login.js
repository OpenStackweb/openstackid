import React from 'react';
import ReCAPTCHA from "react-google-recaptcha";
import ReactDOM from 'react-dom';
import Avatar from '@material-ui/core/Avatar';
import Button from '@material-ui/core/Button';
import CssBaseline from '@material-ui/core/CssBaseline';
import TextField from '@material-ui/core/TextField';
import Link from '@material-ui/core/Link';
import Typography from '@material-ui/core/Typography';
import Paper from '@material-ui/core/Paper';
import Container from '@material-ui/core/Container';
import Chip from '@material-ui/core/Chip';
import FormControlLabel from '@material-ui/core/FormControlLabel';
import Checkbox from '@material-ui/core/Checkbox';
import {verifyAccount, emitOTP, resendVerificationEmail} from './actions';
import {MuiThemeProvider, createTheme} from '@material-ui/core/styles';
import DividerWithText from '../components/divider_with_text';
import Visibility from '@material-ui/icons/Visibility';
import VisibilityOff from '@material-ui/icons/VisibilityOff';
import InputAdornment from '@material-ui/core/InputAdornment';
import IconButton from '@material-ui/core/IconButton';
import { emailValidator } from '../validator';
import Grid from '@material-ui/core/Grid';
import CustomSnackbar from "../components/custom_snackbar";
import Banner from '../components/banner/banner';
import OtpInput from 'react-otp-input';
import {handleErrorResponse, handleThirdPartyProvidersVerbiage} from '../utils';

import styles from './login.module.scss'
import "./third_party_identity_providers.scss";

const EmailInputForm = ({ value, onValidateEmail, onHandleUserNameChange, disableInput, emailError }) => {

    return (
        <Paper elevation={0} component="form"
            target="_self"
            className={styles.paper_root}
            onSubmit={onValidateEmail}>
            <TextField
                id="email"
                name="email"
                value={value}
                autoComplete="email"
                variant="outlined"
                margin="normal"
                required
                fullWidth
                disabled={disableInput}
                label="Email Address"
                autoFocus={true}
                onChange={onHandleUserNameChange}
                error={emailError != ""}
                helperText={emailError}
            />
            {emailError == "" &&
                <Button variant="contained"
                    color="primary"
                    title="Continue"
                    className={styles.apply_button}
                    disabled={disableInput}
                    onClick={onValidateEmail}>
                    &gt;
                </Button>
            }
        </Paper>
    );
}

const PasswordInputForm = ({
                               formAction,
                               onAuthenticate,
                               disableInput,
                               showPassword,
                               passwordValue,
                               passwordError,
                               onUserPasswordChange,
                               handleClickShowPassword,
                               handleMouseDownPassword,
                               userNameValue,
                               csrfToken,
                               shouldShowCaptcha,
                               captchaPublicKey,
                               onChangeRecaptcha,
                               handleEmitOtpAction,
                               forgotPasswordAction
                           }) => {
    return (
        <form method="post" action={formAction} onSubmit={onAuthenticate} target="_self">
            <TextField
                id="password"
                name="password"
                type={showPassword ? 'text' : 'password'}
                value={passwordValue}
                variant="outlined"
                margin="normal"
                required
                fullWidth
                autoFocus={true}
                label="Enter Your Password"
                autoComplete="current-password"
                onChange={onUserPasswordChange}
                InputProps={{
                    endAdornment: (
                        <InputAdornment position="end">
                            <IconButton
                                aria-label="toggle password visibility"
                                onClick={handleClickShowPassword}
                                onMouseDown={handleMouseDownPassword}
                                edge="end"
                            >
                                {showPassword ? <Visibility/> : <VisibilityOff/>}
                            </IconButton>
                        </InputAdornment>
                    )
                }}
            />
            {passwordError &&
                <p className={styles.error_label} dangerouslySetInnerHTML={{__html: passwordError}}></p>
            }
            <Grid container spacing={1}>
                <Grid item xs={12}>
                    <Button variant="contained"
                            disabled={disableInput}
                            type="submit"
                            color="primary">
                        Continue
                    </Button>
                </Grid>
                <Grid item xs={12}>
                    <FormControlLabel
                        disabled={disableInput}
                        control={<Checkbox value="remember" name="remember" id="remember" color="primary"/>}
                        label="Remember me"
                    />
                </Grid>
            </Grid>

            <input type="hidden" value={userNameValue} id="username" name="username"/>
            <input type="hidden" value={csrfToken} id="_token" name="_token"/>
            <input type="hidden" value="password" id="flow" name="flow"/>
            {shouldShowCaptcha() && captchaPublicKey &&
                <ReCAPTCHA
                    className={styles.recaptcha}
                    sitekey={captchaPublicKey}
                    onChange={onChangeRecaptcha}
                />
            }
            <ExistingAccountActions
                emitOtpAction={handleEmitOtpAction}
                userName={userNameValue}
                disableInput={disableInput}
                forgotPasswordAction={forgotPasswordAction}
            />
        </form>
    );
}

const OTPInputForm = ({
                          disableInput,
                          formAction,
                          onAuthenticate,
                          otpCode,
                          otpError,
                          otpLength,
                          onCodeChange,
                          userNameValue,
                          csrfToken,
                          shouldShowCaptcha,
                          captchaPublicKey,
                          onChangeRecaptcha,
                          onReset
                      }) => {
    return (
        <>
            <form method="post" action={formAction} onSubmit={onAuthenticate} target='_self'
                  className={styles.otp_form}>
                <div className={styles.subtitle}>Enter the single-use code sent to your email:</div>
                <div className={styles.code_input}>
                    <OtpInput
                        id="otp_code"
                        value={otpCode}
                        onChange={onCodeChange}
                        numInputs={otpLength}
                        renderInput={(props) => <input {...props} />}
                        shouldAutoFocus={true}
                        hasErrored={!otpError}
                        errorStyle={{border: '1px solid #e5424d'}}
                        data-testid="otp_code"
                    />
                </div>
                {otpError &&
                    <p className={styles.error_label} dangerouslySetInnerHTML={{__html: otpError}}></p>
                }
                <div>
                    <Button variant="contained"
                            disabled={disableInput}
                            color="primary"
                            type="submit"
                            target='_self'>
                        CONTINUE
                    </Button>
                </div>
                <div className={styles.footer_instructions}>
                    <p className={styles.otp_p}>
                        <Link href="#" onClick={onReset} variant="body2" target="_self">
                            Sign in using a different e-mail
                        </Link>
                    </p>
                    <div className={styles.after_login_instructions}>
                        <div>After you login you will be e-mailed a link to</div>
                        <div>set a password and complete your account.</div>
                    </div>
                </div>
                <input type="hidden" value={userNameValue} id="username" name="username"/>
                <input type="hidden" value={csrfToken} id="_token" name="_token"/>
                <input type="hidden" value="otp" id="flow" name="flow"/>
                <input type="hidden" value={otpCode} id="password" name="password"/>
                <input type="hidden" value="email" id="connection" name="connection"/>
                {shouldShowCaptcha() &&
                    <ReCAPTCHA
                        className={styles.recaptcha}
                        sitekey={captchaPublicKey}
                        onChange={onChangeRecaptcha}
                    />
                }
            </form>
        </>
    );
}

const HelpLinks = ({
                       userName,
                       showEmitOtpAction,
                       forgotPasswordAction,
                       showForgotPasswordAction,
                       showVerifyEmailAction,
                       verifyEmailAction,
                       showHelpAction,
                       helpAction,
                       appName,
                       emitOtpAction
                   }) => {
    if (userName) {
        forgotPasswordAction = `${forgotPasswordAction}?email=${encodeURIComponent(userName)}`;
    }

    return (
        <>
            <hr className={styles.separator}/>
            {
                showEmitOtpAction &&
                <Link href="#" onClick={emitOtpAction} variant="body2" target="_self">
                    Get A Single-use Code emailed to you
                </Link>
            }
            {
                showForgotPasswordAction &&
                <Link href={forgotPasswordAction} target="_self" variant="body2">
                    Reset your password
                </Link>
            }
            {
                showVerifyEmailAction &&
                <Link href={verifyEmailAction} target="_self" variant="body2">
                    Verify {appName}
                </Link>
            }
            {showHelpAction &&
                <Link href={helpAction} variant="body2" target="_self">
                    Having trouble?
                </Link>
            }
        </>
    );
}

const OTPHelpLinks = ({ emitOtpAction }) => {
    return (
        <>
            <hr className={styles.separator} />
            <p className={styles.otp_p}>Didn't receive it ?</p>
            <p className={styles.otp_p}>Check your spam folder or <Link href="#" onClick={emitOtpAction} variant="body2" target="_self">resend email.</Link>
            </p>
        </>
    );
}

const EmailErrorActions = ({ emitOtpAction, createAccountAction, onValidateEmail, disableInput }) => {
    return (
        <Grid container spacing={1}>
            <Grid container item spacing={1} justifyContent="center" alignItems="center">
                <Grid item>
                    <Button variant="contained"
                            onClick={emitOtpAction}
                            type="button"
                            className={styles.secondary_btn}
                            color="primary">
                        Email me a one time use code
                    </Button>
                </Grid>
                <Grid item>
                    <Button variant="contained"
                            href={createAccountAction}
                            type="button"
                            target='_self'
                            className={styles.secondary_btn}
                            color="primary">
                        Register and set a password
                    </Button>
                </Grid>
                <Grid item>
                    <Button variant="text"
                            onClick={onValidateEmail}
                            disabled={disableInput}
                            className={styles.secondary_btn}
                            color="primary">
                        Adjust email above and try again
                    </Button>
                </Grid>
            </Grid>
        </Grid>
    );
}

const ExistingAccountActions = ({emitOtpAction, forgotPasswordAction, userName, disableInput}) => {
    if (userName) {
        forgotPasswordAction = `${forgotPasswordAction}?email=${encodeURIComponent(userName)}`;
    }

    return (
        <Grid container spacing={1} style={{marginTop: "30px"}}>
            <Grid item xs={12}>
                <Button variant="contained"
                        onClick={emitOtpAction}
                        type="button"
                        disabled={disableInput}
                        className={styles.secondary_btn}
                        color="primary">
                    Sign in by emailing me a single-use code
                </Button>
            </Grid>
            <Grid item xs={12}>
                <Link
                    disabled={disableInput}
                    href={forgotPasswordAction} target="_self" variant="body2">
                    Reset your password
                </Link>
            </Grid>
        </Grid>
    );
}

const ThirdPartyIdentityProviders = ({ thirdPartyProviders, formAction, disableInput, allowNativeAuth }) => {
    return (
        <>
            {allowNativeAuth && <DividerWithText>or</DividerWithText>}
            {
                thirdPartyProviders.map((provider) => {
                    const verbiage = `${handleThirdPartyProvidersVerbiage(provider.name)} with ${provider.label}`;
                    return (
                        <Button
                            disabled={disableInput}
                            key={provider.name}
                            variant="contained"
                            className={styles.third_party_idp_button + ` ${provider.name}`}
                            color="primary"
                            target="_self"
                            title={verbiage}
                            href={`${formAction}/${provider.name}`}>
                            {verbiage}
                        </Button>
                    );
                })
            }
            <p>If you have a login, you may still choose to use a social login with <b>the same email address</b> to
                access your account.</p>
        </>
    );
}

const otp_flow = 'otp';
const password_flow = 'password';

class LoginPage extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            user_name: props.userName,
            user_password: '',
            otpCode: '',
            user_pic: props.hasOwnProperty('user_pic') ? props.user_pic : null,
            user_fullname: props.hasOwnProperty('user_fullname') ? props.user_fullname : null,
            user_verified: props.hasOwnProperty('user_verified') ? props.user_verified : false,
            user_active: props.hasOwnProperty('user_active') ? props.user_active : null,
            email_verified: props.hasOwnProperty('email_verified') ? props.email_verified : null,
            errors: {
                email: '',
                otp: props.authError != '' ? props.authError : '',
                password: props.authError != '' ? props.authError : '',
            },
            notification: {
                message: null,
                severity: 'info'
            },
            captcha_value: '',
            showPassword: false,
            disableInput: false,
            authFlow: props.flow,
            allowNativeAuth: props.allowNativeAuth,
            showInfoBanner: props.showInfoBanner,
            infoBannerContent: props.infoBannerContent,
        }

        if (props.authError != '' && !this.state.user_fullname) {
            this.state.user_fullname = props.userName;
        }

        if (this.state.errors.password && this.state.errors.password.includes("is not yet verified")) {
            this.state.errors.password = this.state.errors.password + `Or <a target='_self' href='${this.props.verifyEmailAction}?email=${encodeURIComponent(this.props.userName)}'>have another verification email sent to you.</a>`;
        }

        this.onHandleUserNameChange = this.onHandleUserNameChange.bind(this);
        this.onValidateEmail = this.onValidateEmail.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
        this.onAuthenticate = this.onAuthenticate.bind(this);
        this.onChangeRecaptcha = this.onChangeRecaptcha.bind(this);
        this.onUserPasswordChange = this.onUserPasswordChange.bind(this);
        this.onOTPCodeChange = this.onOTPCodeChange.bind(this);
        this.shouldShowCaptcha = this.shouldShowCaptcha.bind(this);
        this.handleClickShowPassword = this.handleClickShowPassword.bind(this);
        this.handleMouseDownPassword = this.handleMouseDownPassword.bind(this);
        this.handleEmitOtpAction = this.handleEmitOtpAction.bind(this);
        this.resendVerificationEmail = this.resendVerificationEmail.bind(this);
        this.handleSnackbarClose = this.handleSnackbarClose.bind(this);
        this.showAlert = this.showAlert.bind(this);
    }

    showAlert(message, severity) {
        this.setState({
            ...this.state,
            notification: {
                message: message,
                severity: severity
            }
        });
    }

    emitOtpAction() {
        let user_fullname = this.state.user_fullname ? this.state.user_fullname : this.state.user_name;

        emitOTP(this.state.user_name, this.props.token).then((payload) => {
            let {response} = payload;
            this.setState({
                ...this.state,
                authFlow: otp_flow,
                errors: {
                    email: '',
                    otp: '',
                    password: ''
                },
                user_verified: true,
                user_fullname: user_fullname,
            });
        }, (error) => {
            let {response, status, message} = error;
            if(status == 412){
                const {message, errors} = response.body;
                this.showAlert(errors[0], 'error');
                return;
            }
            this.showAlert('Oops... Something went wrong!', 'error');
        });
        return false;
    }

    handleEmitOtpAction(ev) {
        ev.preventDefault();
        return this.emitOtpAction();
    }

    shouldShowCaptcha() {
        return (
            this.props.hasOwnProperty('maxLoginAttempts2ShowCaptcha') &&
            this.props.hasOwnProperty('loginAttempts') &&
            this.props.loginAttempts >= this.props.maxLoginAttempts2ShowCaptcha
        )
    }

    onAuthenticate(ev) {
        if (this.state.authFlow === otp_flow) {
            if (this.state.otpCode == '') {
                this.setState({...this.state, disableInput: false, errors: {...this.state.errors, otp: 'Single-use code is empty'}});
                ev.preventDefault();
                return false;
            }
        } else if (this.state.user_password == '') {
            this.setState({...this.state, disableInput: false, errors: {...this.state.errors, password: 'Password is empty'}});
            ev.preventDefault();
            return false;
        }

        if (this.state.captcha_value == '' && this.shouldShowCaptcha()) {
            this.setState({...this.state, disableInput: false, errors: {...this.state.errors, password: 'you must check CAPTCHA'}});
            ev.preventDefault();
            return false;
        }
        this.setState({ ...this.state, disableInput: true });
        return true;
    }

    onChangeRecaptcha(value) {
        this.setState({ ...this.state, captcha_value: value });
    }

    onHandleUserNameChange(ev) {
        let { value, id } = ev.target;
        this.setState({ ...this.state, user_name: value });
    }

    onUserPasswordChange(ev) {
        let {errors} = this.state;
        let {value, id} = ev.target;
        if (value == "") // clean error
            errors[id] = '';
        this.setState({...this.state, user_password: value, errors: {...errors}});
    }

    onOTPCodeChange(value) {
        this.setState({...this.state, otpCode: value});
    }

    onValidateEmail(ev) {

        ev.preventDefault();
        let {user_name} = this.state;
        user_name = user_name?.trim();

        if (user_name == '') {
            return false;
        }
        if (!emailValidator(user_name)) {
            return false;
        }
        this.setState({ ...this.state, disableInput: true });

        verifyAccount(user_name, this.props.token).then((payload) => {
            let { response } = payload;

            let error = '';
            if (response.is_active === false) {
                error = 'Your user account is currently inactive. Please contact support for further assistance.';
            } else if (response.is_active === true && response.is_verified === false) {
                error = 'Your email has not been verified. Please check your inbox or resend the verification email.';
            }

            this.setState({
                ...this.state,
                user_pic: response.pic,
                user_fullname: response.full_name,
                user_verified: true,
                user_active: response.is_active,
                email_verified: response.is_verified,
                authFlow: response.has_password_set ? password_flow : otp_flow,
                errors: {
                    email: error,
                    otp: '',
                    password: ''
                },
                disableInput: false
            }, function () {
                //Once the state is updated, it's now possible to trigger emitOtpAction.
                //No need to wait for the component to update.
                if (!response.has_password_set) {
                    this.emitOtpAction();
                }
            });
        }, (error) => {

            let { response, status, message } = error;

            let newErrors = {};

            newErrors['password'] = '';
            newErrors['email'] = " ";

            if (status == 429) {
                newErrors['email'] = "Too many requests. Try it later.";
            }

            this.setState({
                ...this.state,
                user_pic: null,
                user_fullname: null,
                user_verified: false,
                errors: newErrors,
                disableInput: false
            });
        });
        return true;
    }

    resendVerificationEmail(ev) {
        ev.preventDefault();
        let {user_name} = this.state;
        user_name = user_name?.trim();

        if (!user_name) {
            this.showAlert(
                'Something went wrong while trying to resend the verification email. Please try again later.',
                'error');
            return;
        }

        resendVerificationEmail(user_name, this.props.token).then((payload) => {
            this.showAlert(
                'We\'ve sent you a verification email. Please check your inbox and click the link to verify your account.',
                'success');
        }, (error) => {
            handleErrorResponse(error, (title, messageLines, type) => {
                const message = (messageLines ?? []).join(', ')
                this.showAlert(`${title}: ${message}`, type);
            });
        });
    }

    handleDelete(ev) {
        ev.preventDefault();
        this.setState({
            ...this.state,
            user_name: null,
            user_pic: null,
            user_fullname: null,
            user_verified: false,
            user_active: null,
            email_verified: null,
            authFlow: "password",
            errors: {
                email: '',
                otp: '',
                password: ''
            }
        });
        return false;
    }

    handleClickShowPassword(ev) {
        ev.preventDefault();
        this.setState({ ...this.state, showPassword: !this.state.showPassword })
    }

    handleMouseDownPassword(ev) {
        ev.preventDefault();
    }

    existingUserCanContinue() {
        const { user_active, email_verified } = this.state;
        return user_active !== false && email_verified !== false;
    }

    getSignUpSignInTitle() {
      const { errors, user_active } = this.state;

      if (errors.email && this.existingUserCanContinue()) {
        return 'Create an account for:';
      }
      return 'Sign in';
    }

    handleSnackbarClose() {
        this.setState({
            ...this.state,
            notification: {
                message: null,
                severity: 'info'
            }
        });
    };

    render() {
        return (
            <Container component="main" maxWidth="xs" className={styles.main_container}>
                <CssBaseline />
                {this.state.showInfoBanner && <Banner infoBannerContent={this.state.infoBannerContent} />}
                <Container className={styles.login_container}>
                    <div className={styles.inner_container}>
                        <Typography component="h1" className={styles.app_logo_container}>
                            <a href={window.location.href} target='_self'><img className={styles.app_logo}
                                                                               alt={this.props.appName}
                                                                               src={this.props.appLogo}/></a>
                        </Typography>
                        <Typography component="h1" variant="h5">
                            {this.getSignUpSignInTitle()}
                            {this.state.user_fullname &&
                                <Chip
                                    avatar={<Avatar alt={this.state.user_name} src={this.state.user_pic}/>}
                                    variant="outlined"
                                    className={styles.valid_user_name_chip}
                                    label={this.state.user_name}
                                    onDelete={this.handleDelete}/>
                            }
                        </Typography>
                        {(!this.state.user_verified || !this.existingUserCanContinue()) &&
                            <>
                                {this.state.allowNativeAuth &&
                                    <EmailInputForm
                                        value={this.state.user_name ?? ''}
                                        onValidateEmail={this.onValidateEmail}
                                        onHandleUserNameChange={this.onHandleUserNameChange}
                                        disableInput={this.state.disableInput}
                                        emailError={this.state.errors.email}/>
                                }
                                {this.state.errors.email === '' &&
                                    this.props.thirdPartyProviders.length > 0 &&
                                    <ThirdPartyIdentityProviders
                                        thirdPartyProviders={this.props.thirdPartyProviders}
                                        formAction={this.props.formAction}
                                        disableInput={this.state.disableInput}
                                        allowNativeAuth={this.state.allowNativeAuth}
                                    />
                                }
                                {
                                    // we already had an interaction and got an user error...
                                    this.state.errors.email !== '' &&
                                    <>
                                        {this.existingUserCanContinue() &&
                                            <EmailErrorActions
                                                emitOtpAction={this.handleEmitOtpAction}
                                                onValidateEmail={this.onValidateEmail}
                                                disableInput={this.state.disableInput}
                                                createAccountAction={(this.state.user_name) ? `${this.props.createAccountAction}?email=${encodeURIComponent(this.state.user_name)}` : this.props.createAccountAction}
                                            />
                                        }
                                        {
                                            this.state.email_verified === false &&
                                                <Button variant="contained"
                                                    color="primary"
                                                    title="Resend verification email"
                                                    className={styles.apply_button}
                                                    onClick={this.resendVerificationEmail}>
                                                    Resend verification email
                                                </Button>
                                        }
                                        <HelpLinks
                                            userName={this.state.user_name}
                                            appName={this.props.appName}
                                            forgotPasswordAction={this.props.forgotPasswordAction}
                                            verifyEmailAction={this.props.verifyEmailAction}
                                            helpAction={this.props.helpAction}
                                            showEmitOtpAction={false}
                                            showForgotPasswordAction={false}
                                            showVerifyEmailAction={false}
                                            showHelpAction={true}
                                            emitOtpAction={this.handleEmitOtpAction}
                                        />
                                    </>
                                }
                            </>
                        }
                        {this.state.user_verified && this.existingUserCanContinue() && this.state.authFlow === password_flow &&
                            // proceed to ask for password ( 2nd step )
                            <>
                                <PasswordInputForm
                                    formAction={this.props.formAction}
                                    onAuthenticate={this.onAuthenticate}
                                    disableInput={this.state.disableInput}
                                    showPassword={this.state.showPassword}
                                    passwordValue={this.state.user_password}
                                    passwordError={this.state.errors.password}
                                    onUserPasswordChange={this.onUserPasswordChange}
                                    handleClickShowPassword={this.handleClickShowPassword}
                                    handleMouseDownPassword={this.handleMouseDownPassword}
                                    userNameValue={this.state.user_name}
                                    csrfToken={this.props.token}
                                    shouldShowCaptcha={this.shouldShowCaptcha}
                                    captchaPublicKey={this.props.captchaPublicKey}
                                    onChangeRecaptcha={this.onChangeRecaptcha}
                                    handleEmitOtpAction={this.handleEmitOtpAction}
                                    forgotPasswordAction={this.props.forgotPasswordAction}
                                />
                                <HelpLinks
                                    userName={this.state.user_name}
                                    appName={this.props.appName}
                                    forgotPasswordAction={this.props.forgotPasswordAction}
                                    verifyEmailAction={this.props.verifyEmailAction}
                                    helpAction={this.props.helpAction}
                                    emitOtpAction={this.handleEmitOtpAction}
                                    showEmitOtpAction={false}
                                    showForgotPasswordAction={false}
                                    showVerifyEmailAction={false}
                                    showHelpAction={true}
                                />
                            </>
                        }
                        {this.state.user_verified && this.existingUserCanContinue() && this.state.authFlow === otp_flow &&
                            // proceed to ask for password ( 2nd step )
                            <>
                                <OTPInputForm
                                    disableInput={this.state.disableInput}
                                    formAction={this.props.formAction}
                                    onAuthenticate={this.onAuthenticate}
                                    otpCode={this.state.otpCode}
                                    otpError={this.state.errors.otp}
                                    otpLength={this.props.otpLength}
                                    onCodeChange={this.onOTPCodeChange}
                                    userNameValue={this.state.user_name}
                                    csrfToken={this.props.token}
                                    shouldShowCaptcha={this.shouldShowCaptcha}
                                    captchaPublicKey={this.props.captchaPublicKey}
                                    onChangeRecaptcha={this.onChangeRecaptcha}
                                    onReset={this.handleDelete}
                                />
                                <OTPHelpLinks emitOtpAction={this.handleEmitOtpAction}/>
                            </>
                        }
                        <CustomSnackbar
                            message={this.state.notification.message}
                            severity={this.state.notification.severity}
                            onClose={this.handleSnackbarClose}
                        />
                    </div>
                </Container>
            </Container>
        );
    }
}

// Or Create your Own theme:
const theme = createTheme({
    palette: {
        primary: {
            main: '#3fa2f7'
        },
    },
    overrides: {
        MuiButton: {
            containedPrimary: {
                color: 'white',
                textTransform: 'none'
            }
        }
    }
});

ReactDOM.render(
    <MuiThemeProvider theme={theme}>
        <LoginPage {...config} />
    </MuiThemeProvider>,
    document.querySelector('#root')
);
