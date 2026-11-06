import React from 'react';
import {render, screen, fireEvent} from '@testing-library/react';
import TwoFactorSection from '../../../resources/js/components/two_factor_section';
import {enableTwoFactor} from '../../../resources/js/profile/actions';

jest.mock('../../../resources/js/profile/actions');
jest.mock('sweetalert2', () => jest.fn());

describe('TwoFactorSection', () => {
    beforeEach(() => {
        window.sessionStorage.clear();
        jest.clearAllMocks();
    });

    it('shows the enable button when 2FA is not enabled', () => {
        render(
            <TwoFactorSection twoFactorEnabled={false} recoveryCodesRemaining={0} recoveryCodesTotal={0}
                               email="user@test.com"/>
        );
        expect(screen.getByTestId('enable-two-factor-button')).toBeInTheDocument();
        expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
    });

    it('shows the recovery codes panel when 2FA is already enabled', () => {
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={7} recoveryCodesTotal={10}
                               email="user@test.com"/>
        );
        expect(screen.queryByTestId('enable-two-factor-button')).not.toBeInTheDocument();
        expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 7 of 10 remaining');
    });

    it('enables 2FA and shows the enrollment codes in the modal', async () => {
        const codes = ['AAAA-1111', 'BBBB-2222'];
        enableTwoFactor.mockResolvedValue({response: {recovery_codes: codes}});

        render(
            <TwoFactorSection twoFactorEnabled={false} recoveryCodesRemaining={0} recoveryCodesTotal={0}
                               email="user@test.com"/>
        );

        fireEvent.click(screen.getByTestId('enable-two-factor-button'));

        expect(enableTwoFactor).toHaveBeenCalledWith('email_otp');
        expect(await screen.findByText('AAAA-1111')).toBeInTheDocument();
        expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 2 of 2 remaining');
    });
});
