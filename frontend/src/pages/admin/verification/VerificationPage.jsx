import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { LoadingState, EmptyState, ErrorState } from '@/components/ui/States';
import { verificationApi } from '@/services/api/verificationApi';
import { queryKeys } from '@/services/api/queryClient';

export default function AdminVerificationPage() {
  const queryClient = useQueryClient();
  const [notice, setNotice] = useState(null);

  const {
    data: documents = [],
    isPending,
    isError,
    error,
    refetch,
  } = useQuery({
    queryKey: queryKeys.admin.verificationDocuments('pending'),
    queryFn: () => verificationApi.listPendingDocuments('pending'),
  });

  const handleAction = async (id, action) => {
    setNotice(null);

    try {
      if (action === 'approve') await verificationApi.approveDocument(id);
      else await verificationApi.rejectDocument(id);

      await queryClient.invalidateQueries({ queryKey: ['admin', 'verification-documents'] });
    } catch (err) {
      setNotice({ tone: 'error', message: err?.message ?? 'Failed to update the document.' });
    }
  };

  return (
    <Card title="Pending Provider Documents">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      {isPending && <LoadingState label="Loading documents…" />}

      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && documents.length === 0 && (
        <EmptyState
          title="No pending documents"
          description="Provider verification submissions will appear here for review."
        />
      )}

      {!isPending && !isError && documents.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Provider</th>
                <th className="py-2 pr-4 font-medium">Email</th>
                <th className="py-2 pr-4 font-medium">Document type</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {documents.map((doc) => (
                <tr key={doc.id}>
                  <td className="py-3 pr-4 text-ink">
                    <div className="font-medium">{doc.provider.user_full_name}</div>
                    <div className="text-xs text-ink-muted">{doc.provider.business_name}</div>
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{doc.provider.user_email}</td>
                  <td className="py-3 pr-4 text-ink-muted">{doc.document_type}</td>
                  <td className="py-3">
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        variant="success"
                        onClick={() => handleAction(doc.id, 'approve')}
                      >
                        Approve
                      </Button>
                      <Button
                        size="sm"
                        variant="danger"
                        onClick={() => handleAction(doc.id, 'reject')}
                      >
                        Reject
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </Card>
  );
}
