// Test double for @marsidev/react-turnstile. Use it with:
//   jest.mock('@marsidev/react-turnstile', () => require('<relative path>/helpers/turnstile_mock'));
// It records the callbacks the page wires up (so a test can fire onSuccess / onError) and
// exposes the imperative handle (getResponse / reset) the page reads through its ref.
import React, { forwardRef, useEffect, useImperativeHandle } from 'react';

export const mockTurnstile = {
  props: null,
  token: undefined,
  mounts: 0,
  reset: jest.fn(),
  clear() {
    this.props = null;
    this.token = undefined;
    this.mounts = 0;
    this.reset.mockClear();
  },
};

export const Turnstile = forwardRef((props, ref) => {
  mockTurnstile.props = props;
  useEffect(() => {
    mockTurnstile.mounts += 1;
  }, []);
  useImperativeHandle(ref, () => ({
    getResponse: () => mockTurnstile.token,
    reset: mockTurnstile.reset,
  }));
  return <div data-testid="turnstile" />;
});
