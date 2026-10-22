import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, EmptyState, ErrorState } from '@/components/ui/States';
import { Icon } from '@/components/ui/Icon';
import { VerificationDocumentPreview } from '@/components/verification/VerificationDocumentPreview';
import { verificationApi } from '@/services/api/verificationApi';

const TABS = [
  { key: 'pending', label: 'Pending', params: { status: 'pending' } },
  { key: 'verified', label: 'Verified', params: { status: 'all', profile_status: 'approved' } },
  { key: 'unverified', label: 'Unverified', params: { status: 'all', profile_status: 'pending' } },
  { key: 'rejected', label: 'Rejected', params: { status: 'all', profile_status: 'rejected' } },
];

export default function AdminVerificationPage() {
  const queryClient = useQueryClient();
  const [tab, setTab] = useState('pending');
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState([]);
  const [action, setAction] = useState(null);
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState(null);
  const [actionError, setActionError] = useState(null);
  const [previewDocument, setPreviewDocument] = useState(null);
  const profileTab = tab !== 'pending';
  const params = { ...TABS.find((item) => item.key === tab).params, page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'verification', tab, params],
    queryFn: () => profileTab
      ? verificationApi.listProviders({ status: params.profile_status, page })
      : verificationApi.listDocuments(params),
  });
  const { data: countData } = useQuery({
    queryKey: ['admin', 'verification', 'counts'],
    queryFn: () => verificationApi.listDocuments({ status: 'pending', per_page: 1 }),
  });
  const documents = profileTab ? [] : (data?.items ?? []);
  const profiles = profileTab ? (data?.items ?? []) : [];
  const pendingIds = documents.filter((doc) => doc.status === 'pending').map((doc) => doc.id);
  const selectedPending = selected.filter((id) => pendingIds.includes(id));
  const allSelected = pendingIds.length > 0 && pendingIds.every((id) => selectedPending.includes(id));

  const changeTab = (next) => {
    setTab(next);
    setPage(1);
    setSelected([]);
  };

  const runAction = async () => {
    if (!action) return;
    setBusy(true);
    setActionError(null);
    try {
      if (action.kind === 'bulk') {
        const result = await verificationApi.bulk(action.ids, action.decision, reason);
        setNotice({ tone: 'success', message: `${result.processed} document(s) reviewed; ${result.skipped} ineligible document(s) skipped. Final provider approval remains separate.` });
      } else if (action.kind === 'provider') {
        await verificationApi.approveProvider(action.providerId);
        setNotice({ tone: 'success', message: 'Provider verified after required document checks.' });
      } else if (action.decision === 'approve') {
        await verificationApi.approveDocument(action.id);
        setNotice({ tone: 'success', message: 'Document approved. Both required documents and a final provider decision are still needed.' });
      } else {
        await verificationApi.rejectDocument(action.id, reason);
        setNotice({ tone: 'success', message: 'Document rejected with the recorded reason.' });
      }
      setAction(null);
      setSelected([]);
      setReason('');
      await queryClient.invalidateQueries({ queryKey: ['admin', 'verification'] });
    } catch (err) {
      setActionError(err?.message ?? 'Could not finish the review.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Card title="Provider verification">
      <p className="mb-4 text-sm text-ink-muted">
        Verification means an administrator reviewed submitted documents. It does not mean government validation,
        a criminal background check, or a guarantee of safety.
      </p>
      <p className="mb-4 text-sm text-ink-muted">
        Both required documents must be approved. Then select Verify provider in the Unverified tab.
      </p>
      {notice && <Alert tone={notice.tone} className="mb-4">{notice.message}</Alert>}

      <div className="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Verification status">
        {TABS.map((item) => (
          <button key={item.key} type="button" role="tab" aria-selected={tab === item.key}
            className={`rounded-lg px-3 py-2 text-sm font-medium ${tab === item.key ? 'bg-navy-700 text-white' : 'bg-navy-50 text-navy-700'}`}
            onClick={() => changeTab(item.key)}>
            {item.label} {countData?.meta?.counts?.[item.key] ?? ''}
          </button>
        ))}
      </div>

      {selectedPending.length > 0 && (
        <div className="mb-4 flex flex-wrap items-center gap-2 rounded-lg bg-navy-50 p-3 text-sm">
          <span>{selectedPending.length} pending document(s) selected</span>
          <Button size="sm" onClick={() => setAction({ kind: 'bulk', decision: 'approve', ids: selectedPending })}>Bulk Accept</Button>
          <Button size="sm" variant="danger" onClick={() => setAction({ kind: 'bulk', decision: 'reject', ids: selectedPending })}>Bulk Decline</Button>
        </div>
      )}

      {isPending && <LoadingState label="Loading documents…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}
      {!isPending && !isError && data?.items?.length === 0 && (
        <EmptyState title={profileTab ? 'No providers in this view' : 'No pending documents'}
          description={profileTab ? 'Providers will appear here when their overall verification status matches this tab.' : 'New submissions will appear here for review.'} />
      )}
      {!isPending && !isError && profileTab && profiles.length > 0 && (
        <div className="space-y-4">
          {profiles.map((profile) => (
            <div key={profile.id} className="rounded-lg border border-line p-4">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <Link to={`/admin/providers/${profile.id}`} className="font-semibold text-navy-700 hover:underline">{profile.owner_name}</Link>
                  <p className="text-sm text-ink-muted">{profile.business_name || 'Business name not set'}</p>
                </div>
                <Badge tone={profile.verification_status === 'approved' ? 'success' : profile.verification_status === 'rejected' ? 'danger' : 'warning'}>{profile.verification_status}</Badge>
              </div>
              <div className="mt-3 flex flex-wrap gap-2 text-sm">
                <span>Government ID: {profile.required_documents.government_id}</span>
                <span>Business permit: {profile.required_documents.business_permit}</span>
              </div>
              {profile.rejection_reason && <p className="mt-2 text-sm text-ink-muted">Reason: {profile.rejection_reason}</p>}
              {profile.documents.length === 0 ? (
                <p className="mt-3 text-sm text-ink-muted">No documents submitted yet.</p>
              ) : (
                <ul className="mt-3 space-y-2 border-t border-line pt-3 text-sm">
                  {profile.documents.map((doc) => (
                    <li key={doc.id} className="flex flex-wrap items-center justify-between gap-2">
                      <span>{doc.document_type.replaceAll('_', ' ')} · {doc.original_name} · {doc.status}</span>
                      <span className="flex flex-wrap gap-2">
                        <button type="button" onClick={() => setPreviewDocument(doc)} aria-label={`View ${doc.original_name}`}
                          className="inline-flex items-center gap-1 rounded-lg border border-line px-2 py-1.5 text-navy-700 hover:bg-navy-50"><Icon name="eye" className="h-4 w-4" /> View</button>
                        {doc.status === 'pending' && <>
                          <Button size="sm" variant="success" onClick={() => setAction({ kind: 'document', decision: 'approve', id: doc.id })}>Approve</Button>
                          <Button size="sm" variant="danger" onClick={() => setAction({ kind: 'document', decision: 'reject', id: doc.id })}>Decline</Button>
                        </>}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
              {profile.verification_status !== 'approved' && profile.required_documents.government_id === 'approved' && profile.required_documents.business_permit === 'approved' && (
                <Button className="mt-3" size="sm" onClick={() => setAction({ kind: 'provider', providerId: profile.id })}>Verify provider</Button>
              )}
            </div>
          ))}
        </div>
      )}
      {!isPending && !isError && documents.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full min-w-[700px] text-left text-sm">
            <thead><tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
              <th className="py-2 pr-3"><input type="checkbox" aria-label="Select all pending documents on this page" checked={allSelected}
                onChange={(event) => setSelected(event.target.checked ? pendingIds : [])} disabled={pendingIds.length === 0} /></th>
              <th className="py-2 pr-3">Provider</th><th className="py-2 pr-3">Document</th>
              <th className="py-2 pr-3">Status</th><th className="py-2">Actions</th>
            </tr></thead>
            <tbody className="divide-y divide-line">
              {documents.map((doc) => (
                <tr key={doc.id}>
                  <td className="py-3 pr-3"><input type="checkbox" aria-label={`Select ${doc.original_name}`}
                    checked={selectedPending.includes(doc.id)} disabled={doc.status !== 'pending'}
                    onChange={(event) => setSelected(event.target.checked ? [...selectedPending, doc.id] : selectedPending.filter((id) => id !== doc.id))} /></td>
                  <td className="py-3 pr-3"><Link to={`/admin/providers/${doc.provider.id}`} className="font-medium text-navy-700 hover:underline">
                    {doc.provider.user_full_name}</Link><div className="text-xs text-ink-muted">{doc.provider.business_name}</div></td>
                  <td className="py-3 pr-3">{doc.document_type.replaceAll('_', ' ')}<div className="text-xs text-ink-muted">{doc.original_name}</div></td>
                  <td className="py-3 pr-3"><Badge tone={doc.status === 'approved' ? 'success' : doc.status === 'rejected' ? 'danger' : 'warning'}>{doc.status}</Badge></td>
                  <td className="py-3"><div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => setPreviewDocument(doc)} aria-label={`View ${doc.original_name}`}
                      className="inline-flex items-center gap-1 rounded-lg border border-line px-2 py-1.5 text-navy-700 hover:bg-navy-50"><Icon name="eye" className="h-4 w-4" /> View</button>
                    {doc.status === 'pending' && <>
                      <Button size="sm" variant="success" onClick={() => setAction({ kind: 'document', decision: 'approve', id: doc.id })}>Approve</Button>
                      <Button size="sm" variant="danger" onClick={() => setAction({ kind: 'document', decision: 'reject', id: doc.id })}>Decline</Button>
                    </>}
                    {doc.status === 'approved' && doc.provider_verification_status !== 'approved' && (
                      <Button size="sm" onClick={() => setAction({ kind: 'provider', providerId: doc.provider.id })}>Verify provider</Button>
                    )}
                  </div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <Pagination meta={data?.meta} onChange={(next) => { setPage(next); setSelected([]); }} />

      {previewDocument && <VerificationDocumentPreview document={previewDocument} onClose={() => setPreviewDocument(null)} />}

      <ConfirmDialog open={Boolean(action)} onClose={() => { setAction(null); setActionError(null); setReason(''); }}
        onConfirm={runAction} title={action?.kind === 'provider' ? 'Verify this provider?' : `${action?.decision === 'reject' ? 'Decline' : 'Approve'} selected document${action?.kind === 'bulk' ? 's' : ''}?`}
        description={action?.kind === 'provider' ? 'Both required documents must already be approved.' : 'This decision is recorded in the audit log.'}
        confirmLabel={action?.kind === 'provider' ? 'Verify provider' : action?.decision === 'reject' ? 'Decline' : 'Approve'}
        tone={action?.decision === 'reject' ? 'danger' : 'primary'} loading={busy} error={actionError}>
        {action?.decision === 'reject' && <Input label="Reason for declining" required value={reason} onChange={(event) => setReason(event.target.value)} />}
      </ConfirmDialog>
    </Card>
  );
}
