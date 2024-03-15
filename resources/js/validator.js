import {ref, string} from "yup";

export const emailValidator = (value) => {
    return /^\S+@\S+(\.\S+)*$/.test(value)
}

export const buildPasswordValidationSchema = (passwordPolicy, required = false) => {
    const res = {
        password: string()
            .when([], {
                is: () => required,
                then: string().required("Password is required"),
                otherwise: string().notRequired(),
            })
            .min(passwordPolicy.min_length, `Password must be at least ${passwordPolicy.min_length} characters`)
            .max(passwordPolicy.max_length, `Password must be at most ${passwordPolicy.max_length} characters`)
            .matches(
                new RegExp(passwordPolicy.shape_pattern),
                passwordPolicy.shape_warning
            ),
        password_confirmation: string()
            .min(passwordPolicy.min_length, `Password confirmation must be at least ${passwordPolicy.min_length} characters`)
            .max(passwordPolicy.max_length, `Password confirmation must be at most ${passwordPolicy.max_length} characters`)
            .matches(
                new RegExp(passwordPolicy.shape_pattern),
                passwordPolicy.shape_warning
            )
            .oneOf([ref('password'), null], 'Passwords must match')
    };
    return res;
}