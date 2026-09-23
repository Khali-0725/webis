import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Textarea } from '@/components/ui/Select';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { fieldError } from '@/services/api/client';
import { providerApi } from '@/services/api/providerApi';
import { publicApi } from '@/services/api/publicApi';
import { queryKeys } from '@/services/api/queryClient';

const EMPTY_WORK_EXPERIENCE_FORM = {
  role_title: '',
  employer_name: '',
  description: '',
  started_on: '',
  ended_on: '',
  is_current: false,
};

function formatMonthYear(dateString) {
  if (!dateString) return '';
  return new Date(`${dateString}T00:00:00`).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

export default function ProviderProfilePage() {
  const queryClient = useQueryClient();
  const [notice, setNotice] = useState(null);
  const [form, setForm] = useState(null);
  const [skillsText, setSkillsText] = useState('');
  const [serviceAreaIds, setServiceAreaIds] = useState([]);
  const [weForm, setWeForm] = useState(EMPTY_WORK_EXPERIENCE_FORM);
  const [editingExperience, setEditingExperience] = useState(null);
  const [pendingDeleteExperience, setPendingDeleteExperience] = useState(null);

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
      birthdate: profileQuery.data.birthdate ?? '',
      show_age_publicly: profileQuery.data.show_age_publicly ?? false,
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

  const workExperiencesQuery = useQuery({
    queryKey: queryKeys.provider.workExperiences,
    queryFn: providerApi.listMyWorkExperiences,
  });

  const invalidateWorkExperiences = () =>
    queryClient.invalidateQueries({ queryKey: queryKeys.provider.workExperiences });

  const createWorkExperienceMutation = useMutation({
    mutationFn: providerApi.createWorkExperience,
    onSuccess: () => {
      invalidateWorkExperiences();
      setWeForm(EMPTY_WORK_EXPERIENCE_FORM);
      setNotice({ tone: 'success', message: 'Work experience added.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to add work experience.' }),
  });

  const deleteWorkExperienceMutation = useMutation({
    mutationFn: providerApi.deleteWorkExperience,
    onSuccess: () => {
      invalidateWorkExperiences();
      setPendingDeleteExperience(null);
      setNotice({ tone: 'success', message: 'Work experience deleted.' });
    },
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
      birthdate: form.birthdate || null,
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

  const handleWorkExperienceSubmit = (event) => {
    event.preventDefault();
    setNotice(null);
    createWorkExperienceMutation.mutate({
      role_title: weForm.role_title,
      employer_name: weForm.employer_name || null,
      description: weForm.description || null,
      started_on: weForm.started_on,
      ended_on: weForm.is_current ? null : weForm.ended_on || null,
    });
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

          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Birthdate"
              type="date"
              value={form.birthdate}
              max={new Date().toISOString().slice(0, 10)}
              onChange={(event) => setForm({ ...form, birthdate: event.target.value })}
            />

            <label className="flex items-center gap-2 self-end pb-2.5 text-sm text-ink">
              <input
                type="checkbox"
                checked={form.show_age_publicly}
                onChange={(event) => setForm({ ...form, show_age_publicly: event.target.checked })}
                className="h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
              />
              Show my age on my public profile
            </label>
          </div>
          <p className="-mt-2 text-xs text-ink-muted">
            Your birthdate is never shown publicly — only the computed age, and only if you turn this on.
          </p>

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

      <Card title="Add work experience">
        <form onSubmit={handleWorkExperienceSubmit} className="grid gap-4 sm:grid-cols-2">
          <Input
            label="Role / title"
            required
            value={weForm.role_title}
            onChange={(event) => setWeForm({ ...weForm, role_title: event.target.value })}
            placeholder="e.g. Electrician"
          />
          <Input
            label="Employer (optional)"
            value={weForm.employer_name}
            onChange={(event) => setWeForm({ ...weForm, employer_name: event.target.value })}
            placeholder="e.g. ABC Electrical Services"
          />
          <Input
            label="Started"
            type="date"
            required
            max={new Date().toISOString().slice(0, 10)}
            value={weForm.started_on}
            onChange={(event) => setWeForm({ ...weForm, started_on: event.target.value })}
          />
          <div>
            <Input
              label="Ended"
              type="date"
              max={new Date().toISOString().slice(0, 10)}
              value={weForm.ended_on}
              disabled={weForm.is_current}
              onChange={(event) => setWeForm({ ...weForm, ended_on: event.target.value })}
            />
            <label className="mt-1.5 flex items-center gap-2 text-sm text-ink">
              <input
                type="checkbox"
                checked={weForm.is_current}
                onChange={(event) => setWeForm({ ...weForm, is_current: event.target.checked, ended_on: '' })}
                className="h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
              />
              I currently do this
            </label>
          </div>
          <div className="sm:col-span-2">
            <Textarea
              label="Description (optional)"
              rows={2}
              value={weForm.description}
              onChange={(event) => setWeForm({ ...weForm, description: event.target.value })}
              placeholder="What did this role involve?"
            />
          </div>
          <div className="sm:col-span-2">
            <Button type="submit" loading={createWorkExperienceMutation.isPending}>
              Add work experience
            </Button>
          </div>
        </form>
      </Card>

      <Card title="Your work experience">
        <p className="mb-3 text-sm text-ink-muted">
          Shown on your public profile so clients can see your work history.
        </p>

        {workExperiencesQuery.isPending && <LoadingState label="Loading work experience…" />}

        {workExperiencesQuery.isSuccess && workExperiencesQuery.data.length === 0 && (
          <EmptyState title="No work experience yet" description="Add your past roles above." />
        )}

        {workExperiencesQuery.isSuccess && workExperiencesQuery.data.length > 0 && (
          <ul className="divide-y divide-line">
            {workExperiencesQuery.data.map((experience) => (
              <li key={experience.id} className="flex items-start justify-between gap-4 py-3">
                <div className="min-w-0 flex-1">
                  <p className="font-medium text-ink">
                    {experience.role_title}
                    {experience.employer_name ? ` · ${experience.employer_name}` : ''}
                  </p>
                  <p className="text-sm text-ink-muted">
                    {formatMonthYear(experience.started_on)} –{' '}
                    {experience.is_current ? 'Present' : formatMonthYear(experience.ended_on)}
                  </p>
                  {experience.description && (
                    <p className="mt-1 text-sm text-ink-muted">{experience.description}</p>
                  )}
                </div>

                <div className="flex shrink-0 gap-2">
                  <Button size="sm" variant="subtle" onClick={() => setEditingExperience(experience)}>
                    Edit
                  </Button>
                  <Button size="sm" variant="danger" onClick={() => setPendingDeleteExperience(experience)}>
                    Delete
                  </Button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </Card>

      {editingExperience && (
        <WorkExperienceEditor
          experience={editingExperience}
          onClose={() => setEditingExperience(null)}
          onSaved={() => {
            setEditingExperience(null);
            invalidateWorkExperiences();
            setNotice({ tone: 'success', message: 'Work experience updated.' });
          }}
        />
      )}

      <ConfirmDialog
        open={Boolean(pendingDeleteExperience)}
        onClose={() => {
          setPendingDeleteExperience(null);
          deleteWorkExperienceMutation.reset();
        }}
        onConfirm={() => deleteWorkExperienceMutation.mutate(pendingDeleteExperience.id)}
        title="Delete this work experience?"
        description={pendingDeleteExperience?.role_title}
        confirmLabel="Delete work experience"
        tone="danger"
        loading={deleteWorkExperienceMutation.isPending}
        error={deleteWorkExperienceMutation.error?.message}
      />
    </div>
  );
}

function WorkExperienceEditor({ experience, onClose, onSaved }) {
  const [form, setForm] = useState({
    role_title: experience.role_title ?? '',
    employer_name: experience.employer_name ?? '',
    description: experience.description ?? '',
    started_on: experience.started_on ?? '',
    ended_on: experience.ended_on ?? '',
    is_current: experience.is_current,
  });

  const mutation = useMutation({
    mutationFn: (payload) => providerApi.updateWorkExperience(experience.id, payload),
    onSuccess: onSaved,
  });

  const err = mutation.error;
  const set = (field) => (event) => setForm({ ...form, [field]: event.target.value });

  return (
    <Modal
      open
      onClose={onClose}
      title="Edit work experience"
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="work-experience-editor-form" loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <form
        id="work-experience-editor-form"
        className="grid gap-4"
        onSubmit={(event) => {
          event.preventDefault();
          mutation.mutate({
            role_title: form.role_title,
            employer_name: form.employer_name || null,
            description: form.description || null,
            started_on: form.started_on,
            ended_on: form.is_current ? null : form.ended_on || null,
          });
        }}
      >
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <Input label="Role / title" required value={form.role_title} onChange={set('role_title')} error={fieldError(err, 'role_title')} />
        <Input label="Employer (optional)" value={form.employer_name} onChange={set('employer_name')} error={fieldError(err, 'employer_name')} />
        <Input
          label="Started"
          type="date"
          required
          max={new Date().toISOString().slice(0, 10)}
          value={form.started_on}
          onChange={set('started_on')}
          error={fieldError(err, 'started_on')}
        />
        <div>
          <Input
            label="Ended"
            type="date"
            max={new Date().toISOString().slice(0, 10)}
            value={form.ended_on}
            disabled={form.is_current}
            onChange={set('ended_on')}
            error={fieldError(err, 'ended_on')}
          />
          <label className="mt-1.5 flex items-center gap-2 text-sm text-ink">
            <input
              type="checkbox"
              checked={form.is_current}
              onChange={(event) => setForm({ ...form, is_current: event.target.checked, ended_on: '' })}
              className="h-4 w-4 rounded border-line"
            />
            I currently do this
          </label>
        </div>
        <Textarea label="Description (optional)" rows={2} value={form.description} onChange={set('description')} error={fieldError(err, 'description')} />
      </form>
    </Modal>
  );
}
