import { forwardRef } from 'react';
import { cn } from '@/utils/cn';
import { Spinner } from './Spinner';

const VARIANTS = {
  primary: 'bg-navy-700 text-white hover:bg-navy-600 active:bg-navy-800',
  brand: 'bg-brand text-white hover:bg-brand-600 active:bg-brand-700',
  outline: 'border border-navy-700 bg-white text-navy-700 hover:bg-navy-50',
  ghost: 'text-navy-700 hover:bg-navy-50',
  success: 'bg-emerald-600 text-white hover:bg-emerald-700',
  danger: 'bg-red-600 text-white hover:bg-red-700',
  subtle: 'border border-line bg-white text-ink hover:bg-canvas',
};

const SIZES = {
  sm: 'h-8 px-3 text-xs',
  md: 'h-10 px-4 text-sm',
  lg: 'h-12 px-6 text-base',
};

export const Button = forwardRef(function Button(
  {
    variant = 'primary',
    size = 'md',
    type = 'button',
    loading = false,
    disabled = false,
    fullWidth = false,
    className,
    children,
    ...props
  },
  ref,
) {
  const isDisabled = disabled || loading;

  return (
    <button
      ref={ref}
      type={type}
      disabled={isDisabled}
      aria-busy={loading || undefined}
      className={cn(
        'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors',
        'disabled:cursor-not-allowed disabled:opacity-60',
        VARIANTS[variant] ?? VARIANTS.primary,
        SIZES[size] ?? SIZES.md,
        fullWidth && 'w-full',
        className,
      )}
      {...props}
    >
      {loading && <Spinner className="h-4 w-4" />}
      {children}
    </button>
  );
});
