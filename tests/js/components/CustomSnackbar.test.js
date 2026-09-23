import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import CustomSnackbar from '../../../resources/js/components/custom_snackbar';

describe('CustomSnackbar', () => {
  it('renders message when open', () => {
    render(<CustomSnackbar message="Something went wrong" severity="error" onClose={() => {}} />);
    expect(screen.getByText('Something went wrong')).toBeInTheDocument();
  });

  it('is not visible when message is null', () => {
    render(<CustomSnackbar message={null} severity="info" onClose={() => {}} />);
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  it('calls onClose when close button is clicked', async () => {
    const onClose = jest.fn();
    render(<CustomSnackbar message="Info message" severity="info" onClose={onClose} />);
    await userEvent.click(screen.getByRole('button'));
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('defaults severity to info', () => {
    render(<CustomSnackbar message="Hello" onClose={() => {}} />);
    expect(screen.getByText('Hello')).toBeInTheDocument();
  });
});
