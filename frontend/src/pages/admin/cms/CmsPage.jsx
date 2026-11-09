import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { IconActionButton } from '@/components/ui/IconActionButton';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { faqApi } from '@/services/api/admin/faqApi';
import { settingsApi } from '@/services/api/admin/settingsApi';
import { categoryApi } from '@/services/api/categoryApi';
import { adminBarangayApi } from '@/services/api/admin/barangayApi';
import { fieldError } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';

const emptyFaq = { question: '', answer: '', sort_order: 0, is_published: true };

export default function CmsPage() {
  const queryClient = useQueryClient();
  const [notice, setNotice] = useState(null);
  const [announcement, setAnnouncement] = useState('');
  const [syncedAt, setSyncedAt] = useState(0);
  const [editing, setEditing] = useState(null);
  const [faqForm, setFaqForm] = useState(emptyFaq);
  const [pendingDelete, setPendingDelete] = useState(null);

  const settingsQuery = useQuery({ queryKey: queryKeys.admin.settings, queryFn: settingsApi.list });
  const faqQuery = useQuery({ queryKey: ['admin', 'faqs'], queryFn: faqApi.list });
  const categoryQuery = useQuery({ queryKey: queryKeys.admin.categories, queryFn: () => categoryApi.list({ per_page: 1000 }) });
  const barangayQuery = useQuery({ queryKey: queryKeys.admin.barangays, queryFn: () => adminBarangayApi.list({ per_page: 1000 }) });

  const downloadBackup = () => {
    const backup = {
      exported_at: new Date().toISOString(),
      settings: settingsQuery.data ?? [],
      faqs: faqQuery.data ?? [],
      categories: categoryQuery.data ?? [],
      barangays: barangayQuery.data ?? [],
    };
    const url = URL.createObjectURL(new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `webis-platform-backup-${new Date().toISOString().slice(0, 10)}.json`;
    link.click();
    URL.revokeObjectURL(url);
    setNotice({ tone: 'success', message: 'Platform configuration backup downloaded.' });
  };

  if (settingsQuery.data && settingsQuery.dataUpdatedAt !== syncedAt) {
    setAnnouncement(settingsQuery.data.find((row) => row.key === 'platform.announcement')?.value ?? '');
    setSyncedAt(settingsQuery.dataUpdatedAt);
  }

  const announcementMutation = useMutation({
    mutationFn: () => settingsApi.update([{ key: 'platform.announcement', value: announcement }]),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.admin.settings });
      setNotice({ tone: 'success', message: 'Announcement saved.' });
    },
  });

  const faqMutation = useMutation({
    mutationFn: () => {
      const payload = { ...faqForm, sort_order: Number(faqForm.sort_order) };
      return editing ? faqApi.update(editing.id, payload) : faqApi.create(payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'faqs'] });
      setEditing(null);
      setFaqForm(emptyFaq);
      setNotice({ tone: 'success', message: 'FAQ saved.' });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: () => faqApi.remove(pendingDelete.id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'faqs'] });
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'FAQ archived.' });
    },
  });

  const beginEdit = (faq) => {
    setEditing(faq);
    setFaqForm({ question: faq.question, answer: faq.answer, sort_order: faq.sort_order, is_published: faq.is_published });
    faqMutation.reset();
  };

  return (
    <div className="space-y-5">
      {notice && <Alert tone={notice.tone}>{notice.message}</Alert>}

      <Card title="System data backup">
        <p className="text-sm text-ink-muted">Download platform settings, announcements, FAQs, categories, and barangays as a JSON backup. Database records and uploaded files still require a hosting backup.</p>
        <Button type="button" variant="outline" className="mt-3" onClick={downloadBackup} disabled={settingsQuery.isPending || faqQuery.isPending || categoryQuery.isPending || barangayQuery.isPending}>Download configuration backup</Button>
      </Card>

      <Card title="Public announcement">
        {settingsQuery.isPending && <LoadingState label="Loading announcement…" />}
        {settingsQuery.isError && <ErrorState description={settingsQuery.error?.message} onRetry={() => settingsQuery.refetch()} />}
        {!settingsQuery.isPending && !settingsQuery.isError && (
          <form onSubmit={(event) => { event.preventDefault(); announcementMutation.mutate(); }} className="space-y-3">
            <label className="block text-sm font-medium text-ink" htmlFor="cms-announcement">Announcement</label>
            <textarea id="cms-announcement" rows={3} maxLength={500} value={announcement} onChange={(event) => setAnnouncement(event.target.value)} className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink" />
            <p className="text-xs text-ink-muted">Shown to visitors on the public site. Leave blank to hide it.</p>
            {announcementMutation.error && <Alert tone="error">{fieldError(announcementMutation.error, 'settings.0.value') ?? announcementMutation.error.message}</Alert>}
            <Button type="submit" loading={announcementMutation.isPending}>Save announcement</Button>
          </form>
        )}
      </Card>

      <Card title="Frequently asked questions">
        {faqQuery.isPending && <LoadingState label="Loading FAQs…" />}
        {faqQuery.isError && <ErrorState description={faqQuery.error?.message} onRetry={() => faqQuery.refetch()} />}
        {!faqQuery.isPending && !faqQuery.isError && faqQuery.data.length === 0 && <EmptyState title="No FAQs yet" description="Add the first question below." />}
        {!faqQuery.isPending && !faqQuery.isError && faqQuery.data.length > 0 && (
          <ul className="divide-y divide-line">
            {faqQuery.data.map((faq) => (
              <li key={faq.id} className="py-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="font-medium text-ink">{faq.question}</p>
                      <Badge tone={faq.is_published ? 'success' : 'neutral'}>{faq.is_published ? 'Published' : 'Draft'}</Badge>
                    </div>
                    <p className="mt-1 whitespace-pre-wrap text-sm text-ink-muted">{faq.answer}</p>
                    <p className="mt-1 text-xs text-ink-soft">Order: {faq.sort_order}</p>
                  </div>
                  <div className="flex gap-2">
                    <IconActionButton icon="edit" label={`Edit ${faq.question}`} onClick={() => beginEdit(faq)} />
                    <IconActionButton icon="archive" label={`Archive ${faq.question}`} onClick={() => setPendingDelete(faq)} />
                  </div>
                </div>
              </li>
            ))}
          </ul>
        )}
      </Card>

      <Card title={editing ? 'Edit FAQ' : 'Add FAQ'}>
        <form onSubmit={(event) => { event.preventDefault(); faqMutation.mutate(); }} className="space-y-4">
          <Input label="Question" required minLength={3} maxLength={255} value={faqForm.question} onChange={(event) => setFaqForm({ ...faqForm, question: event.target.value })} error={fieldError(faqMutation.error, 'question')} />
          <label className="block text-sm font-medium text-ink" htmlFor="faq-answer">Answer</label>
          <textarea id="faq-answer" required minLength={3} maxLength={10000} rows={4} value={faqForm.answer} onChange={(event) => setFaqForm({ ...faqForm, answer: event.target.value })} className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink" />
          {fieldError(faqMutation.error, 'answer') && <p className="text-sm text-red-600">{fieldError(faqMutation.error, 'answer')}</p>}
          <Input label="Display order" type="number" min={0} max={100000} required value={faqForm.sort_order} onChange={(event) => setFaqForm({ ...faqForm, sort_order: event.target.value })} error={fieldError(faqMutation.error, 'sort_order')} />
          <label className="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" checked={faqForm.is_published} onChange={(event) => setFaqForm({ ...faqForm, is_published: event.target.checked })} />
            Published
          </label>
          {faqMutation.error && !Object.keys(faqMutation.error.errors ?? {}).length && <Alert tone="error">{faqMutation.error.message}</Alert>}
          <div className="flex gap-2">
            <Button type="submit" loading={faqMutation.isPending}>{editing ? 'Save FAQ' : 'Add FAQ'}</Button>
            {editing && <Button type="button" variant="subtle" onClick={() => { setEditing(null); setFaqForm(emptyFaq); faqMutation.reset(); }}>Cancel edit</Button>}
          </div>
        </form>
      </Card>

      <ConfirmDialog open={Boolean(pendingDelete)} onClose={() => { setPendingDelete(null); deleteMutation.reset(); }} onConfirm={() => deleteMutation.mutate()} title="Archive this FAQ?" description={pendingDelete?.question} confirmLabel="Archive FAQ" loading={deleteMutation.isPending} error={deleteMutation.error?.message}>
        <p className="text-sm text-ink-muted">The question will no longer appear on the public site.</p>
      </ConfirmDialog>
    </div>
  );
}
