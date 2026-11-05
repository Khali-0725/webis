import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { publicApi } from '@/services/api/publicApi';

export default function LandingPage() {
  const { data: announcement } = useQuery({ queryKey: ['public', 'announcement'], queryFn: publicApi.announcement });
  const { data: faqs = [] } = useQuery({ queryKey: ['public', 'faqs'], queryFn: publicApi.faqs });

  return (
    <>
      <section className="bg-gradient-to-br from-navy-700 to-navy-900 text-white">
        <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:py-20">
          <div>
            <h1 className="font-display text-4xl font-bold leading-tight text-white sm:text-5xl">
              Find Trusted
              <br />
              <span className="text-brand">Service Providers</span>
            </h1>

            <p className="mt-4 max-w-lg text-sm text-white/70 sm:text-base">
              WEBIS helps clients and service providers coordinate bookings, messages, and payments in one place.
            </p>
            <div className="mt-7 flex flex-wrap gap-3">
              <Link to="/register"><Button variant="brand">Create an account</Button></Link>
              <Link to="/login"><Button variant="outline">Sign in</Button></Link>
            </div>
          </div>

          <div className="hidden items-center justify-center lg:flex">
            <div className="grid h-56 w-full max-w-xs place-items-center rounded-card bg-white/5 ring-1 ring-white/10">
              <div className="text-center">
                <Icon name="wrench" className="mx-auto h-9 w-9 text-white/70" />
                <p className="mt-3 text-sm text-white/70">Professional Services</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {announcement?.text && <section className="border-b border-line bg-blue-50"><div className="mx-auto max-w-7xl px-4 py-5 sm:px-6"><h2 className="font-display text-lg font-semibold text-navy-800">WEBIS update</h2><p className="mt-1 whitespace-pre-wrap text-sm text-ink">{announcement.text}</p></div></section>}

      <section className="border-t border-line bg-canvas">
        <div className="mx-auto flex max-w-7xl flex-col items-center gap-4 px-4 py-12 text-center sm:px-6">
          <h2 className="font-display text-2xl font-bold text-navy-800">
            Offer your skills on WEBIS
          </h2>
          <p className="max-w-xl text-sm text-ink-muted">
            Independent providers in Tanza can list services, manage bookings and get paid — all in
            one verified platform.
          </p>
          <Link to="/register">
            <Button variant="brand">Register as a provider</Button>
          </Link>
        </div>
      </section>
      <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <h2 className="font-display text-2xl font-bold text-navy-800">How WEBIS works</h2>
        <ol className="mt-6 grid gap-4 md:grid-cols-3">
          <li className="webis-card p-5"><span className="text-sm font-bold text-brand">01 · Discover</span><p className="mt-2 text-sm text-ink-muted">Create an account, then search services and providers inside WEBIS.</p></li>
          <li className="webis-card p-5"><span className="text-sm font-bold text-brand">02 · Book</span><p className="mt-2 text-sm text-ink-muted">Choose an available time, share the job details, and wait for provider acceptance.</p></li>
          <li className="webis-card p-5"><span className="text-sm font-bold text-brand">03 · Confirm</span><p className="mt-2 text-sm text-ink-muted">Track payment verification and leave a final review after the work is completed.</p></li>
        </ol>
        <p className="mt-5 text-sm text-ink-muted">Verification confirms that required documents were reviewed. It does not guarantee workmanship, availability, or personal safety. Review the listing and communicate through WEBIS before booking.</p>
      </section>
      {faqs.length > 0 && (
        <section className="border-t border-line bg-white">
          <div className="mx-auto max-w-4xl px-4 py-14 sm:px-6">
            <h2 className="font-display text-2xl font-bold text-navy-800">Frequently asked questions</h2>
            <div className="mt-5 divide-y divide-line rounded-lg border border-line px-4">
              {faqs.map((faq) => (
                <details key={faq.id} className="py-4">
                  <summary className="cursor-pointer font-medium text-ink">{faq.question}</summary>
                  <p className="mt-2 whitespace-pre-wrap text-sm text-ink-muted">{faq.answer}</p>
                </details>
              ))}
            </div>
          </div>
        </section>
      )}
    </>
  );
}
