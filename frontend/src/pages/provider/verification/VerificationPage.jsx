import { useState } from 'react';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { verificationApi } from '@/services/api/verificationApi';

const DOCUMENT_TYPES = [
  { value: 'government_id', label: 'Government-issued ID' },
  { value: 'barangay_clearance', label: 'Barangay Clearance' },
  { value: 'nbi_clearance', label: 'NBI Clearance' },
  { value: 'skill_certificate', label: 'Skill Certificate / TESDA' },
  { value: 'business_permit', label: 'Business Permit' },
  { value: 'other', label: 'Other Document' },
];

export default function ProviderVerificationPage() {
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
      setNotice({ tone: 'success', message: 'Document uploaded successfully.' });
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
    <Card title="Upload Verification Documents" className="mx-auto max-w-md">
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
