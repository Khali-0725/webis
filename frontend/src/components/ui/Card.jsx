import { cn } from '@/utils/cn';

export function Card({ title, action, className, bodyClassName, children, ...props }) {
  return (
    <section className={cn('webis-card', className)} {...props}>
      {title && <CardHeader title={title} action={action} />}
      {title ? <CardBody className={bodyClassName}>{children}</CardBody> : children}
    </section>
  );
}

export function CardHeader({ title, action, className }) {
  return (
    <header className={cn('flex min-w-0 flex-wrap items-center justify-between gap-2 px-5 py-4', className)}>
      <h2 className="min-w-0 break-words text-base font-semibold text-navy-800">{title}</h2>
      {action}
    </header>
  );
}

export function CardBody({ className, children }) {
  return <div className={cn('min-w-0 px-5 pb-5', className)}>{children}</div>;
}

/**
 * The KPI tile from the dashboard mockups: a large coloured figure above a
 * muted caption.
 */
export function StatCard({ value, label, tone = 'brand', className }) {
  const tones = {
    brand: 'text-brand',
    success: 'text-emerald-600',
    navy: 'text-navy-700',
    warning: 'text-amber-500',
    info: 'text-blue-600',
  };

  return (
    <div className={cn('webis-card px-6 py-5 text-center', className)}>
      <p className={cn('font-display text-3xl font-bold leading-none', tones[tone] ?? tones.brand)}>
        {value}
      </p>
      <p className="mt-2 text-sm text-ink-muted">{label}</p>
    </div>
  );
}
