import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';

const STEPS = [
  {
    icon: 'search',
    title: 'Search',
    description: 'Browse verified providers by service, barangay, or price - no account needed to look around.',
  },
  {
    icon: 'calendar',
    title: 'Book a time slot',
    description: 'Pick an open slot from the provider’s real availability and send a request with your location.',
  },
  {
    icon: 'wallet',
    title: 'Pay by QR',
    description: 'Scan the provider’s QR code, pay through your banking app, and upload proof for verification.',
  },
  {
    icon: 'star',
    title: 'Rate the service',
    description: 'Once the job is done, leave a rating and review to help the next client choose well.',
  },
];

export default function AboutPage() {
  return (
    <div className="mx-auto max-w-4xl px-4 py-14 sm:px-6">
      <p className="text-xs font-medium uppercase tracking-wide text-brand">About WEBIS</p>
      <h1 className="mt-2 font-display text-3xl font-bold text-navy-800 sm:text-4xl">
        A booking platform built for Tanza, Cavite
      </h1>
      <p className="mt-4 max-w-2xl text-sm leading-relaxed text-ink-muted sm:text-base">
        WEBIS connects clients with independent service providers - plumbers, electricians, house
        cleaners, and more - in one place. Every provider is manually reviewed by an administrator
        before they can accept bookings, and every payment is a QR-based transfer confirmed by a
        human, not an automated gateway.
      </p>

      <div className="mt-10 grid gap-6 sm:grid-cols-2">
        {STEPS.map((step, index) => (
          <div key={step.title} className="webis-card p-5">
            <div className="flex items-center gap-3">
              <span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-50 text-brand">
                <Icon name={step.icon} className="h-4 w-4" />
              </span>
              <p className="text-xs font-semibold uppercase tracking-wide text-ink-muted">
                Step {index + 1}
              </p>
            </div>
            <h3 className="mt-3 font-display text-lg font-semibold text-navy-800">{step.title}</h3>
            <p className="mt-1 text-sm text-ink-muted">{step.description}</p>
          </div>
        ))}
      </div>

      <div className="mt-10 webis-card p-6">
        <h2 className="font-display text-lg font-semibold text-navy-800">What WEBIS is not</h2>
        <p className="mt-2 text-sm leading-relaxed text-ink-muted">
          WEBIS does not move money through a payment gateway, verify government IDs automatically,
          or provide live GPS tracking. Location pins are placed manually on a map, and identity
          verification is a manual document review by an administrator - by design, to keep the
          system simple and transparent about what it actually does.
        </p>
      </div>

      <div className="mt-10 flex flex-wrap gap-3">
        <Link to="/search">
          <Button>Browse services</Button>
        </Link>
        <Link to="/providers">
          <Button variant="outline">Browse providers</Button>
        </Link>
      </div>
    </div>
  );
}
