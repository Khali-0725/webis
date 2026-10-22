import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Badge } from '@/components/ui/Badge';
import { VerifiedBadge } from '@/components/ui/VerifiedBadge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { publicApi } from '@/services/api/publicApi';
import { queryKeys } from '@/services/api/queryClient';

export default function ProvidersPage() {
  const [search, setSearch] = useState('');
  const [barangayId, setBarangayId] = useState('');
  const [municipality, setMunicipality] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [page, setPage] = useState(1);

  const { data: barangays = [] } = useQuery({
    queryKey: queryKeys.public.barangays,
    queryFn: publicApi.listBarangays,
  });
  const { data: categories = [] } = useQuery({ queryKey: queryKeys.public.categories, queryFn: publicApi.listCategories });
  const municipalities = [...new Set(barangays.map((item) => item.municipality).filter(Boolean))].sort();

  const params = {
    ...(search ? { search } : {}),
    ...(barangayId ? { barangay_id: barangayId } : {}),
    ...(municipality ? { municipality } : {}),
    ...(categoryId ? { category_id: categoryId } : {}),
    page,
  };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.public.providers(params),
    queryFn: () => publicApi.listProviders(params),
  });

  const providers = data?.items ?? [];

  const updateFilter = (patch) => {
    if ('search' in patch) setSearch(patch.search);
    if ('barangay_id' in patch) setBarangayId(patch.barangay_id);
    if ('municipality' in patch) { setMunicipality(patch.municipality); setBarangayId(''); }
    if ('category_id' in patch) setCategoryId(patch.category_id);
    setPage(1);
  };

  return (
    <div className="mx-auto max-w-5xl px-4 py-10 sm:px-6">
      <div className="flex items-center justify-between gap-3"><h1 className="font-display text-2xl font-bold text-navy-800">Browse providers</h1><Link to="/providers/map" className="text-sm font-semibold text-brand hover:underline">Map view</Link></div>
      <p className="mt-1 text-sm text-ink-muted">
        Verified service providers on WEBIS, ranked by rating.
      </p>

      <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input
          type="text"
          placeholder="Search by business name or skill"
          value={search}
          onChange={(event) => updateFilter({ search: event.target.value })}
          className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
        />
        <select aria-label="Filter by municipality" value={municipality} onChange={(event) => updateFilter({ municipality: event.target.value })} className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm"><option value="">All municipalities</option>{municipalities.map((value) => <option key={value} value={value}>{value}</option>)}</select>
        <select
          aria-label="Filter by barangay"
          value={barangayId}
          onChange={(event) => updateFilter({ barangay_id: event.target.value })}
          className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
        >
          <option value="">All barangays</option>
          {barangays.filter((barangay) => !municipality || barangay.municipality === municipality).map((barangay) => (
            <option key={barangay.id} value={barangay.id}>
              {barangay.name}
            </option>
          ))}
        </select>
        <select aria-label="Filter by category" value={categoryId} onChange={(event) => updateFilter({ category_id: event.target.value })} className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm"><option value="">All categories</option>{categories.map((value) => <option key={value.id} value={value.id}>{value.name}</option>)}</select>
      </div>

      <div className="mt-6">
        {isPending && <LoadingState label="Loading providers…" />}
        {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

        {!isPending && !isError && providers.length === 0 && (
          <>
            <EmptyState title="No providers found" description="Try a different search term or barangay." />
            <p className="mt-4 text-center text-sm text-ink-muted">
              See a sample listing in our <a href="/#demo-provider" className="font-semibold text-brand hover:underline">demo provider preview</a>.
            </p>
          </>
        )}

        {!isPending && !isError && providers.length > 0 && (
          <ul className="space-y-4">
            {providers.map((provider) => (
              <li key={provider.id}>
                <Link
                  to={`/providers/${provider.id}`}
                  className="webis-card flex items-start justify-between gap-4 p-5"
                >
                  <div>
                    <h3 className="font-display text-lg font-semibold text-navy-800">
                      {provider.business_name}
                    </h3>
                    <VerifiedBadge verified={provider.is_verified} />
                    {provider.bio && (
                      <p className="mt-1 line-clamp-2 text-sm text-ink-muted">{provider.bio}</p>
                    )}
                    <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                      {provider.base_barangay?.name && <span>{provider.base_barangay.name}</span>}
                      {provider.skills?.length > 0 && (
                        <>
                          <span aria-hidden="true">·</span>
                          {provider.skills.slice(0, 3).map((skill) => (
                            <Badge key={skill} tone="neutral">
                              {skill}
                            </Badge>
                          ))}
                        </>
                      )}
                    </div>
                  </div>
                  {provider.rating_count > 0 && (
                    <Badge tone="success" className="shrink-0">
                      ★ {provider.rating_avg} ({provider.rating_count})
                    </Badge>
                  )}
                </Link>
              </li>
            ))}
          </ul>
        )}

        <Pagination meta={data?.meta} onChange={setPage} />
      </div>
    </div>
  );
}
