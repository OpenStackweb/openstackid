import {ref, string} from "yup";
import {decodeHtmlEntities} from "./utils.js"

const validatePasswordPattern = (shapePattern, allowed_special_characters, warning) => {
    return function(value) {
        if (!value) return true;

        const pattern = new RegExp(shapePattern);

        if (pattern.test(value)) {
            return true;
        }

        // Check invalid characters
        const allowedCharsRegEx = new RegExp(allowed_special_characters);

        for (let i = 0; i < value.length; i++) {
            const char = value[i];
            if (!allowedCharsRegEx.test(char)) {
                return this.createError({
                    message: `Invalid character "${char}" at position ${i + 1}`,
                    path: this.path,
                });
            }
        }

        // Check remain requirements
        return this.createError({
            message: decodeHtmlEntities(warning),
            path: this.path,
        });
    }
}

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
            .test('password-requirements', validatePasswordPattern(
                passwordPolicy.shape_pattern, passwordPolicy.allowed_special_characters, passwordPolicy.shape_warning)),
        password_confirmation: string()
            .min(passwordPolicy.min_length, `Password confirmation must be at least ${passwordPolicy.min_length} characters`)
            .max(passwordPolicy.max_length, `Password confirmation must be at most ${passwordPolicy.max_length} characters`)
            .test('password-requirements', validatePasswordPattern(
                passwordPolicy.shape_pattern, passwordPolicy.allowed_special_characters, passwordPolicy.shape_warning))
            .oneOf([ref('password'), null], 'Passwords must match')
    };
    return res;
}