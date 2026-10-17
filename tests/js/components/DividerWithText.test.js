import React from 'react';
import { render, screen } from '@testing-library/react';
import DividerWithText from '../../../resources/js/components/divider_with_text';

describe('DividerWithText', () => {
  it('renders children text', () => {
    render(<DividerWithText>or</DividerWithText>);
    expect(screen.getByText('or')).toBeInTheDocument();
  });

  it('renders any string children', () => {
    render(<DividerWithText>Sign in with</DividerWithText>);
    expect(screen.getByText('Sign in with')).toBeInTheDocument();
  });
});
