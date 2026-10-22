import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { publicApi } from '@/services/api/publicApi';
import { queryKeys } from '@/services/api/queryClient';

export default function LandingPage() {
  const navigate = useNavigate();
  const [keyword, setKeyword] = useState('');

  const {
    data: categories = [],
    isPending,
    isError,
    error,
    refetch,
  } = useQuery({
    queryKey: queryKeys.public.categories,
    queryFn: publicApi.listCategories,
  });
  const { data: announcement } = useQuery({ queryKey: ['public', 'announcement'], queryFn: publicApi.announcement });
  const { data: featured } = useQuery({ queryKey: ['public', 'featured-services'], queryFn: () => publicApi.searchServices({ per_page: 3 }) });

  const handleSearch = (event) => {
    event.preventDefault();
    const params = new URLSearchParams();
    if (keyword.trim()) params.set('q', keyword.trim());
    navigate(`/search${params.toString() ? `?${params}` : ''}`);
  };

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
              Find independent service providers, compare their listings, and manage bookings in one place. WEBIS verifies provider documents before they can publish services.
            </p>
            <div className="mt-5 flex flex-wrap gap-4 text-sm font-semibold"><Link to="/search" className="text-white underline underline-offset-4">Browse services</Link><Link to="/providers" className="text-white underline underline-offset-4">Browse providers</Link><Link to="/providers/map" className="text-white underline underline-offset-4">Explore the map</Link></div>

            <form
              className="mt-7 flex max-w-lg overflow-hidden rounded-lg bg-white shadow-card"
              onSubmit={handleSearch}
              role="search"
            >
              <label htmlFor="hero-search" className="sr-only">
                Search for services
              </label>
              <input
                id="hero-search"
                type="search"
                value={keyword}
                onChange={(event) => setKeyword(event.target.value)}
                placeholder="Search for services…"
                className="h-12 flex-1 px-4 text-sm text-ink placeholder:text-ink-soft"
              />
              <button
                type="submit"
                className="h-12 bg-brand px-7 text-sm font-semibold text-white transition-colors hover:bg-brand-600"
              >
                Search
              </button>
            </form>
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

      {featured?.items?.length > 0 && <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6"><div className="flex items-center justify-between gap-3"><h2 className="font-display text-2xl font-bold text-navy-800">Explore services</h2><Link to="/search" className="text-sm font-semibold text-brand hover:underline">Browse all</Link></div><div className="mt-5 grid gap-4 md:grid-cols-3">{featured.items.map((service) => <Link key={service.id} to={`/services/${service.id}`} className="webis-card block p-5 hover:border-navy-300"><p className="text-xs font-semibold uppercase tracking-wide text-brand">{service.category?.name}</p><h3 className="mt-2 font-display text-lg font-semibold text-navy-800">{service.title}</h3><p className="mt-2 line-clamp-2 text-sm text-ink-muted">{service.description}</p></Link>)}</div></section>}

      {announcement?.text && <section className="border-b border-line bg-blue-50"><div className="mx-auto max-w-7xl px-4 py-5 sm:px-6"><h2 className="font-display text-lg font-semibold text-navy-800">WEBIS update</h2><p className="mt-1 whitespace-pre-wrap text-sm text-ink">{announcement.text}</p></div></section>}

      <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div className="flex items-end justify-between">
          <div>
            <h2 className="font-display text-2xl font-bold text-navy-800">Browse by category</h2>
            <p className="mt-1 text-sm text-ink-muted">Find the right provider for the job</p>
          </div>
          <Link to="/search" className="text-sm font-medium text-brand hover:text-brand-600">
            View all
          </Link>
        </div>

        {isPending && <LoadingState label="Loading categories…" className="mt-6" />}

        {isError && (
          <ErrorState description={error?.message} onRetry={() => refetch()} className="mt-6" />
        )}

        {!isPending && !isError && categories.length === 0 && (
          <EmptyState
            title="No categories yet"
            description="Categories will appear here once an administrator adds them."
            className="mt-6"
          />
        )}

        {!isPending && !isError && categories.length > 0 && (
          <ul className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            {categories.map((category) => (
              <li key={category.id}>
                <Link
                  to={`/search?category=${category.slug}`}
                  className="webis-card flex flex-col items-center gap-3 px-4 py-6 transition-colors hover:border-navy-300"
                >
                  <span className="grid h-11 w-11 place-items-center rounded-lg bg-brand-50 text-brand">
                    <Icon name={category.icon || 'wrench'} />
                  </span>
                  <span className="text-center text-sm font-medium text-ink">{category.name}</span>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>

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
          <li className="webis-card p-5"><span className="text-sm font-bold text-brand">01 · Discover</span><p className="mt-2 text-sm text-ink-muted">Search services or browse provider profiles and public service areas.</p></li>
          <li className="webis-card p-5"><span className="text-sm font-bold text-brand">02 · Book</span><p className="mt-2 text-sm text-ink-muted">Choose an available time, share the job details, and wait for provider acceptance.</p></li>
          <li className="webis-card p-5"><span className="text-sm font-bold text-brand">03 · Confirm</span><p className="mt-2 text-sm text-ink-muted">Track payment verification and leave a final review after the work is completed.</p></li>
        </ol>
        <p className="mt-5 text-sm text-ink-muted">Verification confirms that required documents were reviewed. It does not guarantee workmanship, availability, or personal safety. Review the listing and communicate through WEBIS before booking.</p>
      </section>
    </>
  );
}
