import React from 'react';
import {render, screen, fireEvent} from '@testing-library/react';
import TwoFactorCheckbox, {ENFORCED_BY_GROUP_TEXT, OWN_ACCOUNT_TEXT} from '../../../resources/js/admin/edit_user/components/two_factor_checkbox';

describe('admin edit user TwoFactorCheckbox', () => {
    it('reflects the stored flag and can be toggled', () => {
        const onChange = jest.fn();
        const {rerender} = render(<TwoFactorCheckbox enabled={false} onChange={onChange}/>);
        const box = screen.getByTestId('two-factor-checkbox');
        expect(box).not.toBeChecked();
        expect(box).not.toBeDisabled();
        expect(screen.getByText('Two-Factor Authentication (Email OTP)')).toBeInTheDocument();

        fireEvent.click(box);
        expect(onChange).toHaveBeenCalledTimes(1);

        rerender(<TwoFactorCheckbox enabled={true} onChange={onChange}/>);
        expect(screen.getByTestId('two-factor-checkbox')).toBeChecked();
        expect(screen.queryByTestId('two-factor-enforced-text')).not.toBeInTheDocument();
    });

    it('is checked and disabled with the explanation for a group-enforced user, even with the stored flag off', () => {
        const onChange = jest.fn();
        render(<TwoFactorCheckbox enabled={false} enforced={true} onChange={onChange}/>);

        const box = screen.getByTestId('two-factor-checkbox');
        expect(box).toBeChecked();
        expect(box).toBeDisabled();
        expect(screen.getByTestId('two-factor-enforced-text')).toHaveTextContent(ENFORCED_BY_GROUP_TEXT);
        expect(ENFORCED_BY_GROUP_TEXT).toBe('Enforced by group membership');

        fireEvent.click(box);
        expect(onChange).not.toHaveBeenCalled();
    });

    it('is read only on the admin own account, keeping the stored value', () => {
        const onChange = jest.fn();
        render(<TwoFactorCheckbox enabled={true} ownAccount={true} onChange={onChange}/>);

        const box = screen.getByTestId('two-factor-checkbox');
        expect(box).toBeChecked();
        expect(box).toBeDisabled();
        expect(screen.getByTestId('two-factor-own-account-text')).toHaveTextContent(OWN_ACCOUNT_TEXT);
        expect(screen.queryByTestId('two-factor-enforced-text')).not.toBeInTheDocument();

        fireEvent.click(box);
        expect(onChange).not.toHaveBeenCalled();
    });

    it('shows when 2FA was enforced as read only text, only when set', () => {
        const {rerender} = render(<TwoFactorCheckbox enabled={true} enforcedAt=""/>);
        expect(screen.queryByTestId('two-factor-enforced-at')).not.toBeInTheDocument();

        // 2026-07-23 10:30:00 UTC
        rerender(<TwoFactorCheckbox enabled={true} enforcedAt={1784802600}/>);
        expect(screen.getByTestId('two-factor-enforced-at')).toHaveTextContent('Enforced since 2026-07-23 10:30 UTC');
    });
});
