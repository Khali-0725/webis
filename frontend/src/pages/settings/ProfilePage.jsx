import { useState } from 'react';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { profileApi } from '@/services/api/profileApi';
import { useAuth } from '@/hooks/useAuth';
import { useAuthStore } from '@/store/authStore';
import { useQueryClient } from '@tanstack/react-query';
import { ROLES } from '@/constants';

export default function ProfilePage() {
  const { user, refreshUser } = useAuth();
  const clearUser = useAuthStore((s) => s.clearUser);
  const queryClient = useQueryClient();
  const [savingProfile, setSavingProfile] = useState(false);
  const [showDeleteAccount, setShowDeleteAccount] = useState(false);
  const [deletePassword, setDeletePassword] = useState('');
  const [deletingAccount, setDeletingAccount] = useState(false);
  const [deleteError, setDeleteError] = useState(null);

  const handleDeleteAccount = async () => {
    setDeleteError(null);
    setDeletingAccount(true);
    try {
      await profileApi.deleteAccount(deletePassword);
      // The server already ended the session - drop local state the same way
      // useLogout() does, and the router sends us to the sign-in page.
      clearUser();
      queryClient.clear();
    } catch (err) {
      setDeleteError(err?.errors?.password?.[0] ?? err?.message ?? 'Could not delete your account.');
      setDeletingAccount(false);
    }
  };
  const [uploadingAvatar, setUploadingAvatar] = useState(false);
  const [notice, setNotice] = useState(null);
  const [formData, setFormData] = useState({
    first_name: user?.first_name || '',
    last_name: user?.last_name || '',
  });

  // Re-seed the form whenever a *different* user record arrives (e.g. after
  // refreshUser()), without looping through an effect: this runs during
  // render, which React explicitly allows for "adjusting state from props".
  const [syncedUserId, setSyncedUserId] = useState(user?.id ?? null);
  if (user && user.id !== syncedUserId) {
    setSyncedUserId(user.id);
    setFormData({
      first_name: user.first_name || '',
      last_name: user.last_name || '',
    });
  }

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setNotice(null);
    setSavingProfile(true);

    try {
      await profileApi.updateProfile(formData);
      await refreshUser();
      setNotice({ tone: 'success', message: 'Profile updated successfully.' });
    } catch (error) {
      setNotice({ tone: 'error', message: error?.message ?? 'Failed to update profile.' });
    } finally {
      setSavingProfile(false);
    }
  };

  const handleAvatarChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setNotice(null);
    setUploadingAvatar(true);

    const avatarData = new FormData();
    avatarData.append('avatar', file);

    try {
      await profileApi.updateAvatar(avatarData);
      await refreshUser();
      setNotice({ tone: 'success', message: 'Avatar updated successfully.' });
    } catch (error) {
      setNotice({ tone: 'error', message: error?.message ?? 'Failed to upload avatar.' });
    } finally {
      setUploadingAvatar(false);
      e.target.value = '';
    }
  };

  return (
    <Card title="Profile Settings" className="mx-auto mt-10 max-w-md">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <form onSubmit={handleProfileSubmit} className="space-y-4">
        <Input
          label="First Name"
          value={formData.first_name}
          onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
        />
        <Input
          label="Last Name"
          value={formData.last_name}
          onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
        />
        <Button type="submit" loading={savingProfile}>
          Save changes
        </Button>
      </form>

      <div className="mt-6 border-t border-line pt-5">
        <label htmlFor="avatar-upload" className="block text-sm font-medium text-ink">
          Update avatar
        </label>
        <p className="mt-0.5 text-xs text-ink-muted">JPEG, PNG, or WebP. Up to 4 MB.</p>
        <input
          id="avatar-upload"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          disabled={uploadingAvatar}
          onChange={handleAvatarChange}
          className="mt-2 block w-full text-sm text-ink-muted file:mr-4 file:rounded-lg file:border-0 file:bg-navy-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-navy-700 hover:file:bg-navy-100 disabled:cursor-not-allowed disabled:opacity-60"
        />
      </div>

      {user?.role !== ROLES.ADMIN && (
        <div className="mt-6 border-t border-line pt-5">
          <p className="text-sm font-medium text-ink">Delete account</p>
          <p className="mt-0.5 text-xs text-ink-muted">
            Your account is deactivated and you are signed out. Bookings and payments you were part of stay on
            record for the other party. An administrator can restore the account if you change your mind.
          </p>
          <Button variant="danger" size="sm" className="mt-3" onClick={() => setShowDeleteAccount(true)}>
            Delete my account
          </Button>
        </div>
      )}

      <ConfirmDialog
        open={showDeleteAccount}
        onClose={() => {
          setShowDeleteAccount(false);
          setDeletePassword('');
          setDeleteError(null);
        }}
        onConfirm={handleDeleteAccount}
        title="Delete your account?"
        description="Enter your password to confirm."
        confirmLabel="Delete my account"
        tone="danger"
        loading={deletingAccount}
        error={deleteError}
      >
        <Input
          label="Password"
          type="password"
          autoComplete="current-password"
          value={deletePassword}
          onChange={(e) => setDeletePassword(e.target.value)}
        />
      </ConfirmDialog>
    </Card>
  );
}
