import React from 'react'
import {render, screen} from '@testing-library/react';
import '@testing-library/jest-dom'
import {SignUpPage} from '../signup'

const initialValues = {
    first_name: '',
    last_name: '',
    email: '',
    country_iso_code: '',
    password: '',
    password_confirmation: '',
    agree_code_of_conduct: false,
}
const passwordPolicy = {
    min_length: 8,
    max_length: 30,
}

const props = {
    csrfToken: '',
    realm: '',
    appName: 'FNid',
    appLogo: 'https://object-storage-ca-ymq-1.vexxhost.net/swift/v1/6e4619c416ff4bd19e1c087f27a43eea/images-fn/FNid_WHT_logo_rgb.svg',
    clientId: '',
    codeOfConductUrl: 'https://www.openstack.org/legal/community-code-of-conduct',
    countries: [{value: "US", text: "United States"}],
    redirectUri: '',
    signInAction: 'https://local.idp.com/auth/login',
    signUpAction: 'https://local.idp.com/auth/register',
    signUpError: '',
    captchaPublicKey: '6Lds_i0eAAAAALWjzVskhfYMIYxSYyAWEXpbI35r',
    showInfoBanner: parseInt('0') === 1 ? true : false,
    infoBannerContent: '<p><b>OpenStackID is now OpenInfraID!</b></p><p>Same auth, new name. Use your existing login to access OpenStack and OpenInfra services. Need help? <a href="mailto:test@test.com">Contact us.</a></p>',
    tenantName: 'FNTECH',
    initialValues: initialValues,
    passwordPolicy: passwordPolicy
}

describe('SignUpPage', () => {
    test('renders correctly', () => {
        render(<SignUpPage {...props} />)
        expect(screen.getByText(/register now/i)).toBeInTheDocument()
    })
})

