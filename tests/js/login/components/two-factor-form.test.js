import React from 'react';
import { render, screen, act, fireEvent, cleanup } from '@testing-library/react';
import TwoFactorForm from '../../../../resources/js/login/components/two_factor_form';

// Suppress the 1-second interval so tests don't bleed real timers into each other.
// Unmount while the fake clock is still installed so the component's
// clearInterval() targets the fake timer it created.
beforeEach(() => jest.useFakeTimers());
afterEach(() => {
  cleanup();
  jest.useRealTimers();
});

const baseProps = {
  otpCode: '123456',
  otpError: '',
  otpLength: 6,
  otpLifetime: 300,
  codeVersion: 0,
  disableInput: false,
  trustDevice: false,
  onCodeChange: jest.fn(),
  onVerify: jest.fn(),
  onTrustDeviceChange: jest.fn(),
  onResend: jest.fn(),
  onUseRecovery: jest.fn(),
  onCancel: jest.fn(),
};

describe('TwoFactorForm', () => {

  it('renders countdown when otpLifetime > 0', () => {
    render(<TwoFactorForm {...baseProps} otpLifetime={300} />);
    // formatTime(300) → "5 minutes"; the paragraph reads "Code expires in 5 minutes."
    expect(screen.getByText(/Code expires in 5 minutes\./)).toBeInTheDocument();
    expect(screen.queryByText(/has expired/)).not.toBeInTheDocument();
  });

  it('renders expired state when otpLifetime is 0', () => {
    render(<TwoFactorForm {...baseProps} otpLifetime={0} />);
    expect(
      screen.getByText(/Your verification code has expired\. Please request a new one\./)
    ).toBeInTheDocument();
    expect(screen.queryByText(/Code expires in/)).not.toBeInTheDocument();
  });

  it('VERIFY button is disabled when otpCode is empty', () => {
    render(<TwoFactorForm {...baseProps} otpCode="" />);
    // MUI Button spreads unknown props to its root <button>; disabled is set on the native element.
    expect(screen.getByTestId('verify-button')).toBeDisabled();
  });

  it('error paragraph renders when otpError is non-empty', () => {
    const msg = 'Invalid or expired verification code. Please try again.';
    render(<TwoFactorForm {...baseProps} otpError={msg} />);
    const label = screen.getByTestId('error-label');
    expect(label).toBeInTheDocument();
    expect(label).toHaveTextContent(msg);
  });

  it('an expired code is revived by a fresh one (codeVersion bump), re-enabling VERIFY', () => {
    const { rerender } = render(<TwoFactorForm {...baseProps} otpLifetime={5} />);
    act(() => { jest.advanceTimersByTime(6000); });
    expect(screen.getByText(/has expired/)).toBeInTheDocument();
    expect(screen.getByTestId('verify-button')).toBeDisabled();

    // Same lifetime, new code: the parent bumps codeVersion on a successful resend.
    rerender(<TwoFactorForm {...baseProps} otpLifetime={5} codeVersion={1} />);
    expect(screen.queryByText(/has expired/)).not.toBeInTheDocument();
    expect(screen.getByTestId('verify-button')).not.toBeDisabled();
  });

  it('a successful resend keeps the cooldown running', async () => {
    const onResend = jest.fn(() => Promise.resolve());
    render(<TwoFactorForm {...baseProps} onResend={onResend} />);

    await act(async () => { fireEvent.click(screen.getByTestId('resend-link')); });

    expect(onResend).toHaveBeenCalledTimes(1);
    expect(screen.getByTestId('resend-link')).toHaveTextContent(/resend code \(30s\)/);
  });

  it('a failed resend releases the cooldown so the user can retry', async () => {
    const onResend = jest.fn(() => Promise.reject(new Error('429')));
    render(<TwoFactorForm {...baseProps} onResend={onResend} />);

    await act(async () => { fireEvent.click(screen.getByTestId('resend-link')); });

    expect(onResend).toHaveBeenCalledTimes(1);
    expect(screen.getByTestId('resend-link')).toHaveTextContent(/^resend code$/);
  });

});
