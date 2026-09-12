import { cn } from '@/utils/cn';
import { BOOKING_STATUS_META } from '@/constants';

const TONES = {
  neutral: 'bg-slate-100 text-slate-600',
  success: 'bg-emerald-50 text-emerald-700',
  warning: 'bg-amber-50 text-amber-700',
  info: 'bg-blue-50 text-blue-700',
  danger: 'bg-red-50 text-red-700',
  brand: 'bg-brand-50 text-brand-700',
};

export function Badge({ tone = 'neutral', className, children }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
        TONES[tone] ?? TONES.neutral,
        className,
      )}
    >
      {children}
    </span>
  );
}

/** Renders a booking status with the tone and wording used everywhere else. */
export function StatusBadge({ status, className }) {
  const meta = BOOKING_STATUS_META[status] ?? { label: status, tone: 'neutral' };

  return (
    <Badge tone={meta.tone} className={className}>
      {meta.label}
    </Badge>
  );
}
