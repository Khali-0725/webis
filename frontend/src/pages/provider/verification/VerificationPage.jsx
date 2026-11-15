import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { verificationApi } from '@/services/api/verificationApi';
import { Badge } from '@/components/ui/Badge';
import { VerifiedBadge } from '@/components/ui/VerifiedBadge';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { useAuth } from '@/hooks/useAuth';
import { VerificationDocumentPreview } from '@/components/verification/VerificationDocumentPreview';

const REQUIRED_DOCUMENTS = [
  { value: 'government_id', label: 'Government-issued ID' },
  { value: 'business_permit', label: 'Business Permit' },
];

export default function ProviderVerificationPage() {
  const queryClient = useQueryClient();
  const { refreshUser } = useAuth();
  const statusQuery = useQuery({ queryKey: ['provider', 'verification'], queryFn: verificationApi.myStatus });
  const [uploadingType, setUploadingType] = useState(null);
  const [files, setFiles] = useState({ government_id: null, business_permit: null });
  const [notice, setNotice] = useState(null);
  const [previewDocument, setPreviewDocument] = useState(null);
  const documents = Array.isArray(statusQuery.data?.documents) ? statusQuery.data.documents : [];

  const handleSubmit = async (e, documentType) => {
    e.preventDefault();
    const file = files[documentType];
    if (!file) return;

    setNotice(null);
    setUploadingType(documentType);

    const formData = new FormData();
    formData.append('document', file);
    formData.append('document_type', documentType);

    try {
      await verificationApi.uploadDocument(formData);
      await queryClient.invalidateQueries({ queryKey: ['provider', 'verification'] });
      await refreshUser();
      setNotice({ tone: 'success', message: 'Document submitted for administrator review.' });
      setFiles((current) => ({ ...current, [documentType]: null }));
    } catch (error) {
      setNotice({
        tone: 'error',
        message: error?.message ?? 'Failed to upload document.',
      });
    } finally {
      setUploadingType(null);
    }
  };

  return (
    <Card title={<span className="inline-flex flex-wrap items-center gap-2">Provider verification {statusQuery.data?.verification_status === 'approved' && <VerifiedBadge verified />}</span>} className="mx-auto max-w-2xl">
      <p className="mb-4 text-sm text-ink-muted">
        Upload a government-issued ID and a business permit. An administrator reviews each document, then makes a final provider decision. Business features remain unavailable until approval.
      </p>
      <p className="mb-4 text-xs text-ink-muted">
        WEBIS verification means documents were reviewed by an administrator. It does not establish government validation, background clearance, or a guarantee of safety.
      </p>
      {statusQuery.data?.verification_status === 'approved' && (
        <Alert tone="warning" className="mb-4">
          Uploading a replacement government ID or business permit will pause your verified status and provider business access until both current required documents receive final approval again.
        </Alert>
      )}
      {statusQuery.isPending && <LoadingState label="Loading verification status…" />}
      {statusQuery.isError && <ErrorState description={statusQuery.error?.message} onRetry={() => statusQuery.refetch()} />}
      {statusQuery.data && (
        <div className="mb-5 rounded-lg bg-navy-50 p-4">
          {statusQuery.data.verification_status !== 'approved' && <div className="flex items-center gap-2 text-sm font-medium text-ink">
            Overall status: <Badge tone={statusQuery.data.verification_status === 'rejected' ? 'danger' : 'warning'}>{statusQuery.data.verification_status}</Badge>
          </div>}
          {statusQuery.data.rejection_reason && <p className="mt-2 text-sm text-ink-muted">Reason: {statusQuery.data.rejection_reason}</p>}
          <ul className="mt-3 space-y-1 text-sm text-ink-muted">
            {['government_id', 'business_permit'].map((requiredType) => {
              const current = statusQuery.data.documents.find((doc) => doc.document_type === requiredType);
              return <li key={requiredType}>{requiredType === 'government_id' ? 'Government-issued ID' : 'Business permit'}: {current?.status === 'approved' ? 'Approved' : current?.status === 'rejected' ? 'Rejected — replace this document' : 'Required or under review'}</li>;
            })}
          </ul>
          {documents.length > 0 && (
            <div className="mt-4 border-t border-line pt-3">
              <p className="mb-2 text-sm font-medium">Submitted documents</p>
              {documents.map((doc) => (
                <div key={doc.id} className="flex flex-wrap items-center justify-between gap-2 py-1 text-sm">
                  <span>{doc.original_name} ({doc.document_type.replaceAll('_', ' ')})</span>
                  <span>{doc.status} · <button type="button" onClick={() => setPreviewDocument(doc)} aria-label={`View ${doc.original_name}`} className="text-brand underline">View</button></span>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <div className="space-y-4">
        {REQUIRED_DOCUMENTS.map((document) => (
          <form key={document.value} onSubmit={(event) => handleSubmit(event, document.value)} className="rounded-lg border border-line p-4">
            <label htmlFor={`${document.value}-file`} className="mb-1 block text-sm font-semibold text-ink">
              {document.label}
            </label>
            <p className="mb-2 text-xs text-ink-muted">JPEG, PNG, WebP, or PDF. Maximum 4 MB.</p>
            <input
              id={`${document.value}-file`}
              type="file"
              accept="image/jpeg,image/png,image/webp,application/pdf"
              onChange={(e) => setFiles((current) => ({ ...current, [document.value]: e.target.files[0] ?? null }))}
              className="block w-full text-sm text-ink-muted file:mr-4 file:rounded-lg file:border-0 file:bg-navy-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-navy-700 hover:file:bg-navy-100"
            />
            <Button type="submit" className="mt-3" loading={uploadingType === document.value} disabled={!files[document.value] || Boolean(uploadingType)}>
              Upload {document.label}
            </Button>
          </form>
        ))}
      </div>
      {previewDocument && <VerificationDocumentPreview document={previewDocument} onClose={() => setPreviewDocument(null)} />}
    </Card>
  );
}
