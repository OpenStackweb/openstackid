import React from 'react'
import {fireEvent, getByPlaceholderText, render, screen, waitFor} from '@testing-library/react';
import '@testing-library/jest-dom'
import {ProfilePage} from '../profile'

const props = {
    countries: [{value: "CA", text: "Canada"}],
    csrfToken: '',
    initialValues: {
        id: 1,
        pic: '',
        language: 'en',
        country_iso_code: 'CA',
        gender: 'Male',
        first_name: 'Test',
        last_name: 'User',
        email: 'test@mail.com'
    },
    languages: [
        {value: "en", text: "English"},
        {value: "cs", text: "Czech"}
    ],
    passwordPolicy: {
        min_length: 8,
        max_length: 30,
    },
    menuConfig: {
        settingsText: ''
    }
}

const dummyUserActions = {
    "total": 1,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "data": [
        {
            "id": 10428,
            "created_at": 1607162809,
            "updated_at": 1607162809,
            "realm": "https:\/\/infinityfestival2020.fnvirtual.app\/auth\/callback?BackUrl=%252Fa%252F",
            "user_action": "LOGIN",
            "from_ip": "80.5.135.101"
        }
    ]
}


jest.mock('../actions', () => {
    return {
        __esModule: true,
        getUserActions: jest.fn(() => Promise.resolve(dummyUserActions)),
        save: jest.fn(() => Promise.resolve()),
        PAGE_SIZE: 50
    };
});

const mockChildComponent = jest.fn();
jest.mock('../../components/user_actions_grid', () => (props) => {
    mockChildComponent(props);
    return <div/>;
})

afterEach(() => {
    jest.clearAllMocks();
});

describe('ProfilePage', () => {
    test('form is populated', () => {
        render(<ProfilePage {...props} />)
        expect(screen.getByRole('textbox', {name: /first name/i})).toHaveValue()
        expect(screen.getByRole('textbox', {name: /last name/i})).toHaveValue()
        expect(screen.getByRole('textbox', {name: 'Email'})).toHaveValue()
    })

    test("user actions grid component is called once", () => {
        render(<ProfilePage {...props} />)
        expect(mockChildComponent).toHaveBeenCalledTimes(1);
    });

    test('submitting the form with empty fields shows validation error', async () => {
        const {getByRole, getByText} = render(<ProfilePage {...props} />)

        const input = getByRole('textbox', {name: /first name/i})
        fireEvent.change(input, {target: {value: ''}});

        const submitButton = getByText('Save');
        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(getByText('First name is required')).toBeInTheDocument();
        });
    });

    test('change password panel shows on change password link clicked', async () => {
        const {getByRole} = render(<ProfilePage {...props} />)

        const changePasswordLink = getByRole('link', {name: /change password/i})
        fireEvent.click(changePasswordLink);

        await waitFor(() => {
            expect(screen.getByLabelText(/new password/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/confirm password/i)).toBeInTheDocument();
        });
    });
})

