import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { settingsApi } from '@/services/api/admin/settingsApi';
import { queryKeys } from '@/services/api/queryClient';

const KNOWN_KEYS = [
  { key: 'platform.name', label: 'Platform name' },
  { key: 'booking.min_lead_hours', label: 'Minimum booking lead time (hours)' },
  { key: 'booking.max_advance_days', label: 'Maximum booking advance (days)' },
];

export default function SettingsPage() {
  const queryClient = useQueryClient();
  const [notice, setNotice] = useState(null);
  const [values, setValues] = useState(null);
  const [syncedAt, setSyncedAt] = useState(0);

  const { data: rows, dataUpdatedAt, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.settings,
    queryFn: settingsApi.list,
  });

  if (rows && dataUpdatedAt !== syncedAt) {
    const map = Object.fromEntries(rows.map((r) => [r.key, r.value]));
    setValues(Object.fromEntries(KNOWN_KEYS.map(({ key }) => [key, map[key] ?? ''])));
    setSyncedAt(dataUpdatedAt);
  }

  const updateMutation = useMutation({
    mutationFn: (settings) => settingsApi.update(settings),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.admin.settings });
      setNotice({ tone: 'success', message: 'Settings updated.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to update settings.' }),
  });

  const handleSubmit = (event) => {
    event.preventDefault();
    setNotice(null);
    updateMutation.mutate(KNOWN_KEYS.map(({ key }) => ({ key, value: values[key] })));
  };

  if (isPending || !values) return <LoadingState label="Loading settings…" />;
  if (isError) return <ErrorState description={error?.message} onRetry={() => refetch()} />;

  return (
    <Card title="Platform Settings">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        {KNOWN_KEYS.map(({ key, label }) => (
          <Input
            key={key}
            label={label}
            value={values[key] ?? ''}
            onChange={(e) => setValues({ ...values, [key]: e.target.value })}
          />
        ))}
        <Button type="submit" loading={updateMutation.isPending}>
          Save settings
        </Button>
      </form>
    </Card>
  );
}
