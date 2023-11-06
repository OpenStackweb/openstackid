import React from 'react'
import {render, screen} from "@testing-library/react";
import '@testing-library/jest-dom'
import {EmailVerificationPage} from '../email_verification'

const props = {
    appLogo: '',
    captchaPublicKey: '',
    csrfToken: '',
    emailVerificationAction: '',
    emailVerificationError: '',
    infoBannerContent: '',
    initialValues: {
        id: 1,
        pic: '',
        language: 'en',
        country_iso_code: 'CA',
        gender: 'Male'
    },
    sessionStatus: '',
    showInfoBanner: '',
    submitButtonText: ''
}

describe('EmailVerificationPage', () => {
    test('renders correctly', () => {
        render(<EmailVerificationPage {...props} />)
        expect(screen.getByText('Email', {
            selector: 'label'
        })).toBeInTheDocument()
    })
})

