import React from 'react';
import {render, screen, fireEvent, waitFor} from '@testing-library/react';
import TwoFactorSection from '../../../resources/js/components/two_factor_section';
import {enableTwoFactor, disableTwoFactor} from '../../../resources/js/profile/actions';
import {handleErrorResponse} from '../../../resources/js/utils';

jest.mock('../../../resources/js/profile/actions');
jest.mock('sweetalert2', () => jest.fn());
// the real handler resolves the message and hands it to the custom handler when one is given
jest.mock('../../../resources/js/utils', () => ({
    handleErrorResponse: jest.fn((err, handler) => handler && handler('Validation error', ['current_password: is not correct'], 'warning')),
}));

describe('TwoFactorSection', () => {
    beforeEach(() => {
        window.sessionStorage.clear();
        jest.clearAllMocks();
    });

    it('shows the switch off when 2FA is not enabled', () => {
        render(
            <TwoFactorSection twoFactorEnabled={false} recoveryCodesRemaining={0} recoveryCodesTotal={0}
                               email="user@test.com"/>
        );
        expect(screen.getByTestId('two-factor-switch')).not.toBeChecked();
        expect(screen.getByTestId('two-factor-switch')).not.toBeDisabled();
        expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
    });

    it('shows the recovery codes panel when 2FA is already enabled', () => {
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={7} recoveryCodesTotal={10}
                               recoveryCodesAcknowledged={true} email="user@test.com"/>
        );
        expect(screen.getByTestId('two-factor-switch')).toBeChecked();
        expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 7 of 10 remaining');
    });

    it('treats a group-enforced user as enabled even when the stored flag is off', () => {
        render(
            <TwoFactorSection twoFactorEnabled={false} twoFactorEnforced={true} recoveryCodesRemaining={0}
                               recoveryCodesTotal={0} recoveryCodesAcknowledged={true} email="admin@test.com"/>
        );
        expect(screen.getByTestId('two-factor-switch')).toBeChecked();
        expect(screen.getByTestId('two-factor-switch')).toBeDisabled();
        expect(screen.getByTestId('recovery-codes-count')).toBeInTheDocument();
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

        fireEvent.click(screen.getByTestId('two-factor-switch'));

        expect(enableTwoFactor).toHaveBeenCalledWith('email_otp');
        expect(await screen.findByText('AAAA-1111')).toBeInTheDocument();
        // Not acknowledged yet: no count behind the modal.
        expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
    });

    it('renders the switch checked and disabled with the explanation for a group-enforced user', () => {
        render(
            <TwoFactorSection twoFactorEnabled={false} twoFactorEnforced={true} recoveryCodesRemaining={0}
                               recoveryCodesTotal={0} email="admin@test.com"/>
        );
        const toggle = screen.getByTestId('two-factor-switch');
        expect(toggle).toBeChecked();
        expect(toggle).toBeDisabled();
        expect(screen.getByTestId('two-factor-enforced-message')).toHaveTextContent(
            'Two-factor authentication is required for members of an administrator group and cannot be turned off.'
        );
        fireEvent.click(toggle);
        expect(screen.queryByTestId('disable-two-factor-dialog')).not.toBeInTheDocument();
    });

    it('does not show the enforced explanation to a self-enrolled user', () => {
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={7} recoveryCodesTotal={10}
                               email="user@test.com"/>
        );
        expect(screen.queryByTestId('two-factor-enforced-message')).not.toBeInTheDocument();
        expect(screen.getByTestId('two-factor-switch')).not.toBeDisabled();
    });

    it('turning the switch off asks for the password, disables 2FA and hides the recovery codes', async () => {
        disableTwoFactor.mockResolvedValue({response: {two_factor_enabled: false}});
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={7} recoveryCodesTotal={10}
                               hasPassword={true} email="user@test.com"/>
        );

        fireEvent.click(screen.getByTestId('two-factor-switch'));

        expect(await screen.findByTestId('disable-two-factor-dialog')).toBeInTheDocument();
        expect(disableTwoFactor).not.toHaveBeenCalled();
        expect(screen.getByTestId('confirm-disable-two-factor-button')).toBeDisabled();

        fireEvent.change(screen.getByTestId('disable-two-factor-current-password').querySelector('input'),
            {target: {value: 'secret'}});
        fireEvent.click(screen.getByTestId('confirm-disable-two-factor-button'));

        await waitFor(() => expect(disableTwoFactor).toHaveBeenCalledWith('secret'));
        await waitFor(() => expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument());
        expect(screen.getByTestId('two-factor-switch')).not.toBeChecked();
        expect(screen.getByTestId('two-factor-disabled-message')).toBeInTheDocument();
    });

    it('keeps the dialog open, shows the error inside it and keeps 2FA on when the password is rejected', async () => {
        disableTwoFactor.mockRejectedValue({status: 412});
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={7} recoveryCodesTotal={10}
                               hasPassword={true} email="user@test.com"/>
        );

        fireEvent.click(screen.getByTestId('two-factor-switch'));
        fireEvent.change((await screen.findByTestId('disable-two-factor-current-password')).querySelector('input'),
            {target: {value: 'wrong'}});
        fireEvent.click(screen.getByTestId('confirm-disable-two-factor-button'));

        await waitFor(() => expect(disableTwoFactor).toHaveBeenCalledWith('wrong'));
        expect(handleErrorResponse).toHaveBeenCalled();
        // shown inside the dialog: a SweetAlert would open below it
        expect(await screen.findByTestId('disable-two-factor-error')).toHaveTextContent('current_password: is not correct');
        expect(screen.getByTestId('disable-two-factor-dialog')).toBeInTheDocument();
        expect(screen.getByTestId('two-factor-switch')).toBeChecked();
        expect(screen.getByTestId('recovery-codes-count')).toBeInTheDocument();
    });

    it('cancelling the dialog leaves 2FA on and sends nothing', async () => {
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={7} recoveryCodesTotal={10}
                               hasPassword={true} email="user@test.com"/>
        );

        fireEvent.click(screen.getByTestId('two-factor-switch'));
        fireEvent.click(await screen.findByTestId('cancel-disable-two-factor-button'));

        expect(disableTwoFactor).not.toHaveBeenCalled();
        expect(screen.getByTestId('two-factor-switch')).toBeChecked();
    });

    it('a user without a password confirms without typing one', async () => {
        disableTwoFactor.mockResolvedValue({response: {two_factor_enabled: false}});
        render(
            <TwoFactorSection twoFactorEnabled={true} recoveryCodesRemaining={7} recoveryCodesTotal={10}
                               hasPassword={false} recoveryCodesWindowOpen={true} email="user@test.com"/>
        );

        fireEvent.click(screen.getByTestId('two-factor-switch'));

        expect(await screen.findByTestId('disable-two-factor-dialog')).toBeInTheDocument();
        expect(screen.queryByTestId('disable-two-factor-current-password')).not.toBeInTheDocument();
        fireEvent.click(screen.getByTestId('confirm-disable-two-factor-button'));

        await waitFor(() => expect(disableTwoFactor).toHaveBeenCalledWith(null));
    });
});
