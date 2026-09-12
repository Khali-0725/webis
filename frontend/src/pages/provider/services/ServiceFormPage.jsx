import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { providerApi } from '@/services/api/providerApi';
import { publicApi } from '@/services/api/publicApi';
import { queryKeys } from '@/services/api/queryClient';
import { PRICING_TYPE_META } from '@/constants';

const EMPTY_FORM = {
  service_category_id: '',
  title: '',
  description: '',
  pricing_type: 'fixed',
  price: '',
  min_price: '',
  max_price: '',
  duration_minutes: '',
};

export default function ServiceFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditing = Boolean(id);
  const [form, setForm] = useState(EMPTY_FORM);
  const [formError, setFormError] = useState(null);

  const { data: categories = [] } = useQuery({
    queryKey: queryKeys.public.categories,
    queryFn: publicApi.listCategories,
  });

  const profileQuery = useQuery({
    queryKey: queryKeys.provider.profile,
    queryFn: providerApi.getMyProfile,
    enabled: !isEditing,
  });

  const serviceQuery = useQuery({
    queryKey: ['provider', 'service', id],
    queryFn: () => providerApi.getService(id),
    enabled: isEditing,
  });

  // Adjust-during-render, not an effect - see ProviderProfilePage.jsx for why.
  const [syncedAt, setSyncedAt] = useState(0);
  if (serviceQuery.data && serviceQuery.dataUpdatedAt !== syncedAt) {
    setSyncedAt(serviceQuery.dataUpdatedAt);
    setForm({
      service_category_id: serviceQuery.data.category?.id ?? '',
      title: serviceQuery.data.title,
      description: serviceQuery.data.description,
      pricing_type: serviceQuery.data.pricing_type,
      price: serviceQuery.data.price ?? '',
      min_price: serviceQuery.data.min_price ?? '',
      max_price: serviceQuery.data.max_price ?? '',
      duration_minutes: serviceQuery.data.duration_minutes ?? '',
    });
  }

  const mutation = useMutation({
    mutationFn: (payload) =>
      isEditing ? providerApi.updateService(id, payload) : providerApi.createService(payload),
    onSuccess: () => navigate('/provider/services'),
    onError: (error) => setFormError(error?.message ?? 'Failed to save the service.'),
  });

  if (isEditing && serviceQuery.isPending) {
    return <LoadingState label="Loading service…" className="mt-10" />;
  }

  if (isEditing && serviceQuery.isError) {
    return (
      <ErrorState
        description={serviceQuery.error?.message}
        onRetry={() => serviceQuery.refetch()}
        className="mt-10"
      />
    );
  }

  if (!isEditing && profileQuery.isPending) {
    return <LoadingState label="Checking verification status…" className="mt-10" />;
  }

  if (!isEditing && profileQuery.data && profileQuery.data.verification_status !== 'approved') {
    return (
      <Card title="Verification required" className="mx-auto max-w-2xl">
        <Alert tone="warning" title="Your provider account isn't verified yet">
          You need an approved verification before you can add a service. Submit your documents
          and an administrator will review them.
        </Alert>
        <Link to="/provider/verification" className="mt-4 inline-block">
          <Button>Go to verification</Button>
        </Link>
      </Card>
    );
  }

  const handleSubmit = (event) => {
    event.preventDefault();
    setFormError(null);

    const payload = {
      ...form,
      price: form.price === '' ? null : Number(form.price),
      min_price: form.min_price === '' ? null : Number(form.min_price),
      max_price: form.max_price === '' ? null : Number(form.max_price),
      duration_minutes: form.duration_minutes === '' ? null : Number(form.duration_minutes),
    };

    mutation.mutate(payload);
  };

  return (
    <Card title={isEditing ? 'Edit Service' : 'Create Service'} className="mx-auto max-w-2xl">
      {formError && (
        <Alert tone="error" className="mb-4">
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="category" className="mb-1.5 block text-sm font-medium text-ink">
            Category<span className="ml-0.5 text-red-600">*</span>
          </label>
          <select
            id="category"
            required
            value={form.service_category_id}
            onChange={(event) => setForm({ ...form, service_category_id: event.target.value })}
            className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
          >
            <option value="">Select a category</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </div>

        <Input
          label="Title"
          required
          value={form.title}
          onChange={(event) => setForm({ ...form, title: event.target.value })}
          placeholder="e.g. Emergency pipe repair"
        />

        <div>
          <label htmlFor="description" className="mb-1.5 block text-sm font-medium text-ink">
            Description<span className="ml-0.5 text-red-600">*</span>
          </label>
          <textarea
            id="description"
            required
            rows={4}
            minLength={20}
            value={form.description}
            onChange={(event) => setForm({ ...form, description: event.target.value })}
            className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
          />
        </div>

        <div>
          <label htmlFor="pricing-type" className="mb-1.5 block text-sm font-medium text-ink">
            Pricing
          </label>
          <select
            id="pricing-type"
            value={form.pricing_type}
            onChange={(event) => setForm({ ...form, pricing_type: event.target.value })}
            className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
          >
            {Object.entries(PRICING_TYPE_META).map(([value, meta]) => (
              <option key={value} value={value}>
                {meta.label}
              </option>
            ))}
          </select>
        </div>

        {form.pricing_type === 'quote' ? (
          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Min Price (₱)"
              type="number"
              min="0"
              value={form.min_price}
              onChange={(event) => setForm({ ...form, min_price: event.target.value })}
            />
            <Input
              label="Max Price (₱)"
              type="number"
              min="0"
              value={form.max_price}
              onChange={(event) => setForm({ ...form, max_price: event.target.value })}
            />
          </div>
        ) : (
          <Input
            label={form.pricing_type === 'hourly' ? 'Price per hour (₱)' : 'Price (₱)'}
            type="number"
            min="0"
            required
            value={form.price}
            onChange={(event) => setForm({ ...form, price: event.target.value })}
          />
        )}

        <Input
          label="Duration (minutes)"
          type="number"
          min="5"
          hint="Optional. Estimated time to complete the service."
          value={form.duration_minutes}
          onChange={(event) => setForm({ ...form, duration_minutes: event.target.value })}
        />

        <Button type="submit" loading={mutation.isPending}>
          {isEditing ? 'Save changes' : 'Create service'}
        </Button>
      </form>
    </Card>
  );
}
