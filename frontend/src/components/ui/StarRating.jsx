import { cn } from '@/utils/cn';

/**
 * Renders a 1-5 star rating. Interactive when `onChange` is given (a picker
 * for submitting a review); read-only otherwise (displaying one already
 * given).
 */
export function StarRating({ value = 0, onChange, size = 'md', className }) {
  const sizeClass = size === 'sm' ? 'text-base' : 'text-2xl';

  return (
    <div className={cn('flex gap-0.5', sizeClass, className)} role={onChange ? 'radiogroup' : undefined}>
      {[1, 2, 3, 4, 5].map((star) => (
        <button
          key={star}
          type="button"
          disabled={!onChange}
          onClick={() => onChange?.(star)}
          aria-label={`${star} star${star > 1 ? 's' : ''}`}
          className={cn(
            'leading-none',
            star <= value ? 'text-amber-400' : 'text-slate-300',
            onChange && 'cursor-pointer hover:scale-110 transition-transform',
            !onChange && 'cursor-default',
          )}
        >
          ★
        </button>
      ))}
    </div>
  );
}
