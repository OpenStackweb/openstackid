import styles from './login.module.scss'
import "./third_party_identity_providers.scss";
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
import {verifyAccount, emitOTP} from './actions';
import {MuiThemeProvider, createMuiTheme} from '@material-ui/core/styles';
import DividerWithText from '../components/divider_with_text';
import Visibility from '@material-ui/icons/Visibility';
import VisibilityOff from '@material-ui/icons/VisibilityOff';
import InputAdornment from '@material-ui/core/InputAdornment';
import IconButton from '@material-ui/core/IconButton';
import {emailValidator} from '../validator';
import Grid from '@material-ui/core/Grid';
import Swal from 'sweetalert2'

const EmailInputForm = ({onValidateEmail, onHandleUserNameChange, disableInput, emailError}) => {

    return (
        <Paper elevation={0} component="form"
               target="_self"
               className={styles.paper_root}
               onSubmit={onValidateEmail}>
            <TextField
                id="email"
                name="email"
                autoComplete="email"
                variant="outlined"
                margin="normal"
                required
                fullWidth
                disabled={disableInput}
                label="Email Address"
                autoFocus
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
                               handleEmitOtpAction
                           }) => {
    return(
        <form method="post" action={formAction} onSubmit={onAuthenticate} target="_self">
            <TextField
                id="password"
                name="password"
                disabled={disableInput}
                type={showPassword ? 'text' : 'password'}
                value={passwordValue}
                variant="outlined"
                margin="normal"
                required
                fullWidth
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
                <p  className={styles.error_label} dangerouslySetInnerHTML={{__html: passwordError}}></p>
            }
            <FormControlLabel
                disabled={disableInput}
                control={<Checkbox value="remember" name="remember" id="remember" color="primary"/>}
                label="Remember me"
            />
            <input type="hidden" value={userNameValue} id="username" name="username"/>
            <input type="hidden" value={csrfToken} id="_token" name="_token"/>
            <input type="hidden" value="password" id="flow" name="flow"/>
            {shouldShowCaptcha() &&
            <ReCAPTCHA
                className={styles.recaptcha}
                sitekey={captchaPublicKey}
                onChange={onChangeRecaptcha}
            />
            }
            <ExistingAccountActions
                emitOtpAction={handleEmitOtpAction}
                onAuthenticate={onAuthenticate}
                disableInput={disableInput}
            />
        </form>
    );
}

const OTPInputForm = ({
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
                                onChangeRecaptcha
                            }) => {
    return(
        <form method="post" action={formAction} onSubmit={onAuthenticate}>
            <TextField
                id="password"
                name="password"
                disabled={disableInput}
                type={showPassword ? 'text' : 'password'}
                value={passwordValue}
                variant="outlined"
                margin="normal"
                required
                fullWidth
                label="Enter Your verification code"
                autoComplete="new-password"
                error={passwordError != ""}
                helperText={passwordError}
                onChange={onUserPasswordChange}
                InputProps={{
                    endAdornment: (
                        <InputAdornment position="end">
                            <IconButton
                                aria-label="toggle code visibility"
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
            <p>A Verification Code was just sent to your Email.</p>
            <FormControlLabel
                disabled={disableInput}
                control={<Checkbox value="remember" name="remember" id="remember" color="primary"/>}
                label="Remember me"
            />
            <input type="hidden" value={userNameValue} id="username" name="username"/>
            <input type="hidden" value={csrfToken} id="_token" name="_token"/>
            <input type="hidden" value="otp" id="flow" name="flow"/>
            <input type="hidden" value="email" id="connection" name="connection"/>
            {shouldShowCaptcha() &&
            <ReCAPTCHA
                className={styles.recaptcha}
                sitekey={captchaPublicKey}
                onChange={onChangeRecaptcha}
            />
            }
            <Button variant="contained"
                    disabled={disableInput}
                    className={styles.continue_btn}
                    color="primary"
                    type="submit"
                    onClick={onAuthenticate}>
                Verify
            </Button>
        </form>
    );
}

const HelpLinks = ({
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
    return (
        <>
            <hr className={styles.separator}/>
            {
                showEmitOtpAction &&
                <Link href="#" onClick={emitOtpAction} variant="body2" target="_self">
                    Get A Login Code emailed to you
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
            {   showHelpAction &&
                <Link href={helpAction} variant="body2" target="_self">
                    Having trouble?
                </Link>
            }
        </>
    );
}

const OTPHelpLinks = ({emitOtpAction}) => {
    return (
        <>
            <hr className={styles.separator}/>
            <p className={styles.otp_p}>Didn't receive it ?</p>
            <p className={styles.otp_p}>Check your spam folder or <Link href="#" onClick={emitOtpAction} variant="body2" target="_self">resend email.</Link>
            </p>
        </>
    );
}

const EmailErrorActions = ({emitOtpAction, createAccountAction, onValidateEmail, disableInput}) => {
    return(
            <Grid container style={{alignItems: 'center', marginTop: "20%"}}>
                <Grid item xs>
                    <p>
                    Access your account by <Link href="#" onClick={emitOtpAction} variant="body2" target="_self">having
                        a single-use login code emailed to you</Link>.  Or
                        <Link href={createAccountAction} variant="body2" target="_self"> establish your account by setting up a password</Link>.
                    </p>
                </Grid>
                <Grid item>
                    <Button variant="contained"
                            onClick={onValidateEmail}
                            disabled={disableInput}
                            color="primary">
                        Continue
                    </Button>
                </Grid>
            </Grid>
    );
}

const ExistingAccountActions = ({emitOtpAction, onAuthenticate, disableInput}) => {
    return(
        <Grid container style={{alignItems: 'center', marginTop: "20%"}}>
            <Grid item xs>
                <p>
                You have an existing account. If you don't remember or never set a password,
                <Link href="#" onClick={emitOtpAction} variant="body2" target="_self">  get a single-use login code </Link> emailed to you now.
                </p>
            </Grid>
            <Grid item>
                <Button variant="contained"
                        disabled={disableInput}
                        onClick={onAuthenticate}
                        className={styles.continue_btn} type="submit"
                        color="primary">
                    Continue
                </Button>
            </Grid>
        </Grid>
 );
}

const ThirdPartyIdentityProviders = ({thirdPartyProviders, formAction, disableInput}) => {
    return(
        <>
            <DividerWithText>Or</DividerWithText>
            {
                thirdPartyProviders.map((provider) => {
                    return (
                        <Button
                            disabled={disableInput}
                            key={provider.name}
                            variant="contained"
                            className={styles.third_party_idp_button+` ${provider.name}`}
                            color="primary"
                            title={`Sign In with ${provider.label}`}
                            href={`${formAction}/${provider.name}`}>
                            {provider.label}
                        </Button>
                    );
                })
            }
            <p>If you have a login, you may still choose to use a social login with <b>the same email address</b> to access your account.</p>
        </>
    );
}

class LoginPage extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            user_name: props.userName,
            user_password: '',
            user_pic: props.hasOwnProperty('user_pic') ? props.user_pic : null,
            user_fullname: props.hasOwnProperty('user_fullname') ? props.user_fullname : null,
            user_verified: props.hasOwnProperty('user_verified') ? props.user_verified : false,
            errors: {
                email: "",
                password: props.authError != "" ? props.authError : "",
            },
            captcha_value: '',
            showPassword: false,
            disableInput: false,
            authFlow: props.flow,
        }

        if(this.state.errors.password && this.state.errors.password.includes("is not yet verified")){
            this.state.errors.password = this.state.errors.password + `Or <a href='${this.props.verifyEmailAction}?email=${encodeURIComponent(this.props.userName)}'>have another verification email sent to you.</a>`;
        }

        this.onHandleUserNameChange = this.onHandleUserNameChange.bind(this);
        this.onValidateEmail = this.onValidateEmail.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
        this.onAuthenticate = this.onAuthenticate.bind(this);
        this.onChangeRecaptcha = this.onChangeRecaptcha.bind(this);
        this.onUserPasswordChange = this.onUserPasswordChange.bind(this);
        this.shouldShowCaptcha = this.shouldShowCaptcha.bind(this);
        this.handleClickShowPassword = this.handleClickShowPassword.bind(this);
        this.handleMouseDownPassword = this.handleMouseDownPassword.bind(this);
        this.handleEmitOtpAction = this.handleEmitOtpAction.bind(this);
    }

    handleEmitOtpAction(ev){
        ev.preventDefault();
        let user_fullname = this.state.user_fullname ? this.state.user_fullname : this.state.user_name;

        emitOTP(this.state.user_name, this.props.token).then((payload) => {
            let {response} = payload;
            this.setState({...this.state,
                authFlow:"otp",
                errors: {
                    email: "",
                    password:"",
                },
                user_verified: true,
                user_fullname:user_fullname,
            });
        }, (error) => {
            let {response, status, message} = error;
            Swal('Oops...', 'Something went wrong!', 'error')
        });
        return false;
    }

    shouldShowCaptcha() {
        return (
            this.props.hasOwnProperty('maxLoginAttempts2ShowCaptcha') &&
            this.props.hasOwnProperty('loginAttempts') &&
            this.props.loginAttempts >= this.props.maxLoginAttempts2ShowCaptcha
        )
    }

    onAuthenticate(ev) {
        if (this.state.user_password == '') {
            let error = 'Password is empty';
            if(this.state.authFlow == 'OTP'){
                error = 'Verification Code is empty';
            }
            this.setState({...this.state, errors: {...this.state.errors, password: error}});
            ev.preventDefault();
            return false;
        }
        if (this.state.captcha_value == '' && this.shouldShowCaptcha()) {
            this.setState({...this.state, errors: {...this.state.errors, password: 'you must check CAPTCHA'}});
            ev.preventDefault();
            return false;
        }
        return true;
    }

    onChangeRecaptcha(value) {
        this.setState({...this.state, captcha_value: value});
    }

    onHandleUserNameChange(ev) {
        let {value, id} = ev.target;
        this.setState({...this.state, user_name: value});
    }

    onUserPasswordChange(ev) {
        let {errors} = this.state;
        let {value, id} = ev.target;
        if(value == "") // clean error
            errors[id] = '';
        this.setState({...this.state, user_password: value, errors: {...errors}});
    }

    onValidateEmail(ev) {

        ev.preventDefault();

        if (this.state.user_name == '') {
            return false;
        }

        if (!emailValidator(this.state.user_name)) {
            return false;
        }

        this.setState({...this.state, disableInput: true});
        verifyAccount(this.state.user_name).then((payload) => {
            let {response} = payload;

            this.setState({
                ...this.state,
                user_pic: response.pic,
                user_fullname: response.full_name,
                user_verified: true,
                errors: {
                    email: '',
                    password: ''
                },
                disableInput: false
            })
        }, (error) => {

            let {response, status, message} = error;

            let newErrors = {};

            newErrors['password'] = '';
            newErrors['email'] = "We could not find an Account with that email Address";

            if(status == 429){
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

    handleDelete() {
        this.setState({...this.state, user_name: null, user_pic: null, user_fullname: null, user_verified: false, authFlow:"password", errors: {
                email: "",
                password:"",
            },});
    }

    handleClickShowPassword(ev) {
        this.setState({...this.state, showPassword: !this.state.showPassword})
    }

    handleMouseDownPassword(ev) {
        ev.preventDefault();
    }

    render() {
        return (
            <Container component="main" maxWidth="xs" className={styles.main_container}>
                <CssBaseline/>
                <div className={styles.inner_container}>
                    <Typography component="h1" className={styles.app_logo_container}>
                        <a href={window.location.href}><img className={styles.app_logo} alt="appLogo" src={this.props.appLogo}/></a>
                    </Typography>
                    <Typography component="h1" variant="h5">
                        Sign in {this.state.user_fullname && <Chip
                        avatar={<Avatar alt={this.state.user_fullname} src={this.state.user_pic}/>}
                        variant="outlined"
                        className={styles.valid_user_name_chip}
                        label={this.state.user_fullname}
                        onDelete={this.handleDelete}/>}
                    </Typography>
                    {!this.state.user_verified &&
                    <>
                        <EmailInputForm
                            onValidateEmail={this.onValidateEmail}
                            onHandleUserNameChange={this.onHandleUserNameChange}
                            disableInput={this.state.disableInput}
                            emailError={this.state.errors.email}/>
                        { this.state.errors.email == '' &&
                            <p>If you have just registered for an event or don't know your password, enter your email
                                address and you'll be able to request a single-use login code.</p>
                        }
                        { this.state.errors.email == '' &&
                          this.props.thirdPartyProviders.length > 0 &&
                            <ThirdPartyIdentityProviders
                                thirdPartyProviders={this.props.thirdPartyProviders}
                                formAction={this.props.formAction}
                                disableInput={this.state.disableInput}
                            />
                        }
                        {
                            // we already had an interaction and got an user error...
                            this.state.errors.email != '' &&
                            <>
                                <EmailErrorActions
                                    emitOtpAction={this.handleEmitOtpAction}
                                    onValidateEmail={this.onValidateEmail}
                                    disableInput={this.state.disableInput}
                                    createAccountAction={(this.state.user_name) ? `${this.props.createAccountAction}?email=${encodeURIComponent(this.state.user_name)}`: this.props.createAccountAction}
                                />
                                <HelpLinks
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
                    {this.state.user_verified && this.state.authFlow == 'password' &&
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
                        />
                        <HelpLinks
                            appName={this.props.appName}
                            forgotPasswordAction={this.props.forgotPasswordAction}
                            verifyEmailAction={this.props.verifyEmailAction}
                            helpAction={this.props.helpAction}
                            emitOtpAction={this.handleEmitOtpAction}
                            showEmitOtpAction={false}
                            showForgotPasswordAction={true}
                            showVerifyEmailAction={false}
                            showHelpAction={true}
                        />
                    </>
                    }
                    {this.state.user_verified && this.state.authFlow == 'otp' &&
                    // proceed to ask for password ( 2nd step )
                    <>
                        <OTPInputForm
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
                        />
                        <OTPHelpLinks emitOtpAction={this.handleEmitOtpAction}/>
                    </>
                    }
                </div>
            </Container>
        );
    }
}

// Or Create your Own theme:
const theme = createMuiTheme({
    palette: {
        primary: {
            main: '#3fa2f7'
        },
    },
    overrides: {
        MuiButton: {
            containedPrimary: {
                color: 'white'
            }
        }
    }
});

ReactDOM.render(
    <MuiThemeProvider theme={theme}>
        <LoginPage {...config}/>
    </MuiThemeProvider>,
    document.querySelector('#root')
);