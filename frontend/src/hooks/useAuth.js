import { useCallback, useEffect, useRef } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { authApi } from '@/services/auth/authApi';
import { useAuthStore } from '@/store/authStore';

/**
 * Runs once at app boot: asks the API who the session belongs to.
 *
 * Mounted by <AuthBootstrap> in App.jsx, never called from a page.
 *
 * The single-run guard is a ref, not the store's `status`. An earlier version
 * depended on `status` and cancelled its in-flight request from the effect
 * cleanup. That deadlocked on every load: calling setStatus('loading')
 * re-rendered with a new `status`, which re-ran the effect, which fired the
 * cleanup, which set the cancelled flag - so when the request finally resolved
 * its handler was skipped and `status` stayed 'loading' forever. The app sat on
 * the session-restore spinner and never reached a page.
 *
 * A ref survives re-renders and React StrictMode's deliberate double-invoke,
 * so exactly one request is sent and its result is always applied.
 */
export function useAuthBootstrap() {
  const setUser = useAuthStore((s) => s.setUser);
  const clearUser = useAuthStore((s) => s.clearUser);
  const setStatus = useAuthStore((s) => s.setStatus);
  const status = useAuthStore((s) => s.status);
  const startedRef = useRef(false);

  useEffect(() => {
    if (startedRef.current) return;
    startedRef.current = true;

    setStatus('loading');

    // No cancellation: these are store setters, not component state, so they
    // are safe to call regardless of what has mounted or unmounted, and this
    // hook lives for the lifetime of the app.
    authApi
      .me()
      .then((user) => (user ? setUser(user) : clearUser()))
      .catch(() => clearUser());
  }, [setUser, clearUser, setStatus]);

  return status;
}

export function useAuth() {
  const user = useAuthStore((s) => s.user);
  const status = useAuthStore((s) => s.status);
  const setUser = useAuthStore((s) => s.setUser);

  const refreshUser = useCallback(async () => {
    try {
      const user = await authApi.me();
      if (user) {
        setUser(user);
      }
    } catch (error) {
      console.error('Failed to refresh user:', error);
    }
  }, [setUser]);

  return {
    user,
    role: user?.role ?? null,
    isAuthenticated: user !== null,
    isReady: status === 'ready',
    refreshUser,
  };
}

export function useLogin() {
  const setUser = useAuthStore((s) => s.setUser);

  return useMutation({
    mutationFn: authApi.login,
    onSuccess: (user) => setUser(user),
  });
}

export function useRegister() {
  const setUser = useAuthStore((s) => s.setUser);

  return useMutation({
    mutationFn: authApi.register,
    onSuccess: (user) => setUser(user),
  });
}

export function useForgotPassword() {
  return useMutation({ mutationFn: authApi.forgotPassword });
}

export function useResetPassword() {
  return useMutation({ mutationFn: authApi.resetPassword });
}

export function useLogout() {
  const clearUser = useAuthStore((s) => s.clearUser);
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: authApi.logout,
    // Clear local state either way: if the server already dropped the session
    // the request 401s, and staying "signed in" in the UI would be worse.
    onSettled: () => {
      clearUser();
      queryClient.clear();
    },
  });

  const logout = useCallback(() => mutation.mutateAsync().catch(() => {}), [mutation]);

  return { logout, isPending: mutation.isPending };
}
