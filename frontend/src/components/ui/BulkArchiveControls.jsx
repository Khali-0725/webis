import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { bulkArchiveApi } from '@/services/api/admin/bulkArchiveApi';

export function BulkArchiveControls({ resource, rows, selectedIds, onSelectionChange, onArchived }) {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const visibleIds = rows.filter((row) => !row.deleted_at).map((row) => row.id);
  const selectedVisible = visibleIds.filter((id) => selectedIds.includes(id));
  const allSelected = visibleIds.length > 0 && selectedVisible.length === visibleIds.length;

  const submit = async () => {
    if (resource === 'reviews' && reason.trim().length < 5) {
      setError('Enter a moderation reason of at least 5 characters.');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await bulkArchiveApi.archive(resource, selectedVisible, reason);
      onSelectionChange([]);
      setConfirmOpen(false);
      setReason('');
      onArchived(selectedVisible.length);
    } catch (err) {
      setError(err?.message ?? 'Could not archive the selected records.');
    } finally {
      setBusy(false);
    }
  };

  if (visibleIds.length === 0) return null;

  return (
    <div className="mb-3 flex flex-wrap items-center gap-3 print:hidden">
      <label className="inline-flex min-h-10 items-center gap-2 text-sm text-ink">
        <input
          type="checkbox"
          aria-label="Select all visible records"
          checked={allSelected}
          onChange={(event) => onSelectionChange(event.target.checked
            ? [...new Set([...selectedIds, ...visibleIds])]
            : selectedIds.filter((id) => !visibleIds.includes(id)))}
        />
        Select all on this page
      </label>
      <Button size="sm" variant="danger" disabled={selectedVisible.length === 0} onClick={() => setConfirmOpen(true)}>
        Archive selected ({selectedVisible.length})
      </Button>
      <ConfirmDialog
        open={confirmOpen}
        onClose={() => { setConfirmOpen(false); setError(null); }}
        onConfirm={submit}
        title={`Archive ${selectedVisible.length} selected record${selectedVisible.length === 1 ? '' : 's'}?`}
        description="The selected records leave active lists and can be restored from Trash."
        confirmLabel="Archive selected"
        tone="danger"
        loading={busy}
        error={error}
      >
        {resource === 'reviews' && <label className="block text-sm text-ink">Moderation reason
          <textarea value={reason} onChange={(event) => setReason(event.target.value)} minLength={5} maxLength={500} rows={3} className="mt-1 block w-full rounded-lg border border-line p-2" />
        </label>}
      </ConfirmDialog>
    </div>
  );
}
