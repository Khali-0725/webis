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
              Book reliable and professional services anytime, anywhere. WEBIS connects you with
              verified experts in Tanza, Cavite.
            </p>

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
    </>
  );
}
