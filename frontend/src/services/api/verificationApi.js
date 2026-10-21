import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const verificationApi = {
  /** POST /api/provider/verification/documents */
  async uploadDocument(formData) {
    const response = await api.post('/provider/verification/documents', formData);
    return unwrap(response);
  },

  async myStatus() {
    const response = await api.get('/provider/verification');
    return unwrap(response);
  },

  /** GET /api/admin/verification/documents?status= */
  async listDocuments(params = {}) {
    const response = await api.get('/admin/verification/documents', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  /** PATCH /api/admin/verification/documents/{document}/approve */
  async approveDocument(documentId) {
    const response = await api.patch(`/admin/verification/documents/${documentId}/approve`);
    return unwrap(response);
  },

  /** PATCH /api/admin/verification/documents/{document}/reject */
  async rejectDocument(documentId, reason) {
    const response = await api.patch(`/admin/verification/documents/${documentId}/reject`, { reason });
    return unwrap(response);
  },

  async listProviders(params = {}) {
    const response = await api.get('/admin/verification/providers', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  async approveProvider(providerId) {
    const response = await api.patch(`/admin/verification/providers/${providerId}/approve`);
    return unwrap(response);
  },

  async bulk(documentIds, action, reason) {
    const response = await api.post('/admin/verification/documents/bulk', {
      document_ids: documentIds,
      action,
      ...(reason ? { reason } : {}),
    });
    return unwrap(response);
  },
};
