import { afterEach, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { AdminRecordFilters } from '@/components/ui/AdminRecordFilters';

afterEach(() => vi.useRealTimers());

it('sets rolling three and five month ranges using local calendar dates', () => {
  vi.useFakeTimers();
  vi.setSystemTime(new Date(2026, 10, 4, 12));
  const onChange = vi.fn();
  render(<AdminRecordFilters value={{ sort: 'newest', start_date: '', end_date: '' }} onChange={onChange} />);

  fireEvent.change(screen.getByLabelText('Date range'), { target: { value: '3' } });
  expect(onChange).toHaveBeenLastCalledWith({ start_date: '2026-08-04', end_date: '2026-11-04' });
  fireEvent.change(screen.getByLabelText('Date range'), { target: { value: '5' } });
  expect(onChange).toHaveBeenLastCalledWith({ start_date: '2026-06-04', end_date: '2026-11-04' });
});

it('keeps manual dates available and combines page search with filters', () => {
  const onChange = vi.fn();
  render(
    <AdminRecordFilters className="xl:gap-2" value={{ sort: 'newest', start_date: '', end_date: '' }} onChange={onChange}>
      <input aria-label="Search business name" />
    </AdminRecordFilters>,
  );
  const toolbar = screen.getByTestId('admin-filter-toolbar');
  expect(toolbar).toHaveClass('xl:gap-2', 'sm:flex-wrap');
  expect(within(toolbar).getByRole('textbox', { name: 'Search business name' })).toBeInTheDocument();
  expect(screen.queryByLabelText('Created from')).not.toBeInTheDocument();

  fireEvent.change(screen.getByLabelText('Date range'), { target: { value: 'custom' } });
  fireEvent.change(screen.getByLabelText('Created from'), { target: { value: '2026-10-01' } });
  expect(onChange).toHaveBeenLastCalledWith({ start_date: '2026-10-01' });
});
