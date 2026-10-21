export function AdminRecordFilters({ value, onChange, inTrash = false }) {
  const update = (key, next) => onChange({ [key]: next });
  const dateLabel = inTrash ? 'Archived' : 'Created';

  return (
    <div className="mb-4 flex flex-wrap items-end gap-3 print:hidden">
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
    </div>
  );
}
