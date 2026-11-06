import React from 'react';
import { render, screen, fireEvent, act } from '@testing-library/react';

jest.mock('@marsidev/react-turnstile', () => require('../helpers/turnstile_mock'));

import { mockTurnstile } from '../helpers/turnstile_mock';
import {
  TurnstileCaptcha,
  useTurnstileCaptcha,
  CAPTCHA_LOAD_TIMEOUT_MS,
  CAPTCHA_MESSAGES,
  TURNSTILE_SCRIPT_ID,
} from '../../../resources/js/components/turnstile_captcha';

// Minimal Turnstile-protected form, wired the way the auth pages wire it.
const ProtectedForm = ({ siteKey, onPost }) => {
  const captcha = useTurnstileCaptcha(siteKey);
  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        if (captcha.ensureToken()) onPost();
      }}
    >
      <TurnstileCaptcha captcha={captcha} errorClassName="error" />
      <button type="submit">Submit</button>
    </form>
  );
};

const submit = () => fireEvent.click(screen.getByRole('button', { name: 'Submit' }));
const retryButton = () => screen.queryByRole('button', { name: /retry security check/i });
const solve = () => {
  mockTurnstile.token = 'turnstile-token';
  act(() => mockTurnstile.props.onSuccess('turnstile-token'));
};

describe('TurnstileCaptcha / useTurnstileCaptcha', () => {
  let onPost;

  beforeEach(() => {
    mockTurnstile.clear();
    onPost = jest.fn();
    // api.js loaded: the global Turnstile API exists (cases below delete it when needed).
    window.turnstile = {};
  });

  afterEach(() => {
    jest.useRealTimers();
    delete window.turnstile;
    document.getElementById(TURNSTILE_SCRIPT_ID)?.remove();
  });

  it('asks the user to wait, instead of to "check the captcha", when submitted before a token exists', () => {
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    submit();

    expect(screen.getByRole('alert')).toHaveTextContent(CAPTCHA_MESSAGES.VERIFYING);
    expect(screen.queryByText(/remember to check the captcha/i)).not.toBeInTheDocument();
    expect(onPost).not.toHaveBeenCalled();
  });

  it('lets the form through and clears the waiting message once a token is issued', () => {
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    submit();
    solve();
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();

    submit();
    expect(onPost).toHaveBeenCalledTimes(1);
  });

  it('posts the response under the field name the backend validates', () => {
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    expect(mockTurnstile.props.siteKey).toBe('site-key');
    expect(mockTurnstile.props.options).toEqual({ responseFieldName: 'cf-turnstile-response' });
  });

  it('reports a failed security check with a retry when no token arrives (iframe never loaded)', () => {
    jest.useFakeTimers();
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    act(() => jest.advanceTimersByTime(CAPTCHA_LOAD_TIMEOUT_MS - 1));
    expect(screen.queryByText(CAPTCHA_MESSAGES.FAILED)).not.toBeInTheDocument();

    act(() => jest.advanceTimersByTime(1));
    expect(screen.getByRole('alert')).toHaveTextContent(CAPTCHA_MESSAGES.FAILED);

    fireEvent.click(retryButton());
    expect(mockTurnstile.reset).toHaveBeenCalledTimes(1);
    expect(screen.queryByText(CAPTCHA_MESSAGES.FAILED)).not.toBeInTheDocument();
    expect(retryButton()).not.toBeInTheDocument();

    // The retried widget gets a fresh timeout window.
    act(() => jest.advanceTimersByTime(CAPTCHA_LOAD_TIMEOUT_MS));
    expect(screen.getByText(CAPTCHA_MESSAGES.FAILED)).toBeInTheDocument();
  });

  it('reloads api.js on retry when the script itself never loaded', () => {
    jest.useFakeTimers();
    delete window.turnstile;
    const failedScript = document.createElement('script');
    failedScript.id = TURNSTILE_SCRIPT_ID;
    document.head.appendChild(failedScript);
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);
    expect(mockTurnstile.mounts).toBe(1);

    act(() => jest.advanceTimersByTime(CAPTCHA_LOAD_TIMEOUT_MS));
    fireEvent.click(retryButton());

    // The failed <script> is dropped and the widget remounted, so the library injects
    // api.js again; reset() would be a no-op without a widget.
    expect(document.getElementById(TURNSTILE_SCRIPT_ID)).toBeNull();
    expect(mockTurnstile.mounts).toBe(2);
    expect(mockTurnstile.reset).not.toHaveBeenCalled();
    expect(screen.queryByText(CAPTCHA_MESSAGES.FAILED)).not.toBeInTheDocument();

    // The reloaded widget can still succeed and unblock the form.
    solve();
    submit();
    expect(onPost).toHaveBeenCalledTimes(1);
  });

  it('does not report a load failure while the user is completing an interactive challenge', () => {
    jest.useFakeTimers();
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    act(() => mockTurnstile.props.onBeforeInteractive());
    act(() => jest.advanceTimersByTime(CAPTCHA_LOAD_TIMEOUT_MS * 2));

    expect(screen.queryByText(CAPTCHA_MESSAGES.FAILED)).not.toBeInTheDocument();
    submit();
    expect(onPost).not.toHaveBeenCalled();
    expect(screen.getByRole('alert')).toHaveTextContent(CAPTCHA_MESSAGES.INTERACTIVE);

    solve();
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  it('re-arms the load timeout when the widget restarts after an interactive challenge', () => {
    jest.useFakeTimers();
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    act(() => mockTurnstile.props.onBeforeInteractive());
    act(() => mockTurnstile.props.onTimeout());
    act(() => jest.advanceTimersByTime(CAPTCHA_LOAD_TIMEOUT_MS));

    expect(screen.getByText(CAPTCHA_MESSAGES.FAILED)).toBeInTheDocument();
  });

  it('does not report a failure once a token arrived before the timeout', () => {
    jest.useFakeTimers();
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    solve();
    act(() => jest.advanceTimersByTime(CAPTCHA_LOAD_TIMEOUT_MS * 2));

    expect(screen.queryByText(CAPTCHA_MESSAGES.FAILED)).not.toBeInTheDocument();
  });

  it('reports a failed security check with a retry when Turnstile raises an error', () => {
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    act(() => mockTurnstile.props.onError('200500'));

    expect(screen.getByRole('alert')).toHaveTextContent(CAPTCHA_MESSAGES.FAILED);
    expect(retryButton()).toBeInTheDocument();
  });

  it('recovers if Turnstile succeeds on its own after an error', () => {
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    act(() => mockTurnstile.props.onError('200500'));
    solve();

    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    expect(retryButton()).not.toBeInTheDocument();
  });

  it('re-runs the widget when the token expires and blocks the post until a new one arrives', () => {
    render(<ProtectedForm siteKey="site-key" onPost={onPost} />);

    solve();
    mockTurnstile.token = undefined;
    act(() => mockTurnstile.props.onExpire());

    expect(mockTurnstile.reset).toHaveBeenCalledTimes(1);
    submit();
    expect(onPost).not.toHaveBeenCalled();
    expect(screen.getByRole('alert')).toHaveTextContent(CAPTCHA_MESSAGES.VERIFYING);
  });

  it('renders nothing and never blocks the post when no site key is configured', () => {
    jest.useFakeTimers();
    render(<ProtectedForm siteKey="" onPost={onPost} />);

    expect(screen.queryByTestId('turnstile')).not.toBeInTheDocument();
    act(() => jest.advanceTimersByTime(CAPTCHA_LOAD_TIMEOUT_MS * 2));
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();

    submit();
    expect(onPost).toHaveBeenCalledTimes(1);
  });
});
