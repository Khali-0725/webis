import { beforeEach, expect, it, vi } from 'vitest';
import { screen, within } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import LandingPage from '@/pages/public/LandingPage';
import { PublicLayout } from '@/layouts/PublicLayout';
import { renderWithProviders, resetSession } from './utils';

vi.mock('@/services/api/publicApi', () => ({ publicApi: {
  announcement: vi.fn().mockResolvedValue({ text: '' }),
  faqs: vi.fn().mockResolvedValue([]),
} }));

beforeEach(resetSession);

it('keeps one account call to action, header Login and a real hero image', () => {
  renderWithProviders(<Routes><Route element={<PublicLayout />}><Route path="/" element={<LandingPage />} /></Route></Routes>);
  const nav = within(screen.getByRole('navigation', { name: 'Primary' }));
  expect(nav.getByRole('link', { name: 'Home' })).toHaveAttribute('href', '/');
  expect(screen.getAllByRole('link', { name: 'Create an account' })).toHaveLength(1);
  expect(screen.getAllByRole('link', { name: 'Login' })).toHaveLength(1);
  expect(screen.queryByRole('link', { name: 'Register' })).not.toBeInTheDocument();
  expect(screen.queryByRole('link', { name: 'Register as a provider' })).not.toBeInTheDocument();
  expect(screen.queryByRole('link', { name: 'Sign in' })).not.toBeInTheDocument();
  expect(screen.getByRole('img', { name: /home service professional/i })).toHaveAttribute('src', '/images/home-service-visit.png');
});
