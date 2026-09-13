import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { LoadingState, EmptyState } from '@/components/ui/States';
import { paymentMethodApi } from '@/services/api/paymentMethodApi';
import { queryKeys } from '@/services/api/queryClient';
import { PAYMENT_METHOD_TYPE_META } from '@/constants';

const EMPTY_FORM = {
  type: 'gcash',
  account_name: '',
  account_ref_masked: '',
  instructions: '',
  is_default: false,
};

export default function PaymentMethodsPage() {
  const queryClient = useQueryClient();
  const [form, setForm] = useState(EMPTY_FORM);
  const [qrImage, setQrImage] = useState(null);
  const [notice, setNotice] = useState(null);

  const { data: methods = [], isPending } = useQuery({
    queryKey: queryKeys.provider.paymentMethods,
    queryFn: paymentMethodApi.list,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.provider.paymentMethods });

  const createMutation = useMutation({
    mutationFn: paymentMethodApi.create,
    onSuccess: () => {
      invalidate();
      setForm(EMPTY_FORM);
      setQrImage(null);
      setNotice({ tone: 'success', message: 'Payment method added.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to add the payment method.' }),
  });

  const toggleMutation = useMutation({
    mutationFn: paymentMethodApi.toggle,
    onSuccess: invalidate,
  });

  const usesQrImage = PAYMENT_METHOD_TYPE_META[form.type]?.usesQrImage;

  const handleSubmit = (event) => {
    event.preventDefault();
    setNotice(null);
    createMutation.mutate({ ...form, qr_image: qrImage ?? undefined });
  };

  return (
    <div className="space-y-5">
      {notice && <Alert tone={notice.tone}>{notice.message}</Alert>}

      <Card title="Add a payment method">
        <form onSubmit={handleSubmit} className="grid gap-4 sm:grid-cols-2">
          <div>
            <label htmlFor="pm-type" className="mb-1.5 block text-sm font-medium text-ink">
              Type
            </label>
            <select
              id="pm-type"
              value={form.type}
              onChange={(event) => setForm({ ...form, type: event.target.value })}
              className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
            >
              {Object.entries(PAYMENT_METHOD_TYPE_META).map(([value, meta]) => (
                <option key={value} value={value}>
                  {meta.label}
                </option>
              ))}
            </select>
          </div>

          <Input
            label="Account / display name"
            required
            value={form.account_name}
            onChange={(event) => setForm({ ...form, account_name: event.target.value })}
            placeholder="e.g. Juan Dela Cruz"
          />

          <Input
            label="Account reference (masked)"
            hint="Only the last few digits, e.g. **** 1234"
            value={form.account_ref_masked}
            onChange={(event) => setForm({ ...form, account_ref_masked: event.target.value })}
          />

          {usesQrImage && (
            <div>
              <label htmlFor="pm-qr" className="mb-1.5 block text-sm font-medium text-ink">
                QR code image<span className="ml-0.5 text-red-600">*</span>
              </label>
              <input
                id="pm-qr"
                type="file"
                accept="image/*"
                required
                onChange={(event) => setQrImage(event.target.files?.[0] ?? null)}
                className="block w-full text-sm text-ink"
              />
            </div>
          )}

          <div className="sm:col-span-2">
            <label htmlFor="pm-instructions" className="mb-1.5 block text-sm font-medium text-ink">
              Instructions for the client
            </label>
            <textarea
              id="pm-instructions"
              rows={2}
              value={form.instructions}
              onChange={(event) => setForm({ ...form, instructions: event.target.value })}
              className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
              placeholder="e.g. Send the exact amount and keep your receipt."
            />
          </div>

          <label className="flex items-center gap-2 text-sm text-ink">
            <input
              type="checkbox"
              checked={form.is_default}
              onChange={(event) => setForm({ ...form, is_default: event.target.checked })}
              className="h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
            />
            Make this my default payment method
          </label>

          <div className="sm:col-span-2">
            <Button type="submit" loading={createMutation.isPending}>
              Add payment method
            </Button>
          </div>
        </form>
      </Card>

      <Card title="Your payment methods">
        {isPending && <LoadingState label="Loading payment methods…" />}

        {!isPending && methods.length === 0 && (
          <EmptyState
            title="No payment methods yet"
            description="Add a GCash, Maya, QR Ph, or bank account above so clients can pay you."
          />
        )}

        {!isPending && methods.length > 0 && (
          <ul className="divide-y divide-line">
            {methods.map((method) => (
              <li key={method.id} className="flex items-center gap-4 py-3">
                {method.qr_image_url ? (
                  <img
                    src={method.qr_image_url}
                    alt={`${method.type_label} QR code`}
                    crossOrigin="use-credentials"
                    className="h-16 w-16 rounded-lg border border-line object-cover"
                  />
                ) : (
                  <div className="grid h-16 w-16 place-items-center rounded-lg border border-line text-xs text-ink-muted">
                    No QR
                  </div>
                )}

                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <p className="font-medium text-ink">{method.type_label}</p>
                    {method.is_default && <Badge tone="brand">Default</Badge>}
                    <Badge tone={method.is_active ? 'success' : 'neutral'}>
                      {method.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </div>
                  <p className="text-sm text-ink-muted">
                    {method.account_name}
                    {method.account_ref_masked ? ` · ${method.account_ref_masked}` : ''}
                  </p>
                </div>

                <Button
                  size="sm"
                  variant={method.is_active ? 'subtle' : 'outline'}
                  onClick={() => toggleMutation.mutate(method.id)}
                  loading={toggleMutation.isPending && toggleMutation.variables === method.id}
                >
                  {method.is_active ? 'Deactivate' : 'Activate'}
                </Button>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </div>
  );
}
