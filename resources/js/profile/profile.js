import React, {useState} from "react";
import ReactDOM from "react-dom";
import Button from "@material-ui/core/Button";
import Card from "@material-ui/core/Card";
import CardContent from "@material-ui/core/CardContent";
import Container from "@material-ui/core/Container";
import CssBaseline from "@material-ui/core/CssBaseline";
import Checkbox from "@material-ui/core/Checkbox";
import DeleteIcon from '@material-ui/icons/Delete';
import Grid from "@material-ui/core/Grid";
import MenuItem from "@material-ui/core/MenuItem";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";
import Select from "@material-ui/core/Select";
import Swal from "sweetalert2";
import {MuiThemeProvider, createTheme} from "@material-ui/core/styles";
import {useFormik} from "formik";
import {object, ref, string} from "yup";
import RichTextEditor from "../components/rich_text_editor";
import FormControlLabel from "@material-ui/core/FormControlLabel";
import UserAccessTokensGrid from "../components/user_access_tokens_grid";
import UserActionsGrid from "../components/user_actions_grid";
import {getUserActions, getUserAccessTokens, PAGE_SIZE, revokeToken, save} from "./actions";
import ProfileImageUploader from "./components/profile_image_uploader/profile_image_uploader";
import Navbar from "../components/navbar/navbar";
import Divider from "@material-ui/core/Divider";
import Link from "@material-ui/core/Link";
import PasswordChangePanel from "../components/password_change_panel";
import LoadingIndicator from "../components/loading_indicator";
import TopLogo from "../components/top_logo/top_logo";
import {handleErrorResponse} from "../utils";
import {buildPasswordValidationSchema} from "../validator";

import styles from "./profile.module.scss";

const ProfilePage = ({
                         appLogo,
                         countries,
                         csrfToken,
                         initialValues,
                         languages,
                         menuConfig,
                         passwordPolicy,
                         redirectUri
                     }) => {
    const [pic, setPic] = useState(null);
    const [loading, setLoading] = useState(false);
    const [accessTokensListRefresh, setAccessTokensListRefresh] = useState(true);

    const buildValidationSchema = () =>
        object({
            first_name: string("First name is required").required(
                "First name is required"
            ),
            last_name: string("Last name is required").required(
                "Last name is required"
            ),
            email: string("Email is required")
                .email("Enter a valid email")
                .required("Email is required"),
            second_email: string("Enter a valid email")
                .email("Enter a valid email"),
            third_email: string("Email is required")
                .email("Enter a valid email"),
            ...buildPasswordValidationSchema(passwordPolicy)
        });

    const formik = useFormik({
        //enableReinitialize: true,
        initialValues: initialValues,
        validationSchema: buildValidationSchema(),
        onSubmit: (values) => {
            setLoading(true);
            save({...values}, pic, csrfToken).then((res) => {
                setLoading(false);
                Swal("Profile saved", "Your profile has been saved successfully", "success");
            }).catch((err) => {
                console.log(err);
                setLoading(false);
                handleErrorResponse(err);
            });
        },
    });

    const confirmRevocation = (value) => {
        Swal({
            title: 'Are you sure to revoke this token?',
            text: 'This is an non reversible process!',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, revoke it!'
        }).then((result) => {
            if (result.value) {
                revokeToken(value, 'access-token').then(() => {
                    Swal(`Access token revoked`, `The access token has been revoked successfully`, "success");
                    setAccessTokensListRefresh(!accessTokensListRefresh);
                }).catch((err) => {
                    handleErrorResponse(err);
                });
            }
        });
    };

    return (
        <Container component="main" maxWidth="xs" className={styles.main_container}>
            <CssBaseline/>
            <TopLogo appLogo={appLogo}/>
            <Navbar menuConfig={menuConfig}/>
            <form
                onSubmit={formik.handleSubmit}
                method="post"
                encType="multipart/form-data"
                target="_self"
            >
                <Card className={styles.profile_container} variant="outlined">
                    <CardContent>
                        <Grid
                            container
                            direction="column"
                            spacing={2}
                            justifyContent="center"
                        >
                            <Grid item>
                                Hello, {initialValues.first_name} {initialValues.last_name}
                            </Grid>
                            <Grid item>
                                Your OPENID: <a href={initialValues.openid_url}>{initialValues.openid_url}</a>
                            </Grid>
                            <Grid item>
                                <Typography variant="h6">
                                    FNid Account Settings:
                                </Typography>
                            </Grid>
                            <Divider variant="middle"/>
                            <Grid item>
                                <ProfileImageUploader userPicURL={initialValues.pic}
                                                      onFileSelected={(file) => setPic(file)}/>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="first_name"
                                        name="first_name"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="First Name"
                                        inputProps={{maxLength: 100}}
                                        autoFocus={true}
                                        value={formik.values.first_name}
                                        onChange={formik.handleChange}
                                        data-lpignore="true"
                                        error={
                                            formik.touched.first_name &&
                                            Boolean(formik.errors.first_name)
                                        }
                                        helperText={
                                            formik.touched.first_name && formik.errors.first_name
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        id="last_name"
                                        name="last_name"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Last Name"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.last_name}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.last_name &&
                                            Boolean(formik.errors.last_name)
                                        }
                                        helperText={
                                            formik.touched.last_name && formik.errors.last_name
                                        }
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="email"
                                        name="email"
                                        autoComplete="email"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Email"
                                        inputProps={{maxLength: 255}}
                                        value={formik.values.email}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={formik.touched.email && Boolean(formik.errors.email)}
                                        helperText={formik.touched.email && formik.errors.email}
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        id="identifier"
                                        name="identifier"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Identifier"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.identifier}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.identifier &&
                                            Boolean(formik.errors.identifier)
                                        }
                                        helperText={
                                            formik.touched.identifier && formik.errors.identifier
                                        }
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="second_email"
                                        name="second_email"
                                        autoComplete="email"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Second Email"
                                        inputProps={{maxLength: 255}}
                                        value={formik.values.second_email}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={formik.touched.second_email && Boolean(formik.errors.second_email)}
                                        helperText={formik.touched.second_email && formik.errors.second_email}
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        id="third_email"
                                        name="third_email"
                                        autoComplete="email"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Third Email"
                                        inputProps={{maxLength: 255}}
                                        value={formik.values.third_email}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={formik.touched.third_email && Boolean(formik.errors.third_email)}
                                        helperText={formik.touched.third_email && formik.errors.third_email}
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        type="date"
                                        id="birthday"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Birthday"
                                        InputLabelProps={{
                                            shrink: true,
                                        }}
                                        value={formik.values.birthday}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.birthday &&
                                            Boolean(formik.errors.birthday)
                                        }
                                        helperText={
                                            formik.touched.birthday && formik.errors.birthday
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <Select
                                        id="gender"
                                        name="gender"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        value={formik.values.gender}
                                        data-lpignore="true"
                                        displayEmpty
                                        onChange={formik.handleChange}
                                        className={styles.genders}
                                        error={
                                            formik.touched.gender &&
                                            Boolean(formik.errors.gender)
                                        }
                                    >
                                        <MenuItem value="">
                                        <span className={styles.genders_empty_text}>
                                          Select a gender
                                        </span>
                                        </MenuItem>
                                        {['Male', 'Female', 'Prefer not to say', 'Specify'].map((gender) => (
                                            <MenuItem key={gender} value={gender}>
                                                {gender}
                                            </MenuItem>
                                        ))}
                                    </Select>
                                    {formik.touched.gender &&
                                        formik.errors.gender && (
                                            <div className={styles.error_label}>
                                                {formik.errors.gender}
                                            </div>
                                        )}
                                </Grid>
                            </Grid>
                            {formik.values.gender === 'Specify' &&
                                <Grid item spacing={2} container direction="row">
                                    <Grid item xs={6}>
                                    </Grid>
                                    <Grid item xs={6}>
                                        <TextField
                                            id="gender_specify"
                                            name="gender_specify"
                                            autoComplete="email"
                                            variant="outlined"
                                            fullWidth
                                            size="small"
                                            label="Specify your gender"
                                            inputProps={{maxLength: 255}}
                                            value={formik.values.gender_specify}
                                            data-lpignore="true"
                                            onChange={formik.handleChange}
                                            error={formik.touched.gender_specify && Boolean(formik.errors.gender_specify)}
                                            helperText={formik.touched.gender_specify && formik.errors.gender_specify}
                                        />
                                    </Grid>
                                </Grid>
                            }
                            <Grid item container direction="column">
                                <Grid item>
                                    <Typography variant="subtitle1">
                                        Bio
                                    </Typography>
                                </Grid>
                                <Grid item>
                                    <RichTextEditor
                                        id="bio"
                                        name="bio"
                                        value={formik.values.bio}
                                        data-lpignore="true"
                                        onChange={(content) => formik.setFieldValue('bio', content)}
                                    />
                                </Grid>
                            </Grid>
                            <Grid item container direction="column">
                                <Grid item>
                                    <Typography variant="subtitle1">
                                        Statement of interest
                                    </Typography>
                                </Grid>
                                <Grid item>
                                    <RichTextEditor
                                        id="statement_of_interest"
                                        name="statement_of_interest"
                                        value={formik.values.statement_of_interest}
                                        data-lpignore="true"
                                        onChange={(content) => formik.setFieldValue('statement_of_interest', content)}
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={4}>
                                    <TextField
                                        id="irc"
                                        name="irc"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="IRC"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.irc}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.irc &&
                                            Boolean(formik.errors.irc)
                                        }
                                        helperText={
                                            formik.touched.irc && formik.errors.irc
                                        }
                                    />
                                </Grid>
                                <Grid item xs={4}>
                                    <TextField
                                        id="github_user"
                                        name="github_user"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Github user"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.github_user}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.github_user &&
                                            Boolean(formik.errors.github_user)
                                        }
                                        helperText={
                                            formik.touched.github_user && formik.errors.github_user
                                        }
                                    />
                                </Grid>
                                <Grid item xs={4}>
                                    <TextField
                                        id="twitter_name"
                                        name="twitter_name"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Twitter"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.twitter_name}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.twitter_name &&
                                            Boolean(formik.errors.twitter_name)
                                        }
                                        helperText={
                                            formik.touched.twitter_name && formik.errors.twitter_name
                                        }
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="wechat_user"
                                        name="wechat_user"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="WEChat user"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.wechat_user}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.wechat_user &&
                                            Boolean(formik.errors.wechat_user)
                                        }
                                        helperText={
                                            formik.touched.wechat_user && formik.errors.wechat_user
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        id="linked_in_profile"
                                        name="linked_in_profile"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="LinkedIn Profile"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.linked_in_profile}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.linked_in_profile &&
                                            Boolean(formik.errors.linked_in_profile)
                                        }
                                        helperText={
                                            formik.touched.linked_in_profile && formik.errors.linked_in_profile
                                        }
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="address1"
                                        name="address1"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Address 1"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.address1}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.address1 &&
                                            Boolean(formik.errors.address1)
                                        }
                                        helperText={
                                            formik.touched.address1 && formik.errors.address1
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        id="address2"
                                        name="address2"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Address 2"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.address2}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.address2 &&
                                            Boolean(formik.errors.address2)
                                        }
                                        helperText={
                                            formik.touched.address2 && formik.errors.address2
                                        }
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="city"
                                        name="city"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="City"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.city}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.city &&
                                            Boolean(formik.errors.city)
                                        }
                                        helperText={
                                            formik.touched.city && formik.errors.city
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        id="state"
                                        name="state"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="State"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.state}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.state &&
                                            Boolean(formik.errors.state)
                                        }
                                        helperText={
                                            formik.touched.state && formik.errors.state
                                        }
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="post_code"
                                        name="post_code"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Zip Code"
                                        inputProps={{maxLength: 10}}
                                        value={formik.values.post_code}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.post_code &&
                                            Boolean(formik.errors.post_code)
                                        }
                                        helperText={
                                            formik.touched.post_code && formik.errors.post_code
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <Select
                                        id="country_iso_code"
                                        name="country_iso_code"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        value={formik.values.country_iso_code}
                                        data-lpignore="true"
                                        displayEmpty
                                        onChange={formik.handleChange}
                                        className={styles.countries}
                                        error={
                                            formik.touched.country_iso_code &&
                                            Boolean(formik.errors.country_iso_code)
                                        }
                                    >
                                        <MenuItem value="">
                                            <span className={styles.countries_empty_text}>
                                              Select a country
                                            </span>
                                        </MenuItem>
                                        {countries.map((country) => (
                                            <MenuItem key={country.value} value={country.value}>
                                                {country.text}
                                            </MenuItem>
                                        ))}
                                    </Select>
                                    {formik.touched.country_iso_code &&
                                        formik.errors.country_iso_code && (
                                            <div className={styles.error_label}>
                                                {formik.errors.country_iso_code}
                                            </div>
                                        )}
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="company"
                                        name="company"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Company"
                                        inputProps={{maxLength: 100}}
                                        value={formik.values.company}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.company &&
                                            Boolean(formik.errors.company)
                                        }
                                        helperText={
                                            formik.touched.company && formik.errors.company
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        id="job_title"
                                        name="job_title"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Job Title"
                                        inputProps={{maxLength: 10}}
                                        value={formik.values.job_title}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.job_title &&
                                            Boolean(formik.errors.job_title)
                                        }
                                        helperText={
                                            formik.touched.job_title && formik.errors.job_title
                                        }
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <TextField
                                        id="phone_number"
                                        name="phone_number"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        label="Phone"
                                        inputProps={{maxLength: 20}}
                                        value={formik.values.phone_number}
                                        data-lpignore="true"
                                        onChange={formik.handleChange}
                                        error={
                                            formik.touched.phone_number &&
                                            Boolean(formik.errors.phone_number)
                                        }
                                        helperText={
                                            formik.touched.phone_number && formik.errors.phone_number
                                        }
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <Select
                                        id="language"
                                        name="language"
                                        variant="outlined"
                                        fullWidth
                                        size="small"
                                        value={formik.values.language}
                                        data-lpignore="true"
                                        displayEmpty
                                        onChange={formik.handleChange}
                                        className={styles.languages}
                                        error={
                                            formik.touched.language &&
                                            Boolean(formik.errors.language)
                                        }
                                    >
                                        <MenuItem value="">
                                            <span className={styles.languages_empty_text}>
                                              Select a language
                                            </span>
                                        </MenuItem>
                                        {languages.map((language) => (
                                            <MenuItem key={language.value} value={language.value}>
                                                {language.text}
                                            </MenuItem>
                                        ))}
                                    </Select>
                                    {formik.touched.language &&
                                        formik.errors.language && (
                                            <div className={styles.error_label}>
                                                {formik.errors.language}
                                            </div>
                                        )}
                                </Grid>
                            </Grid>
                            <PasswordChangePanel
                                hasPasswordSet={initialValues.has_password_set}
                                formik={formik}
                                passwordPolicy={passwordPolicy}/>

                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <FormControlLabel
                                        control={<Checkbox name="public_profile_show_photo"
                                                           id="public_profile_show_photo"
                                                           checked={formik.values.public_profile_show_photo}
                                                           onChange={formik.handleChange}
                                                           color="primary"/>}
                                        label="Show Picture on Public Profile?"
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <FormControlLabel
                                        control={<Checkbox name="public_profile_show_fullname"
                                                           id="public_profile_show_fullname"
                                                           checked={formik.values.public_profile_show_fullname}
                                                           onChange={formik.handleChange}
                                                           color="primary"/>}
                                        label="Show Full name on Public Profile?"
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <FormControlLabel
                                        control={<Checkbox name="public_profile_show_email"
                                                           id="public_profile_show_email"
                                                           checked={formik.values.public_profile_show_email}
                                                           onChange={formik.handleChange}
                                                           color="primary"/>}
                                        label="Show Email on Public Profile?"
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <FormControlLabel
                                        control={<Checkbox name="public_profile_show_bio"
                                                           id="public_profile_show_bio"
                                                           checked={formik.values.public_profile_show_bio}
                                                           onChange={formik.handleChange}
                                                           color="primary"/>}
                                        label="Show Bio on Public Profile?"
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <FormControlLabel
                                        control={<Checkbox name="public_profile_show_social_media_info"
                                                           id="public_profile_show_social_media_info"
                                                           checked={formik.values.public_profile_show_social_media_info}
                                                           onChange={formik.handleChange}
                                                           color="primary"/>}
                                        label="Show Social Media Info on Public Profile?"
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <FormControlLabel
                                        control={<Checkbox name="public_profile_show_telephone_number"
                                                           id="public_profile_show_telephone_number"
                                                           checked={formik.values.public_profile_show_telephone_number}
                                                           onChange={formik.handleChange}
                                                           color="primary"/>}
                                        label="Show Telephone Number on Public Profile?"
                                    />
                                </Grid>
                            </Grid>
                            <Grid item spacing={2} container direction="row">
                                <Grid item xs={6}>
                                    <FormControlLabel
                                        control={<Checkbox name="public_profile_allow_chat_with_me"
                                                           id="public_profile_allow_chat_with_me"
                                                           checked={formik.values.public_profile_allow_chat_with_me}
                                                           onChange={formik.handleChange}
                                                           color="primary"/>}
                                        label="Allow People Chat With Me?"
                                    />
                                </Grid>
                            </Grid>
                            <Grid item container alignItems="center" justifyContent="center">
                                <Button
                                    variant="contained"
                                    className={styles.button}
                                    disableElevation
                                    type="submit"
                                >
                                    Save
                                </Button>
                            </Grid>
                            <Divider/>
                            <Grid item container>
                                <Typography component="h1" variant="h5">
                                    User Access Tokens
                                </Typography>
                            </Grid>
                            <Grid item container alignItems="center" justifyContent="center">
                                <UserAccessTokensGrid
                                    getUserAccessTokens={
                                        (page, order, orderDir, filters) =>
                                            getUserAccessTokens(page, order, orderDir, filters)
                                    }
                                    pageSize={PAGE_SIZE}
                                    tokensListChanged={accessTokensListRefresh}
                                    onRevoke={confirmRevocation}
                                />
                            </Grid>
                            <Divider/>
                            <Grid item container>
                                <Typography component="h1" variant="h5">
                                    User Actions
                                </Typography>
                            </Grid>
                            <Grid item container alignItems="center" justifyContent="center">
                                <UserActionsGrid getUserActions={
                                    (page, order, orderDir, filters) =>
                                        getUserActions(page, order, orderDir, filters)
                                } pageSize={PAGE_SIZE}/>
                            </Grid>
                            {
                                initialValues.trusted_sites?.length > 0 &&
                                <>
                                    <Divider/>
                                    <Grid item container>
                                        <Typography component="h1" variant="h5">
                                            Trusted Sites
                                        </Typography>
                                    </Grid>
                                    <Grid item container alignItems="center" justifyContent="center">
                                        <Grid item spacing={2} container direction="row">
                                            <Grid item xs={5}>
                                                <Typography variant="subtitle1">
                                                    Realm
                                                </Typography>
                                            </Grid>
                                            <Grid item xs={3}>
                                                <Typography variant="subtitle1">
                                                    Policy
                                                </Typography>
                                            </Grid>
                                            <Grid item xs={3}>
                                                <Typography variant="subtitle1">
                                                    Trusted Data
                                                </Typography>
                                            </Grid>
                                        </Grid>
                                        <Grid item xs={12}>
                                            <Divider/>
                                        </Grid>
                                        {
                                            initialValues.trusted_sites.map((item, ix) =>
                                                <Grid item spacing={2} container direction="row" key={ix}>
                                                    <Grid item xs={5}>
                                                        {item.realm}
                                                    </Grid>
                                                    <Grid item xs={3}>
                                                        {item.auth_policy}
                                                    </Grid>
                                                    <Grid item xs={3}>
                                                        {item.trusted_data}
                                                    </Grid>
                                                    <Grid item xs={1}>
                                                        <Link href={item.deleteURL}
                                                              title="Deletes a decision about a particular trusted site"
                                                              target="_self">
                                                            <DeleteIcon/>
                                                        </Link>
                                                    </Grid>
                                                </Grid>
                                            )
                                        }
                                    </Grid>
                                </>
                            }
                        </Grid>
                    </CardContent>
                </Card>
                <input type="hidden" value={csrfToken} id="_token" name="_token"/>
                <input type="hidden" name="_method" value="PUT"/>
                <input type="hidden" id="id" name="id" value={initialValues.id}/>
                {redirectUri && (
                    <input
                        type="hidden"
                        id="redirect_uri"
                        name="redirect_uri"
                        value={redirectUri}
                    />
                )}
            </form>
            <LoadingIndicator open={loading}/>
        </Container>
    );
};

// Or Create your Own theme:
const theme = createTheme({
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

Object.assign(theme, {
    overrides: {
        MUIRichTextEditor: {
            root: {
                marginTop: 5,
                height: 400,
                border: "1px solid #D3D3D3",
                borderRadius: "5px"
            },
            editor: {
                borderTop: "1px solid #D3D3D3"
            }
        }
    }
})

ReactDOM.render(
    <MuiThemeProvider theme={theme}>
        <ProfilePage {...config} />
    </MuiThemeProvider>,
    document.querySelector("#root")
);
