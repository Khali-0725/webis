import { api, unwrap } from '@/services/api/client';

export const verificationApi = {
  /** POST /api/provider/verification/documents */
  async uploadDocument(formData) {
    const response = await api.post('/provider/verification/documents', formData);
    return unwrap(response);
  },

  /** GET /api/admin/verification/documents?status= */
  async listPendingDocuments(status = 'pending') {
    const response = await api.get('/admin/verification/documents', { params: { status } });
    return unwrap(response);
  },

  /** PATCH /api/admin/verification/documents/{document}/approve */
  async approveDocument(documentId) {
    const response = await api.patch(`/admin/verification/documents/${documentId}/approve`);
    return unwrap(response);
  },

  /** PATCH /api/admin/verification/documents/{document}/reject */
  async rejectDocument(documentId) {
    const response = await api.patch(`/admin/verification/documents/${documentId}/reject`);
    return unwrap(response);
  },
};
