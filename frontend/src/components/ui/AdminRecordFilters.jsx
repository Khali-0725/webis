import { useState } from 'react';

const localDate = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');

function monthsAgo(today, count) {
  const first = new Date(today.getFullYear(), today.getMonth() - count, 1);
  const lastDay = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
  first.setDate(Math.min(today.getDate(), lastDay));
  return first;
}

export function AdminRecordFilters({ value, onChange, inTrash = false, dateFieldLabel, children }) {
  const [datePreset, setDatePreset] = useState(value.start_date || value.end_date ? 'custom' : 'all');
  const update = (key, next) => onChange({ [key]: next });
  const dateLabel = dateFieldLabel ?? (inTrash ? 'Archived' : 'Created');
  const updatePreset = (next) => {
    setDatePreset(next);
    if (next === 'all') onChange({ start_date: '', end_date: '' });
    if (next === '3' || next === '5') {
      const today = new Date();
      onChange({ start_date: localDate(monthsAgo(today, Number(next))), end_date: localDate(today) });
    }
  };

  return (
    <div data-testid="admin-filter-toolbar" className="mb-4 flex min-w-0 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end 2xl:flex-nowrap print:hidden">
      {children}
      <label className="grid gap-1 text-xs font-medium text-ink-muted">
        Sort records
        <select
          aria-label="Sort records"
          className="h-10 rounded-lg border border-line bg-white px-3 text-sm text-ink"
          value={value.sort ?? 'newest'}
          onChange={(event) => update('sort', event.target.value)}
        >
          <option value="newest">Newest first</option>
          <option value="oldest">Oldest first</option>
        </select>
      </label>
      <label className="grid gap-1 text-xs font-medium text-ink-muted">
        Date range
        <select aria-label="Date range" className="h-10 rounded-lg border border-line bg-white px-3 text-sm text-ink" value={datePreset} onChange={(event) => updatePreset(event.target.value)}>
          <option value="all">All dates</option>
          <option value="3">Last 3 months</option>
          <option value="5">Last 5 months</option>
          <option value="custom">Custom dates</option>
        </select>
      </label>
      {datePreset === 'custom' && (
        <>
          <label className="grid gap-1 text-xs font-medium text-ink-muted">
            {dateLabel} from
            <input
              aria-label={`${dateLabel} from`}
              type="date"
              className="h-10 rounded-lg border border-line bg-white px-3 text-sm text-ink"
              value={value.start_date ?? ''}
              max={value.end_date || undefined}
              onChange={(event) => update('start_date', event.target.value)}
            />
          </label>
          <label className="grid gap-1 text-xs font-medium text-ink-muted">
            {dateLabel} through
            <input
              aria-label={`${dateLabel} through`}
              type="date"
              className="h-10 rounded-lg border border-line bg-white px-3 text-sm text-ink"
              value={value.end_date ?? ''}
              min={value.start_date || undefined}
              onChange={(event) => update('end_date', event.target.value)}
            />
          </label>
        </>
      )}
    </div>
  );
}
