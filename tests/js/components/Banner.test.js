import React from 'react';
import { render, screen } from '@testing-library/react';
import Banner from '../../../resources/js/components/banner/banner';

describe('Banner', () => {
  it('renders plain text content', () => {
    render(<Banner infoBannerContent="Scheduled maintenance tonight" />);
    expect(screen.getByText('Scheduled maintenance tonight')).toBeInTheDocument();
  });

  it('renders HTML content via dangerouslySetInnerHTML', () => {
    const { container } = render(
      <Banner infoBannerContent="<strong>Important</strong> notice" />
    );
    expect(container.querySelector('strong')).toBeInTheDocument();
    expect(container.querySelector('strong').textContent).toBe('Important');
  });

  it('renders empty content without crashing', () => {
    const { container } = render(<Banner infoBannerContent="" />);
    expect(container).toBeInTheDocument();
  });
});
