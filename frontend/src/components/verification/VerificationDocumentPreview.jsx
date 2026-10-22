import { useEffect, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Modal } from '@/components/ui/Modal';
import { LoadingState } from '@/components/ui/States';
import { verificationApi } from '@/services/api/verificationApi';

export function VerificationDocumentPreview({ document, onClose }) {
  const [preview, setPreview] = useState(null);
  const [error, setError] = useState(null);
  const documentId = document.id;

  useEffect(() => {
    let active = true;
    let objectUrl;
    verificationApi.previewDocument(documentId).then((file) => {
      if (!active) return;
      if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
        throw new Error('This file type cannot be previewed.');
      }
      objectUrl = URL.createObjectURL(file);
      setPreview({ url: objectUrl, type: file.type });
    }).catch((failure) => {
      if (active) setError(failure?.message ?? 'This document could not be opened.');
    });

    return () => {
      active = false;
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [documentId]);

  return (
    <Modal open onClose={onClose} title={document.original_name} size="lg" className="max-w-4xl">
      {error && <Alert tone="error">{error}</Alert>}
      {!error && !preview && <LoadingState label="Loading document…" />}
      {preview?.type.startsWith('image/') && <img src={preview.url} alt={document.original_name} className="mx-auto max-h-[65vh] max-w-full object-contain" />}
      {preview?.type === 'application/pdf' && <iframe src={preview.url} title={document.original_name} className="h-[65vh] w-full" />}
    </Modal>
  );
}
