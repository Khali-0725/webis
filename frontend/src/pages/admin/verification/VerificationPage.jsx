import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { IconActionButton } from '@/components/ui/IconActionButton';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { VerifiedBadge } from '@/components/ui/VerifiedBadge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, EmptyState, ErrorState } from '@/components/ui/States';
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
  const groupedDocuments = Object.values(documents.reduce((groups, doc) => {
    const key = String(doc.provider.id);
    if (!groups[key]) groups[key] = { provider: doc.provider, documents: [] };
    groups[key].documents.push(doc);
    return groups;
  }, {}));
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
      } else if (action.kind === 'replacement') {
        await verificationApi.requestReplacement(action.id);
        setNotice({ tone: 'success', message: 'Replacement requested from provider.' });
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
          <IconActionButton icon="check" label={`Accept ${selectedPending.length} selected documents`} onClick={() => setAction({ kind: 'bulk', decision: 'approve', ids: selectedPending })} />
          <IconActionButton icon="close" label={`Decline ${selectedPending.length} selected documents`} onClick={() => setAction({ kind: 'bulk', decision: 'reject', ids: selectedPending })} />
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
                {profile.verification_status === 'approved'
                  ? <VerifiedBadge verified />
                  : <Badge tone={profile.verification_status === 'rejected' ? 'danger' : 'warning'}>{profile.verification_status}</Badge>}
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
                        <IconActionButton icon="eye" label={`View ${doc.original_name}`} onClick={() => setPreviewDocument(doc)} />
                        {doc.status === 'pending' && <>
                          <IconActionButton icon="check" label={`Approve ${doc.original_name}`} onClick={() => setAction({ kind: 'document', decision: 'approve', id: doc.id })} />
                          <IconActionButton icon="close" label={`Decline ${doc.original_name}`} onClick={() => setAction({ kind: 'document', decision: 'reject', id: doc.id })} />
                        </>}
                        {doc.status === 'approved' && !doc.replacement_requested && (
                          <IconActionButton icon="settings" label={`Request a new ${doc.document_type.replaceAll('_', ' ')} from ${profile.owner_name}`} onClick={() => setAction({ kind: 'replacement', id: doc.id })} />
                        )}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
              {profile.verification_status !== 'approved' && profile.required_documents.government_id === 'approved' && profile.required_documents.business_permit === 'approved' && (
                <Button className="mt-3" size="sm" onClick={() => setAction({ kind: 'provider', providerId: profile.id })}>Verify provider</Button>
              )}
              {profile.verification_status !== 'approved' && (profile.required_documents.government_id !== 'approved' || profile.required_documents.business_permit !== 'approved') && (
                <p className="mt-3 text-sm text-ink-muted">The provider must submit any outstanding documents. Review and approve the government ID and business permit before final verification.</p>
              )}
            </div>
          ))}
        </div>
      )}
      {!isPending && !isError && documents.length > 0 && (
        <div>
          <label className="mb-3 inline-flex min-h-10 items-center gap-2 text-sm text-ink">
            <input type="checkbox" aria-label="Select all pending documents on this page" checked={allSelected}
              onChange={(event) => setSelected(event.target.checked ? pendingIds : [])} disabled={pendingIds.length === 0} />
            Select all pending documents on this page
          </label>
          <div className="space-y-3">
            {groupedDocuments.map(({ provider, documents: providerDocuments }) => (
              <details key={provider.id} className="rounded-lg border border-line bg-white">
                <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-navy-800">
                  <span>
                    <span className="text-navy-700">{provider.user_full_name}</span>
                    <span className="ml-2 text-xs font-normal text-ink-muted">{provider.business_name || 'Business name not set'}</span>
                  </span>
                  <Badge tone="warning">{providerDocuments.length} document{providerDocuments.length === 1 ? '' : 's'}</Badge>
                </summary>
                <div className="border-t border-line px-4">
                  {providerDocuments.map((doc) => (
                    <div key={doc.id} className="flex flex-wrap items-center justify-between gap-3 border-b border-line py-3 last:border-b-0">
                      <div className="flex items-center gap-3 text-sm">
                        <input type="checkbox" aria-label={`Select ${doc.original_name}`} checked={selectedPending.includes(doc.id)} disabled={doc.status !== 'pending'}
                          onChange={(event) => setSelected(event.target.checked ? [...selectedPending, doc.id] : selectedPending.filter((id) => id !== doc.id))} />
                        <span className="capitalize">{doc.document_type.replaceAll('_', ' ')}<span className="block text-xs text-ink-muted">{doc.original_name}</span></span>
                        <Badge tone={doc.status === 'approved' ? 'success' : doc.status === 'rejected' ? 'danger' : 'warning'}>{doc.status}</Badge>
                      </div>
                      <div className="flex flex-wrap gap-2">
                        <IconActionButton icon="eye" label={`View ${doc.original_name}`} onClick={() => setPreviewDocument(doc)} />
                        {doc.status === 'pending' && <>
                          <IconActionButton icon="check" label={`Approve ${doc.original_name}`} onClick={() => setAction({ kind: 'document', decision: 'approve', id: doc.id })} />
                          <IconActionButton icon="close" label={`Decline ${doc.original_name}`} onClick={() => setAction({ kind: 'document', decision: 'reject', id: doc.id })} />
                        </>}
                      </div>
                    </div>
                  ))}
                </div>
              </details>
            ))}
          </div>
        </div>
      )}
      <Pagination meta={data?.meta} onChange={(next) => { setPage(next); setSelected([]); }} />

      {previewDocument && <VerificationDocumentPreview document={previewDocument} onClose={() => setPreviewDocument(null)} />}

      <ConfirmDialog open={Boolean(action)} onClose={() => { setAction(null); setActionError(null); setReason(''); }}
        onConfirm={runAction} title={action?.kind === 'provider' ? 'Verify this provider?' : action?.kind === 'replacement' ? 'Request a replacement?' : `${action?.decision === 'reject' ? 'Decline' : 'Approve'} selected document${action?.kind === 'bulk' ? 's' : ''}?`}
        description={action?.kind === 'provider' ? 'Both required documents must already be approved.' : action?.kind === 'replacement' ? 'The provider will be allowed to upload a new copy of this approved document.' : 'This decision is recorded in the audit log.'}
        confirmLabel={action?.kind === 'provider' ? 'Verify provider' : action?.kind === 'replacement' ? 'Request replacement' : action?.decision === 'reject' ? 'Decline' : 'Approve'}
        tone={action?.decision === 'reject' ? 'danger' : 'primary'} loading={busy} error={actionError}>
        {action?.decision === 'reject' && <Input label="Reason for declining" required value={reason} onChange={(event) => setReason(event.target.value)} />}
      </ConfirmDialog>
    </Card>
  );
}
