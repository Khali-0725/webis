import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { appealApi } from '@/services/api/appealApi';

export default function AppealListPage() {
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState(null);
  const [decision, setDecision] = useState('');
  const [rationale, setRationale] = useState('');
  const queryClient = useQueryClient();
  const { data, isPending, isError, error, refetch } = useQuery({ queryKey: ['admin', 'appeals', page], queryFn: () => appealApi.list({ page }) });
  const mutation = useMutation({
    mutationFn: () => appealApi.decide(selected.id, { decision, rationale }),
    onSuccess: () => { setSelected(null); setDecision(''); setRationale(''); queryClient.invalidateQueries({ queryKey: ['admin', 'appeals'] }); },
  });

  return <Card title="Suspension Appeals">
    {isPending && <LoadingState label="Loading appeals…" />}
    {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}
    {!isPending && !isError && data.items.length === 0 && <EmptyState title="No appeals" description="Appeals from suspended accounts will appear here." />}
    {!isPending && !isError && data.items.length > 0 && <div className="space-y-3">{data.items.map((item) => <div key={item.id} className="rounded-lg border border-line p-4"><div className="flex flex-wrap items-start justify-between gap-3"><div><p className="font-semibold text-ink">{item.user?.first_name} {item.user?.last_name} · {item.user?.email}</p><p className="mt-1 text-xs text-ink-muted">Submitted {new Date(item.created_at).toLocaleString('en-PH')} · {item.status}</p></div>{item.status === 'pending' && <Button size="sm" onClick={() => { setSelected(item); setDecision(''); setRationale(''); mutation.reset(); }}>Review</Button>}</div><p className="mt-3 whitespace-pre-wrap text-sm text-ink-muted">{item.explanation}</p>{item.decision_rationale && <p className="mt-2 text-sm text-ink-muted">Decision: {item.decision_rationale}</p>}</div>)}</div>}
    <Pagination meta={data?.meta} onChange={setPage} />
    <ConfirmDialog open={Boolean(selected)} onClose={() => { setSelected(null); mutation.reset(); }} onConfirm={() => mutation.mutate()} title="Decide this suspension appeal?" description={selected?.user?.email} confirmLabel={decision === 'approved' ? 'Approve appeal' : decision === 'rejected' ? 'Reject appeal' : 'Choose a decision'} loading={mutation.isPending} disabled={!decision || rationale.trim().length < 10} error={mutation.error?.message}>
      <div className="space-y-3"><label className="block text-sm text-ink">Decision<select value={decision} onChange={(e) => setDecision(e.target.value)} className="mt-1 block h-10 w-full rounded-lg border border-line bg-white px-2"><option value="">Select a decision</option><option value="approved">Approve and restore access</option><option value="rejected">Reject appeal</option></select></label><label className="block text-sm text-ink">Rationale<textarea minLength={10} maxLength={3000} required rows={4} value={rationale} onChange={(e) => setRationale(e.target.value)} className="mt-1 block w-full rounded-lg border border-line p-2" /></label>{rationale.trim().length < 10 && <Alert tone="warning">Enter at least 10 characters for the decision rationale.</Alert>}</div>
    </ConfirmDialog>
  </Card>;
}
