import { forwardRef, useId } from 'react';
import { cn } from '@/utils/cn';

/**
 * Accessible text input.
 *
 * The error message is wired with aria-describedby and aria-invalid so screen
 * readers announce it, which the ISO 25010 "Inclusivity" and "User error
 * protection" sub-criteria both depend on.
 */
export const Input = forwardRef(function Input(
  { label, error, hint, icon, className, id, type = 'text', required = false, ...props },
  ref,
) {
  const generatedId = useId();
  const inputId = id ?? generatedId;
  const errorId = `${inputId}-error`;
  const hintId = `${inputId}-hint`;

  const describedBy = [error ? errorId : null, hint ? hintId : null].filter(Boolean).join(' ');

  return (
    <div className={cn('w-full', className)}>
      {label && (
        <label htmlFor={inputId} className="mb-1.5 block text-sm font-medium text-ink">
          {label}
          {required && (
            <span className="ml-0.5 text-red-600" aria-hidden="true">
              *
            </span>
          )}
        </label>
      )}

      <div className="relative">
        {icon && (
          <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-soft">
            {icon}
          </span>
        )}

        <input
          ref={ref}
          id={inputId}
          type={type}
          required={required}
          aria-invalid={error ? 'true' : undefined}
          aria-describedby={describedBy || undefined}
          className={cn(
            'h-11 w-full rounded-lg border bg-white px-3 text-sm text-ink placeholder:text-ink-soft',
            'transition-colors focus:border-navy-500',
            icon && 'pl-9',
            error ? 'border-red-400' : 'border-line',
          )}
          {...props}
        />
      </div>

      {hint && !error && (
        <p id={hintId} className="mt-1 text-xs text-ink-muted">
          {hint}
        </p>
      )}

      {error && (
        <p id={errorId} role="alert" className="mt-1 text-xs font-medium text-red-600">
          {error}
        </p>
      )}
    </div>
  );
});
