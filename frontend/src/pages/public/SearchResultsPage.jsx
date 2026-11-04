import { useMemo } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Badge } from '@/components/ui/Badge';
import { ResponsiveTable } from '@/components/ui/ResponsiveTable';
import { VerifiedBadge } from '@/components/ui/VerifiedBadge';
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
  const { pathname } = useLocation();
  const servicePath = pathname.startsWith('/client/') ? '/client/services' : '/services';
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
    <div className="mx-auto min-w-0 max-w-7xl px-4 py-10 sm:px-6">
      <h1 className="font-display text-2xl font-bold text-navy-800">Browse services</h1>

      <div className="mt-6 grid min-w-0 gap-8 xl:grid-cols-[240px_minmax(0,1fr)]">
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

        <div className="min-w-0">
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
            <div className="min-w-0 overflow-x-auto rounded-xl border border-line bg-white">
              <ResponsiveTable aria-label="Browse services" className="w-full table-fixed text-left text-sm">
                <thead className="border-b border-line bg-canvas text-xs uppercase tracking-wide text-ink-muted">
                  <tr>
                    <th className="w-[30%] px-4 py-3 font-medium">Service</th>
                    <th className="w-[22%] px-4 py-3 font-medium">Provider</th>
                    <th className="w-[14%] px-4 py-3 font-medium">Category</th>
                    <th className="w-[14%] px-4 py-3 font-medium">Barangay</th>
                    <th className="w-[20%] px-4 py-3 text-right font-medium">Price</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-line">
                  {services.map((service) => (
                    <tr key={service.id}>
                      <td className="min-w-0 px-4 py-4 align-top">
                        <Link to={`${servicePath}/${service.id}`} className="break-words font-semibold text-navy-800 hover:underline">{service.title}</Link>
                        <p className="mt-1 line-clamp-2 break-words text-xs text-ink-muted">{service.description}</p>
                      </td>
                      <td className="min-w-0 px-4 py-4 align-top">
                        <span className="break-words">{service.provider?.business_name || 'Provider'}</span>
                        <div className="mt-1 flex flex-wrap gap-1">
                          <VerifiedBadge verified={service.provider?.is_verified} />
                          {service.provider?.rating_count > 0 && <Badge tone="success">★ {service.provider.rating_avg}</Badge>}
                        </div>
                      </td>
                      <td className="break-words px-4 py-4 align-top text-ink-muted">{service.category?.name || '—'}</td>
                      <td className="break-words px-4 py-4 align-top text-ink-muted">{service.provider?.base_barangay?.name || '—'}</td>
                      <td className="break-words px-4 py-4 text-right align-top font-semibold text-navy-800 [overflow-wrap:anywhere]">{formatPrice(service)}</td>
                    </tr>
                  ))}
                </tbody>
              </ResponsiveTable>
            </div>
          )}

          <Pagination meta={meta} onChange={goToPage} />
        </div>
      </div>
    </div>
  );
}
