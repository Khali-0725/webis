import { Icon } from '@/components/ui/Icon';

const CHANNELS = [
  {
    icon: 'message',
    label: 'Email',
    value: 'support@webis.local',
    href: 'mailto:support@webis.local',
  },
  {
    icon: 'user',
    label: 'Phone',
    value: '0917 000 0000',
    href: 'tel:+639170000000',
  },
  {
    icon: 'pin',
    label: 'Location',
    value: 'Tanza, Cavite, Philippines',
    href: null,
  },
];

export default function ContactPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
      <p className="text-xs font-medium uppercase tracking-wide text-brand">Contact</p>
      <h1 className="mt-2 font-display text-3xl font-bold text-navy-800 sm:text-4xl">
        Get in touch
      </h1>
      <p className="mt-4 max-w-xl text-sm leading-relaxed text-ink-muted sm:text-base">
        Have a question about a booking, a provider account, or something that isn&apos;t working
        right? Reach out through any of the channels below.
      </p>

      <div className="mt-8 grid gap-4 sm:grid-cols-3">
        {CHANNELS.map((channel) => (
          <div key={channel.label} className="webis-card p-5">
            <span className="grid h-9 w-9 place-items-center rounded-full bg-brand-50 text-brand">
              <Icon name={channel.icon} className="h-4 w-4" />
            </span>
            <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
              {channel.label}
            </p>
            {channel.href ? (
              <a href={channel.href} className="mt-1 block text-sm font-medium text-navy-800 hover:text-brand">
                {channel.value}
              </a>
            ) : (
              <p className="mt-1 text-sm font-medium text-navy-800">{channel.value}</p>
            )}
          </div>
        ))}
      </div>

      <div className="mt-10 webis-card p-6">
        <h2 className="font-display text-lg font-semibold text-navy-800">Reporting a problem?</h2>
        <p className="mt-2 text-sm leading-relaxed text-ink-muted">
          If your concern is about a specific booking or account - a no-show, a payment dispute,
          or inappropriate behavior - email us with the booking code and details and an
          administrator will follow up.
        </p>
      </div>
    </div>
  );
}
