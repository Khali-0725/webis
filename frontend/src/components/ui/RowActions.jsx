import { IconActionButton } from './IconActionButton';

/**
 * The standard action cluster on a managed table row: Edit / Activate or
 * Deactivate / Delete for a live row, a single Restore for a trashed one.
 * Each handler is optional - omit one and its button doesn't render.
 */
export function RowActions({ record, onEdit, onToggle, onDelete, onRestore, busy = false, extra = null }) {
  const recordName = record.name ?? record.title ?? record.full_name ?? record.booking_code ?? `record #${record.id}`;
  if (record.deleted_at) {
    return onRestore ? (
      <IconActionButton icon="restore" label={`Restore ${recordName}`} loading={busy} onClick={() => onRestore(record)} />
    ) : null;
  }

  return (
    <div className="flex flex-wrap gap-2">
      {extra}
      {onEdit && (
        <IconActionButton icon="edit" label={`Edit ${recordName}`} onClick={() => onEdit(record)} />
      )}
      {onToggle && (
        <IconActionButton
          icon={record.is_active ? 'pause' : 'check'}
          label={`${record.is_active ? 'Deactivate' : 'Activate'} ${recordName}`}
          loading={busy}
          onClick={() => onToggle(record)}
        />
      )}
      {onDelete && (
        <IconActionButton icon="trash" label={`Delete ${recordName}`} onClick={() => onDelete(record)} />
      )}
    </div>
  );
}
