import { cn } from '@/utils/cn';

/**
 * The WEBIS wordmark: "WEBIS" in navy (or white on dark) and "System" in the
 * brand orange, with an optional portal sub-label, exactly as in the mockups.
 */
export function Logo({ variant = 'dark', sublabel, className, showMark = true }) {
  const isLight = variant === 'light';

  return (
    <div className={cn('flex items-center gap-2.5', className)}>
      {showMark && (
        <span
          className={cn(
            'grid h-8 w-8 shrink-0 place-items-center rounded-lg font-display text-sm font-bold',
            isLight ? 'bg-white/10 text-white' : 'bg-navy-700 text-white',
          )}
          aria-hidden="true"
        >
          W
        </span>
      )}

      <span className="leading-tight">
        <span className="block font-display text-lg font-bold tracking-tight">
          <span className={isLight ? 'text-white' : 'text-navy-800'}>WEBIS</span>{' '}
          <span className="text-brand">System</span>
        </span>
        {sublabel && (
          <span className={cn('block text-[11px]', isLight ? 'text-white/60' : 'text-ink-muted')}>
            {sublabel}
          </span>
        )}
      </span>
    </div>
  );
}
