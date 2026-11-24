import React from 'react';
import {render, screen, fireEvent} from '@testing-library/react';
import RecoveryCodeDisplay from '../../../resources/js/components/recovery_code_display';

const CODES = ['AAAA-1111', 'BBBB-2222', 'CCCC-3333'];

describe('RecoveryCodeDisplay', () => {
    beforeEach(() => {
        Object.assign(navigator, {
            clipboard: {
                writeText: jest.fn().mockResolvedValue(undefined),
            },
        });
        global.URL.createObjectURL = jest.fn(() => 'blob:mock-url');
        global.URL.revokeObjectURL = jest.fn();
    });

    it('renders every recovery code', () => {
        render(<RecoveryCodeDisplay codes={CODES} email="user@test.com"/>);

        CODES.forEach((code) => {
            expect(screen.getByText(code)).toBeInTheDocument();
        });
    });

    it('copies all codes as plain text to the clipboard', async () => {
        render(<RecoveryCodeDisplay codes={CODES} email="user@test.com"/>);

        fireEvent.click(screen.getByTestId('copy-codes-button'));

        expect(navigator.clipboard.writeText).toHaveBeenCalledWith(CODES.join('\n'));
    });

    it('triggers a text file download containing the codes', () => {
        const clickSpy = jest.fn();
        const originalCreateElement = document.createElement.bind(document);
        const createElementSpy = jest.spyOn(document, 'createElement').mockImplementation((tag) => {
            const el = originalCreateElement(tag);
            if (tag === 'a') {
                el.click = clickSpy;
            }
            return el;
        });

        render(<RecoveryCodeDisplay codes={CODES} email="user@test.com"/>);
        fireEvent.click(screen.getByTestId('download-codes-button'));

        expect(global.URL.createObjectURL).toHaveBeenCalled();
        expect(clickSpy).toHaveBeenCalled();

        createElementSpy.mockRestore();
    });
});
