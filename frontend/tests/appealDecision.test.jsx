import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AppealListPage from '@/pages/admin/appeals/AppealListPage';
import { appealApi } from '@/services/api/appealApi';
import { renderWithProviders } from './utils';

vi.mock('@/services/api/appealApi', () => ({ appealApi: { list: vi.fn(), decide: vi.fn() } }));

describe('appeal decision', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    appealApi.list.mockResolvedValue({ items: [{ id: 1, status: 'pending', created_at: '2026-10-09T12:00:00Z', user: { first_name: 'Demo', last_name: 'Client', email: 'demo@example.test' }, explanation: 'Synthetic test appeal.' }], meta: null });
  });

  it('requires an explicit decision and rationale before submission', async () => {
    const user = userEvent.setup();
    renderWithProviders(<AppealListPage />);
    await user.click(await screen.findByRole('button', { name: 'Review' }));

    expect(screen.getByRole('button', { name: 'Choose a decision' })).toBeDisabled();
    expect(appealApi.decide).not.toHaveBeenCalled();
    await user.selectOptions(screen.getByRole('combobox', { name: 'Decision' }), 'rejected');
    await user.type(screen.getByRole('textbox', { name: 'Rationale' }), 'Local test decision with an adequate reason.');
    expect(screen.getByRole('textbox', { name: 'Rationale' })).toHaveValue('Local test decision with an adequate reason.');
    expect(screen.getByRole('button', { name: 'Reject appeal' })).toBeEnabled();
  });
});
