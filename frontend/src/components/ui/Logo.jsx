import { cn } from '@/utils/cn';

/** SERBISYOLINK wordmark and link mark. */
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
          <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
            <path d="M8.5 8.5 7 7a3.5 3.5 0 0 0-5 5l3.5 3.5a3.5 3.5 0 0 0 5 0l1.5-1.5" />
            <path d="m15.5 15.5 1.5 1.5a3.5 3.5 0 0 0 5-5L18.5 10a3.5 3.5 0 0 0-5 0L12 11.5" />
            <path d="m8 16 8-8" />
          </svg>
        </span>
      )}

      <span className="leading-tight">
        <span className="block font-display text-lg font-bold tracking-tight">
          <span className={isLight ? 'text-white' : 'text-navy-800'}>SERBISYOLINK</span>
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
