import { cn } from '@/utils/cn';
import { Button } from './Button';
import { Spinner } from './Spinner';

/** Shown while a panel's data is in flight. */
export function LoadingState({ label = 'Loading…', className }) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3 py-12', className)}>
      <Spinner className="h-6 w-6 text-navy-500" />
      <p className="text-sm text-ink-muted">{label}</p>
    </div>
  );
}

/** Shown when a request succeeded but there is nothing to show. */
export function EmptyState({ title, description, action, icon, className }) {
  return (
    <div className={cn('flex flex-col items-center justify-center px-6 py-12 text-center', className)}>
      {icon && <div className="mb-3 text-ink-soft">{icon}</div>}
      <p className="font-display text-base font-semibold text-navy-800">{title}</p>
      {description && <p className="mt-1 max-w-sm text-sm text-ink-muted">{description}</p>}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}

/** Shown when a request failed. Always offers a way forward. */
export function ErrorState({ title = 'Something went wrong', description, onRetry, className }) {
  return (
    <div className={cn('flex flex-col items-center justify-center px-6 py-12 text-center', className)}>
      <p className="font-display text-base font-semibold text-navy-800">{title}</p>
      {description && <p className="mt-1 max-w-sm text-sm text-ink-muted">{description}</p>}
      {onRetry && (
        <Button variant="outline" size="sm" className="mt-4" onClick={onRetry}>
          Try again
        </Button>
      )}
    </div>
  );
}

/** Placeholder rows used while a list loads. */
export function SkeletonRows({ rows = 3, className }) {
  return (
    <div className={cn('space-y-3', className)} aria-hidden="true">
      {Array.from({ length: rows }).map((_, index) => (
        <div key={index} className="flex items-center justify-between gap-4">
          <div className="flex-1 space-y-2">
            <div className="webis-skeleton h-4 w-1/3" />
            <div className="webis-skeleton h-3 w-1/4" />
          </div>
          <div className="webis-skeleton h-8 w-24 rounded-lg" />
        </div>
      ))}
    </div>
  );
}
