import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { providerApi } from '@/services/api/providerApi';
import { publicApi } from '@/services/api/publicApi';
import { queryKeys } from '@/services/api/queryClient';

export default function ProviderProfilePage() {
  const queryClient = useQueryClient();
  const [notice, setNotice] = useState(null);
  const [form, setForm] = useState(null);
  const [skillsText, setSkillsText] = useState('');
  const [serviceAreaIds, setServiceAreaIds] = useState([]);

  const profileQuery = useQuery({
    queryKey: queryKeys.provider.profile,
    queryFn: providerApi.getMyProfile,
  });

  const { data: barangays = [] } = useQuery({
    queryKey: queryKeys.public.barangays,
    queryFn: publicApi.listBarangays,
  });

  // Re-seed the form whenever a *new* fetch result arrives, without looping
  // through an effect: this runs during render, which React explicitly
  // allows for "adjusting state from props" (see ProfilePage.jsx for the
  // same pattern).
  const [syncedAt, setSyncedAt] = useState(0);
  if (profileQuery.data && profileQuery.dataUpdatedAt !== syncedAt) {
    setSyncedAt(profileQuery.dataUpdatedAt);
    setForm({
      business_name: profileQuery.data.business_name ?? '',
      bio: profileQuery.data.bio ?? '',
      experience_years: profileQuery.data.experience_years ?? '',
      base_barangay_id: profileQuery.data.base_barangay?.id ?? '',
      is_accepting_bookings: profileQuery.data.is_accepting_bookings,
    });
    setSkillsText((profileQuery.data.skills ?? []).join(', '));
    setServiceAreaIds((profileQuery.data.service_areas ?? []).map(String));
  }

  const profileMutation = useMutation({
    mutationFn: providerApi.updateMyProfile,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.provider.profile });
      setNotice({ tone: 'success', message: 'Profile updated successfully.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to update profile.' }),
  });

  const skillsMutation = useMutation({
    mutationFn: providerApi.updateMySkills,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.provider.profile });
      setNotice({ tone: 'success', message: 'Skills updated successfully.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to update skills.' }),
  });

  const serviceAreasMutation = useMutation({
    mutationFn: providerApi.updateMyServiceAreas,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.provider.profile });
      setNotice({ tone: 'success', message: 'Service areas updated successfully.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to update service areas.' }),
  });

  if (profileQuery.isPending || !form) {
    return <LoadingState label="Loading profile…" className="mt-10" />;
  }

  if (profileQuery.isError) {
    return <ErrorState description={profileQuery.error?.message} onRetry={() => profileQuery.refetch()} className="mt-10" />;
  }

  const handleProfileSubmit = (event) => {
    event.preventDefault();
    setNotice(null);
    profileMutation.mutate({
      ...form,
      experience_years: form.experience_years === '' ? null : Number(form.experience_years),
      base_barangay_id: form.base_barangay_id || null,
    });
  };

  const handleSkillsSubmit = (event) => {
    event.preventDefault();
    setNotice(null);
    const skills = skillsText
      .split(',')
      .map((skill) => skill.trim())
      .filter(Boolean);
    skillsMutation.mutate(skills);
  };

  const toggleServiceArea = (id) => {
    setServiceAreaIds((current) =>
      current.includes(id) ? current.filter((existing) => existing !== id) : [...current, id],
    );
  };

  const handleServiceAreasSubmit = (event) => {
    event.preventDefault();
    setNotice(null);
    serviceAreasMutation.mutate(serviceAreaIds.map(Number));
  };

  return (
    <div className="space-y-5">
      {notice && (
        <Alert tone={notice.tone} className="mb-1">
          {notice.message}
        </Alert>
      )}

      <Card title="Business Profile">
        <form onSubmit={handleProfileSubmit} className="space-y-4">
          <Input
            label="Business Name"
            value={form.business_name}
            onChange={(event) => setForm({ ...form, business_name: event.target.value })}
          />

          <div>
            <label htmlFor="bio" className="mb-1.5 block text-sm font-medium text-ink">
              Bio
            </label>
            <textarea
              id="bio"
              rows={4}
              value={form.bio}
              onChange={(event) => setForm({ ...form, bio: event.target.value })}
              className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Years of Experience"
              type="number"
              min="0"
              value={form.experience_years}
              onChange={(event) => setForm({ ...form, experience_years: event.target.value })}
            />

            <div>
              <label htmlFor="base-barangay" className="mb-1.5 block text-sm font-medium text-ink">
                Base Barangay
              </label>
              <select
                id="base-barangay"
                value={form.base_barangay_id}
                onChange={(event) => setForm({ ...form, base_barangay_id: event.target.value })}
                className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
              >
                <option value="">Select a barangay</option>
                {barangays.map((barangay) => (
                  <option key={barangay.id} value={barangay.id}>
                    {barangay.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <label className="flex items-center gap-2 text-sm text-ink">
            <input
              type="checkbox"
              checked={form.is_accepting_bookings}
              onChange={(event) => setForm({ ...form, is_accepting_bookings: event.target.checked })}
              className="h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
            />
            Currently accepting bookings
          </label>

          <Button type="submit" loading={profileMutation.isPending}>
            Save profile
          </Button>
        </form>
      </Card>

      <Card title="Skills">
        <form onSubmit={handleSkillsSubmit} className="space-y-4">
          <Input
            label="Skills"
            hint="Separate each skill with a comma."
            value={skillsText}
            onChange={(event) => setSkillsText(event.target.value)}
            placeholder="Pipe fitting, Leak repair, Faucet installation"
          />
          <Button type="submit" loading={skillsMutation.isPending}>
            Save skills
          </Button>
        </form>
      </Card>

      <Card title="Service Areas">
        <form onSubmit={handleServiceAreasSubmit} className="space-y-4">
          <p className="text-sm text-ink-muted">Select the barangays where you provide services.</p>
          <div className="grid max-h-60 grid-cols-2 gap-2 overflow-y-auto rounded-lg border border-line p-3 sm:grid-cols-3">
            {barangays.map((barangay) => (
              <label key={barangay.id} className="flex items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  checked={serviceAreaIds.includes(String(barangay.id))}
                  onChange={() => toggleServiceArea(String(barangay.id))}
                  className="h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
                />
                {barangay.name}
              </label>
            ))}
          </div>
          <Button type="submit" loading={serviceAreasMutation.isPending}>
            Save service areas
          </Button>
        </form>
      </Card>
    </div>
  );
}
