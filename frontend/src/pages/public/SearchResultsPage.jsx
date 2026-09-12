import { useMemo } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { publicApi } from '@/services/api/publicApi';
import { queryKeys } from '@/services/api/queryClient';
import { PRICING_TYPE_META, SERVICE_SORT_OPTIONS } from '@/constants';

function formatPrice(service) {
  if (service.pricing_type === 'quote') {
    return service.min_price && service.max_price
      ? `₱${service.min_price} - ₱${service.max_price}`
      : 'Quote on request';
  }

  const suffix = service.pricing_type === 'hourly' ? '/hr' : '';
  return service.price ? `₱${service.price}${suffix}` : PRICING_TYPE_META[service.pricing_type]?.label;
}

export default function SearchResultsPage() {
  const [searchParams, setSearchParams] = useSearchParams();

  const params = useMemo(() => {
    const entries = Object.fromEntries(searchParams.entries());
    return { page: 1, ...entries };
  }, [searchParams]);

  const { data: categories = [] } = useQuery({
    queryKey: queryKeys.public.categories,
    queryFn: publicApi.listCategories,
  });

  const { data: barangays = [] } = useQuery({
    queryKey: queryKeys.public.barangays,
    queryFn: publicApi.listBarangays,
  });

  const {
    data,
    isPending,
    isError,
    error,
    refetch,
  } = useQuery({
    queryKey: queryKeys.public.services(params),
    queryFn: () => publicApi.searchServices(params),
  });

  const services = data?.items ?? [];
  const meta = data?.meta;

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams);
    if (value) next.set(key, value);
    else next.delete(key);
    next.delete('page');
    setSearchParams(next);
  };

  const goToPage = (page) => {
    const next = new URLSearchParams(searchParams);
    next.set('page', page);
    setSearchParams(next);
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <h1 className="font-display text-2xl font-bold text-navy-800">Browse services</h1>

      <div className="mt-6 grid gap-8 lg:grid-cols-[240px_1fr]">
        <aside className="space-y-5">
          <div>
            <label htmlFor="filter-category" className="mb-1.5 block text-sm font-medium text-ink">
              Category
            </label>
            <select
              id="filter-category"
              value={params.category ?? ''}
              onChange={(event) => updateParam('category', event.target.value)}
              className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
            >
              <option value="">All categories</option>
              {categories.map((category) => (
                <option key={category.id} value={category.slug}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="filter-barangay" className="mb-1.5 block text-sm font-medium text-ink">
              Barangay
            </label>
            <select
              id="filter-barangay"
              value={params.barangay_id ?? ''}
              onChange={(event) => updateParam('barangay_id', event.target.value)}
              className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
            >
              <option value="">All barangays</option>
              {barangays.map((barangay) => (
                <option key={barangay.id} value={barangay.id}>
                  {barangay.name}
                </option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-2 gap-2">
            <div>
              <label htmlFor="filter-min" className="mb-1.5 block text-sm font-medium text-ink">
                Min ₱
              </label>
              <input
                id="filter-min"
                type="number"
                min="0"
                value={params.min_price ?? ''}
                onChange={(event) => updateParam('min_price', event.target.value)}
                className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
              />
            </div>
            <div>
              <label htmlFor="filter-max" className="mb-1.5 block text-sm font-medium text-ink">
                Max ₱
              </label>
              <input
                id="filter-max"
                type="number"
                min="0"
                value={params.max_price ?? ''}
                onChange={(event) => updateParam('max_price', event.target.value)}
                className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
              />
            </div>
          </div>
        </aside>

        <div>
          <div className="mb-4 flex items-center justify-between">
            <p className="text-sm text-ink-muted">
              {meta ? `${meta.total} service${meta.total === 1 ? '' : 's'} found` : ''}
            </p>
            <select
              value={params.sort ?? 'newest'}
              onChange={(event) => updateParam('sort', event.target.value)}
              className="h-9 rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
            >
              {SERVICE_SORT_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>

          {isPending && <LoadingState label="Searching services…" />}

          {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

          {!isPending && !isError && services.length === 0 && (
            <EmptyState
              title="No services found"
              description="Try a different keyword, category, or barangay."
            />
          )}

          {!isPending && !isError && services.length > 0 && (
            <ul className="space-y-4">
              {services.map((service) => (
                <li key={service.id} className="webis-card p-5">
                  <Link to={`/services/${service.id}`} className="flex items-start justify-between gap-4">
                    <div>
                      <p className="text-xs font-medium uppercase tracking-wide text-brand">
                        {service.category?.name}
                      </p>
                      <h3 className="mt-1 font-display text-lg font-semibold text-navy-800">
                        {service.title}
                      </h3>
                      <p className="mt-1 line-clamp-2 text-sm text-ink-muted">{service.description}</p>
                      <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                        <span>{service.provider?.business_name}</span>
                        {service.provider?.base_barangay && (
                          <>
                            <span aria-hidden="true">·</span>
                            <span>{service.provider.base_barangay.name}</span>
                          </>
                        )}
                        {service.provider?.rating_count > 0 && (
                          <>
                            <span aria-hidden="true">·</span>
                            <Badge tone="success">★ {service.provider.rating_avg}</Badge>
                          </>
                        )}
                      </div>
                    </div>
                    <p className="whitespace-nowrap font-display text-lg font-bold text-navy-800">
                      {formatPrice(service)}
                    </p>
                  </Link>
                </li>
              ))}
            </ul>
          )}

          <Pagination meta={meta} onChange={goToPage} />
        </div>
      </div>
    </div>
  );
}
