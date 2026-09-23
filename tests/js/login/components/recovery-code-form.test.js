import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import RecoveryCodeForm from '../../../../resources/js/login/components/recovery_code_form';

const baseProps = {
  recoveryCode: 'ABCD1234',
  recoveryError: '',
  disableInput: false,
  onRecoveryCodeChange: jest.fn(),
  onVerify: jest.fn(),
  onBackToOtp: jest.fn(),
  onCancel: jest.fn(),
};

describe('RecoveryCodeForm', () => {

  beforeEach(() => jest.clearAllMocks());

  it('does not advertise the field as a one-time-code slot', () => {
    // "one-time-code" would make the OS offer the e-mailed OTP here — the
    // wrong credential for this field, and the OTP/recovery confusion risk.
    render(<RecoveryCodeForm {...baseProps} />);
    expect(document.getElementById('recovery_code')).toHaveAttribute('autocomplete', 'off');
  });

  it('renders the expected-format hint', () => {
    render(<RecoveryCodeForm {...baseProps} />);
    expect(screen.getByText(/8 characters, shown as ABCD-1234/)).toBeInTheDocument();
  });

  it('VERIFY button is disabled when the code is empty', () => {
    render(<RecoveryCodeForm {...baseProps} recoveryCode="" />);
    expect(screen.getByTestId('verify-button')).toBeDisabled();
  });

  it('VERIFY button is disabled while a submit is in flight', () => {
    render(<RecoveryCodeForm {...baseProps} disableInput={true} />);
    expect(screen.getByTestId('verify-button')).toBeDisabled();
  });

  it('error paragraph renders when recoveryError is non-empty', () => {
    const msg = 'Invalid recovery code. Please try again.';
    render(<RecoveryCodeForm {...baseProps} recoveryError={msg} />);
    expect(screen.getByTestId('error-label')).toHaveTextContent(msg);
  });

  it('submitting the form calls onVerify without navigating', () => {
    render(<RecoveryCodeForm {...baseProps} />);
    fireEvent.submit(screen.getByTestId('recovery-form'));
    expect(baseProps.onVerify).toHaveBeenCalledTimes(1);
  });

  it('"Back to verification code" calls onBackToOtp', () => {
    render(<RecoveryCodeForm {...baseProps} />);
    fireEvent.click(screen.getByTestId('back-to-otp-link'));
    expect(baseProps.onBackToOtp).toHaveBeenCalledTimes(1);
    expect(baseProps.onCancel).not.toHaveBeenCalled();
  });

  it('"Cancel" calls onCancel', () => {
    render(<RecoveryCodeForm {...baseProps} />);
    fireEvent.click(screen.getByTestId('cancel-link'));
    expect(baseProps.onCancel).toHaveBeenCalledTimes(1);
    expect(baseProps.onBackToOtp).not.toHaveBeenCalled();
  });

  it('typing in the field reports the raw value to the parent', () => {
    // Normalization lives in LoginPage.onRecoveryCodeChange(); the form must
    // hand over the untouched event so that stays the single source of truth.
    // Read the value inside the handler: the field is controlled, so React
    // resets the DOM node back to the (unchanged) prop before the assertion runs.
    let seen = null;
    const onRecoveryCodeChange = jest.fn((ev) => { seen = ev.target.value; });

    render(<RecoveryCodeForm {...baseProps} recoveryCode="" onRecoveryCodeChange={onRecoveryCodeChange} />);
    fireEvent.change(document.getElementById('recovery_code'), { target: { value: 'abcd-1234' } });

    expect(onRecoveryCodeChange).toHaveBeenCalledTimes(1);
    expect(seen).toBe('abcd-1234');
  });
});
