import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { verificationApi } from '@/services/api/verificationApi';
import { Badge } from '@/components/ui/Badge';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { useAuth } from '@/hooks/useAuth';

const DOCUMENT_TYPES = [
  { value: 'government_id', label: 'Government-issued ID' },
  { value: 'barangay_clearance', label: 'Barangay Clearance' },
  { value: 'nbi_clearance', label: 'NBI Clearance' },
  { value: 'skill_certificate', label: 'Skill Certificate / TESDA' },
  { value: 'business_permit', label: 'Business Permit' },
  { value: 'other', label: 'Other Document' },
];

export default function ProviderVerificationPage() {
  const queryClient = useQueryClient();
  const { refreshUser } = useAuth();
  const statusQuery = useQuery({ queryKey: ['provider', 'verification'], queryFn: verificationApi.myStatus });
  const [loading, setLoading] = useState(false);
  const [file, setFile] = useState(null);
  const [type, setType] = useState(DOCUMENT_TYPES[0].value);
  const [notice, setNotice] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!file) return;

    setNotice(null);
    setLoading(true);

    const formData = new FormData();
    formData.append('document', file);
    formData.append('document_type', type);

    try {
      await verificationApi.uploadDocument(formData);
      await queryClient.invalidateQueries({ queryKey: ['provider', 'verification'] });
      await refreshUser();
      setNotice({ tone: 'success', message: 'Document submitted for administrator review.' });
      setFile(null);
    } catch (error) {
      setNotice({
        tone: 'error',
        message: error?.message ?? 'Failed to upload document.',
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Card title="Provider verification" className="mx-auto max-w-2xl">
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
          <div className="flex items-center gap-2 text-sm font-medium text-ink">
            Overall status: <Badge tone={statusQuery.data.verification_status === 'approved' ? 'success' : statusQuery.data.verification_status === 'rejected' ? 'danger' : 'warning'}>
              {statusQuery.data.verification_status}
            </Badge>
          </div>
          {statusQuery.data.rejection_reason && <p className="mt-2 text-sm text-ink-muted">Reason: {statusQuery.data.rejection_reason}</p>}
          <ul className="mt-3 space-y-1 text-sm text-ink-muted">
            {['government_id', 'business_permit'].map((requiredType) => {
              const current = statusQuery.data.documents.find((doc) => doc.document_type === requiredType);
              return <li key={requiredType}>{requiredType === 'government_id' ? 'Government-issued ID' : 'Business permit'}: {current?.status === 'approved' ? 'Approved' : current?.status === 'rejected' ? 'Rejected — replace this document' : 'Required or under review'}</li>;
            })}
          </ul>
          {statusQuery.data.documents.length > 0 && (
            <div className="mt-4 border-t border-line pt-3">
              <p className="mb-2 text-sm font-medium">Submitted documents</p>
              {statusQuery.data.documents.map((doc) => (
                <div key={doc.id} className="flex flex-wrap items-center justify-between gap-2 py-1 text-sm">
                  <span>{doc.original_name} ({doc.document_type.replaceAll('_', ' ')})</span>
                  <span>{doc.status} · <a href={doc.preview_url} target="_blank" rel="noreferrer" className="text-brand underline">View</a></span>
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

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="document-type" className="mb-1 block text-sm font-medium text-ink">
            Document type
          </label>
          <select
            id="document-type"
            value={type}
            onChange={(e) => setType(e.target.value)}
            className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none focus:ring-1 focus:ring-navy-500"
          >
            {DOCUMENT_TYPES.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="document-file" className="mb-1 block text-sm font-medium text-ink">
            File
          </label>
          <input
            id="document-file"
            type="file"
            accept="image/jpeg,image/png,image/webp,application/pdf"
            onChange={(e) => setFile(e.target.files[0])}
            className="block w-full text-sm text-ink-muted file:mr-4 file:rounded-lg file:border-0 file:bg-navy-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-navy-700 hover:file:bg-navy-100"
          />
        </div>

        <Button type="submit" loading={loading} disabled={!file}>
          Upload document
        </Button>
      </form>
    </Card>
  );
}
