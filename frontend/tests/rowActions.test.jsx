import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { RowActions } from '@/components/ui/RowActions';

describe('compact row actions', () => {
  it('uses neutral accessible icons and preserves the delete handler', async () => {
    const onDelete = vi.fn();
    const record = { id: 3, name: 'Example', is_active: true };
    render(<RowActions record={record} onEdit={vi.fn()} onToggle={vi.fn()} onDelete={onDelete} />);

    const remove = screen.getByRole('button', { name: 'Delete Example' });
    expect(remove.textContent).toBe('');
    expect(remove).toHaveAttribute('title', 'Delete Example');
    expect(remove.querySelector('svg')).toBeInTheDocument();
    expect(remove).not.toHaveClass('bg-red-600');
    await userEvent.setup().click(remove);
    expect(onDelete).toHaveBeenCalledWith(record);
  });
});
