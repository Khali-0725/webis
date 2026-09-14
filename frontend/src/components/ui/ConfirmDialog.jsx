import { Modal } from './Modal';
import { Button } from './Button';
import { Alert } from './Alert';

/**
 * A yes/no confirmation. `tone="danger"` is for deletes; the confirm button
 * names the action ("Delete service"), never a generic "OK".
 */
export function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title,
  description,
  confirmLabel = 'Confirm',
  tone = 'primary',
  loading = false,
  error = null,
  children,
}) {
  return (
    <Modal
      open={open}
      onClose={loading ? undefined : onClose}
      title={title}
      description={description}
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={loading}>
            Cancel
          </Button>
          <Button variant={tone} onClick={onConfirm} loading={loading}>
            {confirmLabel}
          </Button>
        </>
      }
    >
      {error && (
        <Alert tone="error" className="mb-3">
          {error}
        </Alert>
      )}
      {children}
    </Modal>
  );
}
