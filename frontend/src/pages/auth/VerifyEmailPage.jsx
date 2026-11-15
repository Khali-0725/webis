import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { useAuth, useLogout } from '@/hooks/useAuth';
import { authApi } from '@/services/auth/authApi';
import { useAuthStore } from '@/store/authStore';

export default function VerifyEmailPage() {
  const navigate = useNavigate();
  const { user, refreshUser } = useAuth();
  const logout = useLogout();
  const [notice, setNotice] = useState(null);
  const [busy, setBusy] = useState(false);

  const checkVerification = async () => {
    setBusy(true);
    setNotice(null);
    await refreshUser();
    setBusy(false);
    const latest = useAuthStore.getState().user;
    if (latest?.email_verified) navigate(latest.home_path, { replace: true });
    else setNotice({ tone: 'warning', message: 'Email is not verified yet. Open the link in your inbox first.' });
  };

  const resend = async () => {
    setBusy(true);
    setNotice(null);
    try {
      await authApi.resendVerification();
      setNotice({ tone: 'success', message: 'Verification link sent again. Check your inbox.' });
    } catch (error) {
      setNotice({ tone: 'error', message: error?.message ?? 'Could not resend the verification link.' });
    } finally {
      setBusy(false);
    }
  };

  return <Card title="Verify your email" className="mx-auto mt-12 max-w-lg">
    {notice && <Alert tone={notice.tone} className="mb-4">{notice.message}</Alert>}
    <p className="text-sm text-ink-muted">We sent a verification link to <strong className="text-ink">{user?.email}</strong>. Verify it before using WEBIS.</p>
    <div className="mt-6 flex flex-wrap gap-3">
      <Button onClick={checkVerification} loading={busy}>I verified my email</Button>
      <Button variant="secondary" onClick={resend} disabled={busy}>Resend link</Button>
      <Button variant="ghost" onClick={logout.logout}>Sign out</Button>
    </div>
  </Card>;
}
