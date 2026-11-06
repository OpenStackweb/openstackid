import React from 'react';
import {render, screen, fireEvent} from '@testing-library/react';
import RecoveryCodesPanel from '../../../resources/js/components/recovery_codes_panel';
import {regenerateRecoveryCodes} from '../../../resources/js/profile/actions';

jest.mock('../../../resources/js/profile/actions');
jest.mock('sweetalert2', () => jest.fn());

// data-testid on a MUI TextField lands on the wrapper <div>, not the <input>.
const getPasswordInput = () => screen.getByTestId('recovery-codes-current-password').querySelector('input');

describe('RecoveryCodesPanel', () => {
    beforeEach(() => {
        window.sessionStorage.clear();
        jest.clearAllMocks();
    });

    it('shows the remaining/total count', () => {
        render(
            <RecoveryCodesPanel recoveryCodesRemaining={7} recoveryCodesTotal={10} email="user@test.com"/>
        );
        expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 7 of 10 remaining');
    });

    it('shows a dismissable low-code warning when remaining is below the threshold', () => {
        render(
            <RecoveryCodesPanel recoveryCodesRemaining={2} recoveryCodesTotal={10} email="user@test.com"/>
        );
        expect(screen.getByTestId('low-code-warning')).toBeInTheDocument();

        fireEvent.click(screen.getByLabelText('dismiss'));
        expect(screen.queryByTestId('low-code-warning')).not.toBeInTheDocument();
    });

    it('does not show the low-code warning when there are enough codes', () => {
        render(
            <RecoveryCodesPanel recoveryCodesRemaining={9} recoveryCodesTotal={10} email="user@test.com"/>
        );
        expect(screen.queryByTestId('low-code-warning')).not.toBeInTheDocument();
    });

    it('respects a custom lowCodeThreshold instead of the default of 3', () => {
        // 4 remaining would NOT trigger the default threshold (3), but does with a custom threshold of 5.
        render(
            <RecoveryCodesPanel recoveryCodesRemaining={4} recoveryCodesTotal={10} lowCodeThreshold={5}
                                 email="user@test.com"/>
        );
        expect(screen.getByTestId('low-code-warning')).toBeInTheDocument();
    });

    it('respects a custom lower threshold (2 remaining is not below a threshold of 2)', () => {
        // With the default threshold (3) this would show the warning; a custom
        // threshold of 2 must be honored instead of the hardcoded default.
        render(
            <RecoveryCodesPanel recoveryCodesRemaining={2} recoveryCodesTotal={10} lowCodeThreshold={2}
                                 email="user@test.com"/>
        );
        expect(screen.queryByTestId('low-code-warning')).not.toBeInTheDocument();
    });

    it('opens the modal immediately when initialCodes is provided', () => {
        const codes = ['AAAA-1111', 'BBBB-2222'];
        render(
            <RecoveryCodesPanel recoveryCodesRemaining={2} recoveryCodesTotal={2} email="user@test.com"
                                 initialCodes={codes}/>
        );
        expect(screen.getByText('AAAA-1111')).toBeInTheDocument();
    });

    it('regenerates codes after confirming the current password and opens the modal', async () => {
        const newCodes = ['NEW1-CODE', 'NEW2-CODE'];
        regenerateRecoveryCodes.mockResolvedValue({response: {recovery_codes: newCodes}});

        render(
            <RecoveryCodesPanel recoveryCodesRemaining={7} recoveryCodesTotal={10} email="user@test.com"/>
        );

        fireEvent.click(screen.getByText('Regenerate Codes'));
        fireEvent.change(getPasswordInput(), {target: {value: 'my-password'}});
        fireEvent.click(screen.getByTestId('confirm-regenerate-button'));

        expect(regenerateRecoveryCodes).toHaveBeenCalledWith('my-password');
        expect(await screen.findByText('NEW1-CODE')).toBeInTheDocument();
        expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 2 of 2 remaining');
    });

    it('shows an error and keeps the existing count when the password is wrong', async () => {
        regenerateRecoveryCodes.mockRejectedValue({
            status: 412,
            response: {body: {errors: ['current_password is not correct.']}},
        });

        render(
            <RecoveryCodesPanel recoveryCodesRemaining={7} recoveryCodesTotal={10} email="user@test.com"/>
        );

        fireEvent.click(screen.getByText('Regenerate Codes'));
        fireEvent.change(getPasswordInput(), {target: {value: 'wrong'}});
        fireEvent.click(screen.getByTestId('confirm-regenerate-button'));

        expect(regenerateRecoveryCodes).toHaveBeenCalledWith('wrong');
        await new Promise((resolve) => setImmediate(resolve));
        expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 7 of 10 remaining');
    });
});
