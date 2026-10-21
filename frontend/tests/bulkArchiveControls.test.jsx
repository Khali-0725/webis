import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BulkArchiveControls } from '@/components/ui/BulkArchiveControls';
import { bulkArchiveApi } from '@/services/api/admin/bulkArchiveApi';

vi.mock('@/services/api/admin/bulkArchiveApi', () => ({
  bulkArchiveApi: { archive: vi.fn().mockResolvedValue({ archived_count: 2 }) },
}));

function Harness() {
  const [selectedIds, setSelectedIds] = useState([]);
  return <BulkArchiveControls
    resource="services"
    rows={[{ id: 1 }, { id: 2 }, { id: 3, deleted_at: '2026-10-08T00:00:00Z' }]}
    selectedIds={selectedIds}
    onSelectionChange={setSelectedIds}
    onArchived={() => {}}
  />;
}

describe('admin bulk archive selection', () => {
  it('only archives eligible selected rows after confirmation', async () => {
    bulkArchiveApi.archive.mockClear();
    const user = userEvent.setup();
    render(<Harness />);

    await user.click(screen.getByRole('checkbox', { name: 'Select all visible records' }));
    await user.click(screen.getByRole('button', { name: 'Archive selected (2)' }));
    expect(bulkArchiveApi.archive).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', { name: 'Archive selected', exact: true }));
    await waitFor(() => expect(bulkArchiveApi.archive).toHaveBeenCalledWith('services', [1, 2], ''));
  });
});
