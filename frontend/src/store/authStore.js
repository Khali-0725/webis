import { create } from 'zustand';

/**
 * Session state.
 *
 * Holds only what the current user is - never a token, never a permission
 * list the server has not confirmed. The server remains the authority; this
 * store exists so the UI can render the right shell without a round trip.
 */
export const useAuthStore = create((set) => ({
  user: null,
  /** 'idle' | 'loading' | 'ready' - `ready` means the boot check has finished. */
  status: 'idle',

  setUser: (user) => set({ user, status: 'ready' }),
  clearUser: () => set({ user: null, status: 'ready' }),
  setStatus: (status) => set({ status }),
}));

export const selectIsAuthenticated = (state) => state.user !== null;
export const selectRole = (state) => state.user?.role ?? null;
