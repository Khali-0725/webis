import { beforeEach, describe, expect, it } from 'vitest';
import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Route, Routes } from 'react-router-dom';
import { PublicLayout } from '@/layouts/PublicLayout';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

function Harness() {
  return (
    <Routes>
      <Route element={<PublicLayout />}>
        <Route path="/" element={<p>Public home</p>} />
        <Route path="/providers" element={<p>Provider directory</p>} />
      </Route>
      <Route path="/provider/dashboard" element={<p>Provider dashboard</p>} />
    </Routes>
  );
}

describe('public mobile navigation', () => {
  beforeEach(() => resetSession());

  it('lets a visitor reach the provider directory and closes after navigation', async () => {
    setSession(null);
    const user = userEvent.setup();
    renderWithProviders(<Harness />);
    await user.click(screen.getByRole('button', { name: 'Open public navigation' }));
    const menu = screen.getByRole('navigation', { name: 'Mobile primary' });
    expect(within(menu).getByRole('link', { name: 'Login' })).toHaveAttribute('href', '/login');
    await user.click(within(menu).getByRole('link', { name: 'Providers' }));
    expect(await screen.findByText('Provider directory')).toBeInTheDocument();
    expect(screen.queryByRole('navigation', { name: 'Mobile primary' })).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Open public navigation' })).toHaveAttribute('aria-expanded', 'false');
  });

  it('uses the signed-in role dashboard and supports Escape', async () => {
    setSession(makeUser({ role: 'provider', home_path: '/provider/dashboard' }));
    const user = userEvent.setup();
    renderWithProviders(<Harness />);
    await user.click(screen.getByRole('button', { name: 'Open public navigation' }));
    let menu = screen.getByRole('navigation', { name: 'Mobile primary' });
    expect(within(menu).queryByRole('link', { name: 'Login' })).not.toBeInTheDocument();
    await user.keyboard('{Escape}');
    expect(screen.queryByRole('navigation', { name: 'Mobile primary' })).not.toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Open public navigation' }));
    menu = screen.getByRole('navigation', { name: 'Mobile primary' });
    await user.click(within(menu).getByRole('link', { name: 'Go to dashboard' }));
    expect(await screen.findByText('Provider dashboard')).toBeInTheDocument();
  });
});
