import React from 'react'
import {render, screen} from '@testing-library/react';
import '@testing-library/jest-dom'
import {LoginPage} from '../login'

const props = {
    token: '',
    userName: '',
    realm: '',
    appName: 'FNid',
    appLogo: 'https://object-storage-ca-ymq-1.vexxhost.net/swift/v1/6e4619c416ff4bd19e1c087f27a43eea/images-fn/FNid_WHT_logo_rgb.svg',
    formAction: 'https://local.idp.com/auth/login',
    accountVerifyAction: 'https://local.idp.com/auth/login/account-verify',
    emitOtpAction: 'https://local.idp.com/auth/login/otp',
    authError: '',
    captchaPublicKey: '6Lds_i0eAAAAALWjzVskhfYMIYxSYyAWEXpbI35r',
    flow: 'password',
    thirdPartyProviders: [
        {label: "Facebook", name: "facebook"},
        {label: "Apple", name: "apple"},
        {label: "Linkedin", name: "linkedin"},
    ],
    forgotPasswordAction: 'https://local.idp.com/auth/password/reset',
    verifyEmailAction: 'https://local.idp.com/auth/verification',
    helpAction: 'mailto:support@openstack.org',
    createAccountAction: 'https://local.idp.com/auth/register',
    allowNativeAuth: true,
    showInfoBanner: true,
    infoBannerContent: '<p><b>OpenStackID is now OpenInfraID!</b></p><p>Same auth, new name. Use your existing login to access OpenStack and OpenInfra services. Need help? <a href="mailto:test@test.com">Contact us.</a></p>',
}

describe('LoginPage', () => {
    test('renders correctly', () => {
        render(<LoginPage {...props} />)
        expect(screen.getByText('Login')).toBeInTheDocument()
    })

    describe('Banner', () => {
        test('should not render', () => {
            props.showInfoBanner = false
            render(<LoginPage {...props} />)
            expect(screen.queryByText(/Same auth, new name. Use your existing login to access OpenStack and OpenInfra services/i)).not.toBeInTheDocument()
        })

        test('should render', () => {
            props.showInfoBanner = true
            render(<LoginPage {...props} />)
            expect(screen.getByText(/Same auth, new name. Use your existing login to access OpenStack and OpenInfra services/i)).toBeInTheDocument()
        })
    })
})

