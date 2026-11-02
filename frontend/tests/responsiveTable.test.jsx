import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ResponsiveTable } from '@/components/ui/ResponsiveTable';

describe('responsive table', () => {
  it('labels each mobile card value from its desktop column header', () => {
    render(
      <ResponsiveTable>
        <thead><tr><th>Business</th><th>Owner</th><th>Actions</th></tr></thead>
        <tbody><tr><td>Demo Services</td><td>Josh</td><td><button type="button">View</button></td></tr></tbody>
      </ResponsiveTable>,
    );

    expect(screen.getByText('Demo Services').closest('td')).toHaveAttribute('data-label', 'Business');
    expect(screen.getByText('Josh').closest('td')).toHaveAttribute('data-label', 'Owner');
    expect(screen.getByRole('button', { name: 'View' }).closest('td')).toHaveAttribute('data-label', 'Actions');
    expect(screen.getByRole('table')).toHaveClass('webis-responsive-table');
  });
});
