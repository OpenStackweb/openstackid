import React from 'react';
import {render, screen, fireEvent, waitFor} from '@testing-library/react';
import RecoveryCodesPanel from '../../../resources/js/components/recovery_codes_panel';
import {
    regenerateRecoveryCodes,
    enrollRecoveryCodes,
    acknowledgeRecoveryCodes,
} from '../../../resources/js/profile/actions';

jest.mock('../../../resources/js/profile/actions');
jest.mock('sweetalert2', () => jest.fn());

// data-testid on a MUI TextField lands on the wrapper <div>, not the <input>.
const getPasswordInput = () => screen.getByTestId('recovery-codes-current-password').querySelector('input');

const STATUS_ACKED = (remaining = 10) => ({
    recovery_codes_remaining: remaining,
    recovery_codes_total: 10,
    recovery_codes_low_threshold: 3,
    recovery_codes_acknowledged: true,
    recovery_codes_enrollment_required: false,
});

const GENERATED = (codes, batchId) => ({
    recovery_codes: codes,
    batch_id: batchId,
    recovery_codes_remaining: codes.length,
    recovery_codes_total: codes.length,
    recovery_codes_low_threshold: 3,
    recovery_codes_acknowledged: false,
    recovery_codes_enrollment_required: true,
});

const flush = () => new Promise((resolve) => setImmediate(resolve));

describe('RecoveryCodesPanel', () => {
    beforeEach(() => {
        window.sessionStorage.clear();
        jest.clearAllMocks();
    });

    describe('acknowledged batch', () => {
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
            render(
                <RecoveryCodesPanel recoveryCodesRemaining={2} recoveryCodesTotal={10} lowCodeThreshold={2}
                                     email="user@test.com"/>
            );
            expect(screen.queryByTestId('low-code-warning')).not.toBeInTheDocument();
        });

        it('regenerates after confirming the current password, then acknowledges the batch before updating the panel', async () => {
            const newCodes = ['NEW1-CODE', 'NEW2-CODE'];
            regenerateRecoveryCodes.mockResolvedValue({response: GENERATED(newCodes, 55)});
            acknowledgeRecoveryCodes.mockResolvedValue({response: {...STATUS_ACKED(2), recovery_codes_total: 2}});

            render(
                <RecoveryCodesPanel recoveryCodesRemaining={7} recoveryCodesTotal={10} email="user@test.com"/>
            );

            fireEvent.click(screen.getByText('Regenerate Codes'));
            fireEvent.change(getPasswordInput(), {target: {value: 'my-password'}});
            fireEvent.click(screen.getByTestId('confirm-regenerate-button'));

            expect(regenerateRecoveryCodes).toHaveBeenCalledWith('my-password');
            expect(await screen.findByText('NEW1-CODE')).toBeInTheDocument();
            // Not confirmed yet: no "N remaining" for a batch the user has not acknowledged.
            expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
            expect(acknowledgeRecoveryCodes).not.toHaveBeenCalled();

            await waitFor(() => expect(screen.getByTestId('acknowledge-codes-button')).not.toBeDisabled(), {timeout: 7000});
            fireEvent.click(screen.getByTestId('acknowledge-codes-button'));

            expect(acknowledgeRecoveryCodes).toHaveBeenCalledWith(55);
            await waitFor(() => expect(screen.getByTestId('recovery-codes-count'))
                .toHaveTextContent('Recovery Codes: 2 of 2 remaining'));
            expect(screen.queryByText('NEW1-CODE')).not.toBeInTheDocument();
        }, 15000);

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
            await flush();
            expect(screen.getByTestId('recovery-codes-count')).toHaveTextContent('Recovery Codes: 7 of 10 remaining');
        });
    });

    describe('acknowledged batch, user without a password', () => {
        const props = {recoveryCodesRemaining: 2, recoveryCodesTotal: 10, hasPassword: false, email: 'user@test.com'};

        it('inside the window: regenerates without asking for a password', async () => {
            regenerateRecoveryCodes.mockResolvedValue({response: GENERATED(['AAAA-1111'], 9)});

            render(<RecoveryCodesPanel {...props} recoveryCodesWindowOpen={true}/>);

            expect(screen.queryByTestId('recovery-codes-current-password')).not.toBeInTheDocument();
            fireEvent.click(screen.getByText('Regenerate Codes'));

            expect(regenerateRecoveryCodes).toHaveBeenCalledWith(null);
            expect(await screen.findByText('AAAA-1111')).toBeInTheDocument();
        });

        it('outside the window: explains how to regenerate and offers no action', () => {
            render(<RecoveryCodesPanel {...props} recoveryCodesWindowOpen={false}/>);

            expect(screen.queryByText('Regenerate Codes')).not.toBeInTheDocument();
            expect(screen.getByTestId('recovery-codes-trusted-devices-note'))
                .toHaveTextContent('revoke this device in Trusted Devices, sign out and sign in again');
        });
    });

    describe('no usable batch (enrollment required)', () => {
        const base = {
            recoveryCodesRemaining: 10,
            recoveryCodesTotal: 10,
            recoveryCodesAcknowledged: false,
            recoveryCodesEnrollmentRequired: true,
            email: 'user@test.com',
        };

        it('never shows "10 of 10 remaining" for an unacknowledged batch', () => {
            render(<RecoveryCodesPanel {...base} hasPassword={true}/>);

            expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
            expect(screen.getByTestId('recovery-codes-not-set-up')).toBeInTheDocument();
        });

        it('available: offers "Set up recovery codes" and does not enroll on load', () => {
            render(<RecoveryCodesPanel {...base} hasPassword={false} recoveryCodesEnrollmentAvailable={true}/>);

            expect(screen.getByTestId('recovery-codes-not-set-up')).toHaveTextContent('Your recovery codes are not set up yet.');
            expect(screen.getByTestId('setup-recovery-codes-button')).toBeInTheDocument();
            expect(enrollRecoveryCodes).not.toHaveBeenCalled();
        });

        it('available: enrolls only when the user clicks, shows the modal and acknowledges the batch', async () => {
            enrollRecoveryCodes.mockResolvedValue({response: GENERATED(['EEEE-0001', 'EEEE-0002'], 71)});
            acknowledgeRecoveryCodes.mockResolvedValue({response: {...STATUS_ACKED(10)}});

            render(<RecoveryCodesPanel {...base} hasPassword={true} recoveryCodesEnrollmentAvailable={true}/>);
            fireEvent.click(screen.getByTestId('setup-recovery-codes-button'));

            expect(enrollRecoveryCodes).toHaveBeenCalledTimes(1);
            expect(await screen.findByText('EEEE-0001')).toBeInTheDocument();

            await waitFor(() => expect(screen.getByTestId('acknowledge-codes-button')).not.toBeDisabled(), {timeout: 7000});
            fireEvent.click(screen.getByTestId('acknowledge-codes-button'));

            expect(acknowledgeRecoveryCodes).toHaveBeenCalledWith(71);
            // The count comes from the server's status keys, not from a hardcoded 10.
            await waitFor(() => expect(screen.getByTestId('recovery-codes-count'))
                .toHaveTextContent('Recovery Codes: 10 of 10 remaining'));
        }, 15000);

        it('available: a failed enroll falls back to the "not set up yet" state', async () => {
            enrollRecoveryCodes.mockRejectedValue({status: 412});

            render(<RecoveryCodesPanel {...base} hasPassword={true} recoveryCodesEnrollmentAvailable={true}/>);
            fireEvent.click(screen.getByTestId('setup-recovery-codes-button'));
            await flush();

            expect(screen.queryByTestId('setup-recovery-codes-button')).not.toBeInTheDocument();
            expect(screen.getByTestId('recovery-codes-not-set-up'))
                .toHaveTextContent('Regenerate them to get a set you can save.');
            expect(screen.getByText('Regenerate Codes')).toBeInTheDocument();
        });

        it('available: an enroll body without codes counts as a failure', async () => {
            enrollRecoveryCodes.mockResolvedValue({response: {recovery_codes: [], batch_id: 1}});

            render(<RecoveryCodesPanel {...base} hasPassword={false} recoveryCodesEnrollmentAvailable={true}/>);
            fireEvent.click(screen.getByTestId('setup-recovery-codes-button'));
            await flush();

            expect(screen.queryByTestId('acknowledge-codes-button')).not.toBeInTheDocument();
            expect(screen.getByTestId('recovery-codes-not-set-up'))
                .toHaveTextContent('Sign out and sign in again to set them up.');
        });

        it('not available, has password: asks to regenerate', () => {
            render(<RecoveryCodesPanel {...base} hasPassword={true} recoveryCodesEnrollmentAvailable={false}/>);

            expect(screen.getByTestId('recovery-codes-not-set-up'))
                .toHaveTextContent('Your recovery codes are not set up yet. Regenerate them to get a set you can save.');
            expect(screen.getByText('Regenerate Codes')).toBeInTheDocument();
        });

        it('not available, no password: asks to sign out and in again, with no regenerate action', () => {
            render(<RecoveryCodesPanel {...base} hasPassword={false} recoveryCodesEnrollmentAvailable={false}/>);

            expect(screen.getByTestId('recovery-codes-not-set-up'))
                .toHaveTextContent('Your recovery codes are not set up yet. Sign out and sign in again to set them up.');
            expect(screen.queryByText('Regenerate Codes')).not.toBeInTheDocument();
        });

        it('acknowledge 412 (batch replaced): closes the modal and falls back to "not set up yet"', async () => {
            enrollRecoveryCodes.mockResolvedValue({response: GENERATED(['EEEE-0001'], 71)});
            acknowledgeRecoveryCodes.mockRejectedValue({status: 412});

            render(<RecoveryCodesPanel {...base} hasPassword={true} recoveryCodesEnrollmentAvailable={true}/>);
            fireEvent.click(screen.getByTestId('setup-recovery-codes-button'));
            await screen.findByText('EEEE-0001');
            await waitFor(() => expect(screen.getByTestId('acknowledge-codes-button')).not.toBeDisabled(), {timeout: 7000});

            fireEvent.click(screen.getByTestId('acknowledge-codes-button'));

            await waitFor(() => expect(screen.queryByText('EEEE-0001')).not.toBeInTheDocument());
            expect(screen.getByTestId('recovery-codes-not-set-up')).toBeInTheDocument();
            expect(screen.queryByTestId('recovery-codes-count')).not.toBeInTheDocument();
        }, 15000);

        it('acknowledge failure: keeps the modal open with the error and a Retry', async () => {
            enrollRecoveryCodes.mockResolvedValue({response: GENERATED(['EEEE-0001'], 71)});
            acknowledgeRecoveryCodes.mockRejectedValueOnce({status: 500});
            acknowledgeRecoveryCodes.mockResolvedValueOnce({response: STATUS_ACKED(10)});

            render(<RecoveryCodesPanel {...base} hasPassword={true} recoveryCodesEnrollmentAvailable={true}/>);
            fireEvent.click(screen.getByTestId('setup-recovery-codes-button'));
            await screen.findByText('EEEE-0001');
            await waitFor(() => expect(screen.getByTestId('acknowledge-codes-button')).not.toBeDisabled(), {timeout: 7000});

            fireEvent.click(screen.getByTestId('acknowledge-codes-button'));

            expect(await screen.findByTestId('recovery-code-modal-error')).toBeInTheDocument();
            expect(screen.getByText('EEEE-0001')).toBeInTheDocument();

            fireEvent.click(screen.getByTestId('retry-acknowledge-button'));

            expect(acknowledgeRecoveryCodes).toHaveBeenCalledTimes(2);
            await waitFor(() => expect(screen.getByTestId('recovery-codes-count')).toBeInTheDocument());
        }, 15000);
    });

    describe('first batch of a self-enrollment', () => {
        it('opens the modal immediately when initialBatch is provided and acknowledges it', async () => {
            acknowledgeRecoveryCodes.mockResolvedValue({response: {...STATUS_ACKED(2), recovery_codes_total: 2}});

            render(
                <RecoveryCodesPanel
                    recoveryCodesRemaining={2}
                    recoveryCodesTotal={2}
                    recoveryCodesAcknowledged={false}
                    recoveryCodesEnrollmentRequired={true}
                    email="user@test.com"
                    initialBatch={{codes: ['AAAA-1111', 'BBBB-2222'], batchId: 3}}/>
            );
            expect(screen.getByText('AAAA-1111')).toBeInTheDocument();

            await waitFor(() => expect(screen.getByTestId('acknowledge-codes-button')).not.toBeDisabled(), {timeout: 7000});
            fireEvent.click(screen.getByTestId('acknowledge-codes-button'));

            expect(acknowledgeRecoveryCodes).toHaveBeenCalledWith(3);
            await waitFor(() => expect(screen.getByTestId('recovery-codes-count'))
                .toHaveTextContent('Recovery Codes: 2 of 2 remaining'));
        }, 15000);
    });
});
