import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { api } from '@/services/api/client';
import AdminVerificationPage from '@/pages/admin/verification/VerificationPage';
import ProviderVerificationPage from '@/pages/provider/verification/VerificationPage';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

const document = {
  id: 1,
  document_type: 'government_id',
  original_name: 'government-id.jpg',
  preview_url: '/api/files/verification-document/1',
  status: 'approved',
};

const profile = {
  id: 2,
  business_name: null,
  owner_name: 'Joshua Apilado',
  verification_status: 'pending',
  rejection_reason: null,
  required_documents: { government_id: 'approved', business_permit: 'missing' },
  documents: [document],
};

const apiResponse = (data, meta) => ({ data: { success: true, data, meta } });
let originalCreateObjectURL;
let originalRevokeObjectURL;

beforeEach(() => {
  resetSession();
  originalCreateObjectURL = URL.createObjectURL;
  originalRevokeObjectURL = URL.revokeObjectURL;
  URL.createObjectURL = vi.fn(() => 'blob:verification-preview');
  URL.revokeObjectURL = vi.fn();
});

afterEach(() => {
  URL.createObjectURL = originalCreateObjectURL;
  URL.revokeObjectURL = originalRevokeObjectURL;
  vi.restoreAllMocks();
});

describe('verification document preview', () => {
  it('shows an admin the private image inside WEBIS and explains the missing permit', async () => {
    setSession(makeUser({ role: 'admin' }));
    const get = vi.spyOn(api, 'get').mockImplementation((path, options) => {
      if (path === '/admin/verification/documents') {
        return Promise.resolve(apiResponse([], { counts: { pending: 0, verified: 1, unverified: 1, rejected: 0 } }));
      }
      if (path === '/admin/verification/providers') {
        return Promise.resolve(apiResponse([profile], { current_page: 1, last_page: 1 }));
      }
      if (path === '/files/verification-document/1' && options?.responseType === 'blob') {
        return Promise.resolve({ data: new Blob(['image'], { type: 'image/jpeg' }) });
      }
      return Promise.reject(new Error(`Unexpected request: ${path}`));
    });
    const user = userEvent.setup();
    renderWithProviders(<AdminVerificationPage />);

    await user.click(screen.getByRole('tab', { name: /Unverified/ }));
    expect(await screen.findByText('Joshua Apilado')).toBeInTheDocument();
    expect(screen.getByText(/business permit.*missing/i)).toBeInTheDocument();
    expect(screen.getByText(/both required documents.*Verify provider/i)).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'View government-id.jpg' }));

    expect(await screen.findByRole('img', { name: 'government-id.jpg' })).toHaveAttribute('src', 'blob:verification-preview');
    expect(get).toHaveBeenCalledWith('/files/verification-document/1', { responseType: 'blob' });
    await user.click(screen.getByRole('button', { name: 'Close' }));
    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:verification-preview');
  });

  it('shows the provider a PDF preview without direct file navigation', async () => {
    setSession(makeUser({ role: 'provider' }));
    vi.spyOn(api, 'get').mockImplementation((path, options) => {
      if (path === '/provider/verification') {
        return Promise.resolve(apiResponse({ verification_status: 'pending', rejection_reason: null, documents: [{ ...document, original_name: 'permit.pdf', document_type: 'business_permit' }] }));
      }
      if (path === '/files/verification-document/1' && options?.responseType === 'blob') {
        return Promise.resolve({ data: new Blob(['%PDF-test'], { type: 'application/pdf' }) });
      }
      return Promise.reject(new Error(`Unexpected request: ${path}`));
    });
    const user = userEvent.setup();
    renderWithProviders(<ProviderVerificationPage />);

    await user.click(await screen.findByRole('button', { name: 'View permit.pdf' }));
    expect(await screen.findByTitle('permit.pdf')).toHaveAttribute('src', 'blob:verification-preview');
  });

  it('shows a readable error when the signed-in file request fails', async () => {
    setSession(makeUser({ role: 'admin' }));
    vi.spyOn(api, 'get').mockImplementation((path) => {
      if (path === '/admin/verification/documents') {
        return Promise.resolve(apiResponse([], { counts: { pending: 0, verified: 1, unverified: 1, rejected: 0 } }));
      }
      if (path === '/admin/verification/providers') {
        return Promise.resolve(apiResponse([profile], { current_page: 1, last_page: 1 }));
      }
      return Promise.reject({ message: 'This document could not be opened.' });
    });
    const user = userEvent.setup();
    renderWithProviders(<AdminVerificationPage />);

    await user.click(screen.getByRole('tab', { name: /Unverified/ }));
    await user.click(await screen.findByRole('button', { name: 'View government-id.jpg' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('This document could not be opened.');
  });
});
