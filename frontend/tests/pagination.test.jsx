import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Pagination } from '@/components/ui/Pagination';

describe('Pagination', () => {
  it('renders nothing for a single-page result', () => {
    const { container } = render(
      <Pagination meta={{ page: 1, last_page: 1 }} onChange={vi.fn()} />,
    );

    expect(container).toBeEmptyDOMElement();
  });

  it('renders nothing when meta is missing', () => {
    const { container } = render(<Pagination meta={null} onChange={vi.fn()} />);

    expect(container).toBeEmptyDOMElement();
  });

  it('disables Previous on the first page and enables Next', () => {
    render(<Pagination meta={{ page: 1, last_page: 3 }} onChange={vi.fn()} />);

    expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Next' })).toBeEnabled();
    expect(screen.getByText('Page 1 of 3')).toBeInTheDocument();
  });

  it('disables Next on the last page and enables Previous', () => {
    render(<Pagination meta={{ page: 3, last_page: 3 }} onChange={vi.fn()} />);

    expect(screen.getByRole('button', { name: 'Previous' })).toBeEnabled();
    expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled();
  });

  it('calls onChange with the next page number', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();
    render(<Pagination meta={{ page: 2, last_page: 5 }} onChange={onChange} />);

    await user.click(screen.getByRole('button', { name: 'Next' }));
    expect(onChange).toHaveBeenCalledWith(3);

    await user.click(screen.getByRole('button', { name: 'Previous' }));
    expect(onChange).toHaveBeenCalledWith(1);
  });
});
