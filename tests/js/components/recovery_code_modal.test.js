import React from 'react';
import {render, screen, fireEvent, act} from '@testing-library/react';
import RecoveryCodeModal from '../../../resources/js/components/recovery_code_modal';

const CODES = ['AAAA-1111', 'BBBB-2222'];

describe('RecoveryCodeModal', () => {
    beforeEach(() => {
        jest.useFakeTimers();
    });

    afterEach(() => {
        jest.useRealTimers();
    });

    it('renders the title, warning banner and codes when open', () => {
        render(<RecoveryCodeModal open={true} codes={CODES} email="user@test.com" onAcknowledge={jest.fn()}/>);

        expect(screen.getByText('Save Your Recovery Codes')).toBeInTheDocument();
        expect(screen.getByTestId('recovery-code-warning')).toBeInTheDocument();
        CODES.forEach((code) => expect(screen.getByText(code)).toBeInTheDocument());
    });

    it('keeps the acknowledge button disabled until the 5 second delay elapses', () => {
        const onAcknowledge = jest.fn();
        render(<RecoveryCodeModal open={true} codes={CODES} email="user@test.com" onAcknowledge={onAcknowledge}/>);

        const ackButton = screen.getByTestId('acknowledge-codes-button');
        expect(ackButton).toBeDisabled();

        act(() => {
            jest.advanceTimersByTime(4000);
        });
        expect(ackButton).toBeDisabled();

        act(() => {
            jest.advanceTimersByTime(1000);
        });
        expect(ackButton).not.toBeDisabled();

        fireEvent.click(ackButton);
        expect(onAcknowledge).toHaveBeenCalledTimes(1);
    });

    it('cannot be dismissed by pressing Escape', () => {
        const onAcknowledge = jest.fn();
        render(<RecoveryCodeModal open={true} codes={CODES} email="user@test.com" onAcknowledge={onAcknowledge}/>);

        fireEvent.keyDown(screen.getByRole('dialog'), {key: 'Escape', code: 'Escape', keyCode: 27});

        expect(screen.getByText('Save Your Recovery Codes')).toBeInTheDocument();
        expect(onAcknowledge).not.toHaveBeenCalled();
    });

    it('renders nothing sensitive when there are no codes yet (closed state)', () => {
        render(<RecoveryCodeModal open={false} codes={null} email="user@test.com" onAcknowledge={jest.fn()}/>);

        expect(screen.queryByText('Save Your Recovery Codes')).not.toBeInTheDocument();
    });
});
