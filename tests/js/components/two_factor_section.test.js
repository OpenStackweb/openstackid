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
                               recoveryCodesAcknowledged={true} email="user@test.com"/>
        );
        expect(screen.queryByTestId('enable-two-factor-button')).not.toBeInTheDocument();
        expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 7 of 10 remaining');
    });

    it('shows "not set up yet" instead of a count when the user requires enrollment', () => {
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={10} recoveryCodesTotal={10}
                               recoveryCodesAcknowledged={false} recoveryCodesEnrollmentRequired={true}
                               recoveryCodesEnrollmentAvailable={true} hasPassword={true} email="user@test.com"/>
        );
        expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
        expect(screen.getByTestId('setup-recovery-codes-button')).toBeInTheDocument();
    });

    it('enables 2FA and shows the first batch in the modal, which still has to be confirmed', async () => {
        const codes = ['AAAA-1111', 'BBBB-2222'];
        enableTwoFactor.mockResolvedValue({
            response: {
                recovery_codes: codes,
                batch_id: 12,
                recovery_codes_remaining: 2,
                recovery_codes_total: 2,
                recovery_codes_low_threshold: 3,
                recovery_codes_acknowledged: false,
                recovery_codes_enrollment_required: true,
            },
        });

        render(
            <TwoFactorSection twoFactorEnabled={false} recoveryCodesRemaining={0} recoveryCodesTotal={0}
                               email="user@test.com"/>
        );

        fireEvent.click(screen.getByTestId('enable-two-factor-button'));

        expect(enableTwoFactor).toHaveBeenCalledWith('email_otp');
        expect(await screen.findByText('AAAA-1111')).toBeInTheDocument();
        // Not acknowledged yet: no count behind the modal.
        expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
    });
});
