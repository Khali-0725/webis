import { useState } from 'react';
import { Card } from '@/components/ui/Card';
import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { IconActionButton } from '@/components/ui/IconActionButton';
import { profileApi } from '@/services/api/profileApi';
import { useAuth } from '@/hooks/useAuth';
import { useAuthStore } from '@/store/authStore';
import { useQueryClient } from '@tanstack/react-query';
import { ROLES } from '@/constants';
import { fieldError } from '@/services/api/client';

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
    email: user?.email || '',
    phone: user?.phone || '',
    address_line: user?.address_line || '',
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
      email: user.email || '',
      phone: user.phone || '',
      address_line: user.address_line || '',
    });
  }

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setNotice(null);
    setSavingProfile(true);

    try {
      const result = await profileApi.updateProfile(formData);
      await refreshUser();
      setNotice({ tone: 'success', message: result.email_verified === false && formData.email !== user?.email
        ? 'Profile saved. Check your new email address for a verification link.'
        : 'Profile updated successfully.' });
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
    if (file.size > 4 * 1024 * 1024) {
      setNotice({ tone: 'error', message: 'Choose an image up to 4 MB.' });
      e.target.value = '';
      return;
    }
    setUploadingAvatar(true);

    const avatarData = new FormData();
    avatarData.append('avatar', file);

    try {
      await profileApi.updateAvatar(avatarData);
      await refreshUser();
      setNotice({ tone: 'success', message: 'Avatar updated successfully.' });
    } catch (error) {
      setNotice({ tone: 'error', message: fieldError(error, 'avatar') ?? error?.message ?? 'Failed to upload avatar.' });
    } finally {
      setUploadingAvatar(false);
      e.target.value = '';
    }
  };

  return (
    <Card title="My Profile" className="mx-auto mt-6 max-w-xl">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <div className="mb-6 flex items-center gap-4 border-b border-line pb-5">
        <Avatar src={user?.avatar_url} initials={user?.initials} name={user?.full_name} size="lg" className="h-20 w-20 text-xl" />
        <div className="min-w-0">
          <p className="truncate font-semibold text-navy-800">{user?.full_name}</p>
          <p className="mt-1 text-sm text-ink-muted">Your current profile picture</p>
          <a href="#avatar-upload" className="mt-2 inline-block text-sm font-semibold text-navy-700 underline underline-offset-4">Change profile picture</a>
        </div>
      </div>

      <form onSubmit={handleProfileSubmit} className="space-y-4">
        <Input
          label="First Name"
          required
          value={formData.first_name}
          onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
        />
        <Input
          label="Last Name"
          required
          value={formData.last_name}
          onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
        />
        <Input
          label="Email address"
          type="email"
          autoComplete="email"
          required
          value={formData.email}
          onChange={(e) => setFormData({ ...formData, email: e.target.value })}
          hint="Changing your email requires verification."
        />
        <Input
          label="Contact number"
          type="tel"
          autoComplete="tel"
          value={formData.phone}
          onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
          placeholder="09171234567"
        />
        <Input
          label="Address"
          autoComplete="street-address"
          value={formData.address_line}
          onChange={(e) => setFormData({ ...formData, address_line: e.target.value })}
          placeholder="House number, street, barangay"
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
          <IconActionButton icon="trash" label="Delete my account" className="mt-3" onClick={() => setShowDeleteAccount(true)} />
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
