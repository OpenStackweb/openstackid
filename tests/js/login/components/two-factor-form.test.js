import React from 'react';
import { render, screen } from '@testing-library/react';
import TwoFactorForm from '../../../../resources/js/login/components/two_factor_form';

// Suppress the 1-second interval so tests don't bleed real timers into each other.
beforeEach(() => jest.useFakeTimers());
afterEach(() => jest.useRealTimers());

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

});
