import { cn } from '@/utils/cn';
import { Badge } from './Badge';

/**
 * Switches a list between its live records and its trash (soft-deleted
 * rows). `value` is '' for live or 'only' for trash - the same string the
 * API's `?trashed=` filter takes, so pages pass it straight through.
 */
export function TrashToggle({ value, onChange, className }) {
  const options = [
    { value: '', label: 'Active' },
    { value: 'only', label: 'Trash' },
  ];

  return (
    <div
      role="group"
      aria-label="Show active or deleted records"
      className={cn('inline-flex h-10 rounded-lg border border-line bg-white p-0.5', className)}
    >
      {options.map((option) => {
        const selected = option.value === value;
        return (
          <button
            key={option.label}
            type="button"
            aria-pressed={selected}
            onClick={() => onChange(option.value)}
            className={cn(
              'rounded-md px-3 text-sm font-medium transition-colors',
              selected ? 'bg-navy-700 text-white' : 'text-ink-muted hover:text-ink',
            )}
          >
            {option.label}
          </button>
        );
      })}
    </div>
  );
}

/** Marks a row that lives in the trash. */
export function DeletedBadge({ deletedAt }) {
  if (!deletedAt) return null;
  const when = new Date(deletedAt).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
  return <Badge tone="warning">Deleted {when}</Badge>;
}
