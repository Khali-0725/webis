import { cn } from '@/utils/cn';

const SIZES = {
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-14 w-14 text-base',
};

export function Avatar({ src, initials, name, size = 'md', className }) {
  if (src) {
    return (
      <img
        src={src}
        alt={name ? `${name}'s profile photo` : 'Profile photo'}
        className={cn('shrink-0 rounded-full object-cover', SIZES[size] ?? SIZES.md, className)}
      />
    );
  }

  return (
    <span
      role="img"
      aria-label={name ? `${name}'s initials` : 'Profile initials'}
      className={cn(
        'grid shrink-0 place-items-center rounded-full bg-navy-600 font-semibold uppercase text-white',
        SIZES[size] ?? SIZES.md,
        className,
      )}
    >
      {initials || '?'}
    </span>
  );
}
