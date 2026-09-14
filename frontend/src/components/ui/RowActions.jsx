import { Button } from './Button';

/**
 * The standard action cluster on a managed table row: Edit / Activate or
 * Deactivate / Delete for a live row, a single Restore for a trashed one.
 * Each handler is optional - omit one and its button doesn't render.
 */
export function RowActions({ record, onEdit, onToggle, onDelete, onRestore, busy = false, extra = null }) {
  if (record.deleted_at) {
    return onRestore ? (
      <Button size="sm" variant="outline" loading={busy} onClick={() => onRestore(record)}>
        Restore
      </Button>
    ) : null;
  }

  return (
    <div className="flex flex-wrap gap-2">
      {extra}
      {onEdit && (
        <Button size="sm" variant="subtle" onClick={() => onEdit(record)}>
          Edit
        </Button>
      )}
      {onToggle && (
        <Button size="sm" variant="subtle" loading={busy} onClick={() => onToggle(record)}>
          {record.is_active ? 'Deactivate' : 'Activate'}
        </Button>
      )}
      {onDelete && (
        <Button size="sm" variant="danger" onClick={() => onDelete(record)}>
          Delete
        </Button>
      )}
    </div>
  );
}
