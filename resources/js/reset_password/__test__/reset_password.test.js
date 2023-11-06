import React from 'react'
import {render, screen} from '@testing-library/react';
import '@testing-library/jest-dom'
import {ResetPasswordPage} from '../reset_password'

const props = {
    appLogo: '',
    captchaPublicKey: '',
    csrfToken: '',
    token: '',
    infoBannerContent: '',
    initialValues: {
        email: '',
        password: '',
        password_confirmation: ''
    },
    passwordPolicy: '',
    resetPasswordAction: '',
    resetPasswordError: '',
    sessionStatus: '',
    submitButtonText: 'Change Password',
    showInfoBanner: true,
}

describe('ResetPasswordPage', () => {
    test('renders correctly', () => {
        render(<ResetPasswordPage {...props} />)
        expect(screen.getByRole('button', {name: props.submitButtonText})).toBeInTheDocument()
    })
})

