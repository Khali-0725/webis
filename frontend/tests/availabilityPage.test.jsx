import { expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import AvailabilityPage from '@/pages/provider/availability/AvailabilityPage';
import { availabilityApi } from '@/services/api/availabilityApi';
import { renderWithProviders } from './utils';

vi.mock('@/services/api/availabilityApi', () => ({
  availabilityApi: {
    holidays: vi.fn(),
    getRules: vi.fn(),
    listExceptions: vi.fn(),
    updateHolidays: vi.fn(),
    updateRules: vi.fn(),
    createException: vi.fn(),
    deleteException: vi.fn(),
    updateException: vi.fn(),
  },
}));

it('puts weekly hours and unavailable dates in one availability module', async () => {
  availabilityApi.holidays.mockResolvedValue({ exclude_all_holidays: false, dates: [] });
  availabilityApi.getRules.mockResolvedValue([]);
  availabilityApi.listExceptions.mockResolvedValue([]);
  renderWithProviders(<AvailabilityPage />);

  expect(await screen.findByRole('heading', { name: 'Availability' })).toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'Weekly days and times' })).toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'Unavailable dates' })).toBeInTheDocument();
  expect(screen.getByLabelText('Monday')).toBeInTheDocument();
});
