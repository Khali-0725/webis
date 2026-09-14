import { forwardRef, useId } from 'react';
import { cn } from '@/utils/cn';

const fieldClasses = (error) =>
  cn(
    'w-full rounded-lg border bg-white px-3 text-sm text-ink placeholder:text-ink-soft',
    'transition-colors focus:border-navy-500 focus:outline-none',
    error ? 'border-red-400' : 'border-line',
  );

function FieldShell({ label, required, error, hint, id, className, children }) {
  return (
    <div className={cn('w-full', className)}>
      {label && (
        <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-ink">
          {label}
          {required && (
            <span className="ml-0.5 text-red-600" aria-hidden="true">
              *
            </span>
          )}
        </label>
      )}
      {children}
      {hint && !error && <p className="mt-1 text-xs text-ink-muted">{hint}</p>}
      {error && (
        <p role="alert" className="mt-1 text-xs font-medium text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}

/** A labelled `<select>` styled like `Input`. Pass `options` as [{value,label}]. */
export const Select = forwardRef(function Select(
  { label, error, hint, options = [], className, id, required = false, children, ...props },
  ref,
) {
  const generatedId = useId();
  const selectId = id ?? generatedId;

  return (
    <FieldShell label={label} required={required} error={error} hint={hint} id={selectId} className={className}>
      <select
        ref={ref}
        id={selectId}
        required={required}
        aria-invalid={error ? 'true' : undefined}
        className={cn(fieldClasses(error), 'h-11')}
        {...props}
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
        {children}
      </select>
    </FieldShell>
  );
});

/** A labelled `<textarea>` styled like `Input`. */
export const Textarea = forwardRef(function Textarea(
  { label, error, hint, className, id, required = false, rows = 3, ...props },
  ref,
) {
  const generatedId = useId();
  const textareaId = id ?? generatedId;

  return (
    <FieldShell label={label} required={required} error={error} hint={hint} id={textareaId} className={className}>
      <textarea
        ref={ref}
        id={textareaId}
        rows={rows}
        required={required}
        aria-invalid={error ? 'true' : undefined}
        className={cn(fieldClasses(error), 'py-2.5')}
        {...props}
      />
    </FieldShell>
  );
});
