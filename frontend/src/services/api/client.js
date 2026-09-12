import axios from 'axios';

// An explicitly empty VITE_API_URL means "same origin, relative path" -
// the setup used behind a Vercel rewrite proxy in production, so a plain
// `||` (which treats '' as falsy) would wrongly fall back to localhost.
const envApiUrl = import.meta.env.VITE_API_URL;
const BASE_URL = (envApiUrl !== undefined ? envApiUrl : 'http://localhost:8000').replace(/\/$/, '');

/**
 * Single Axios instance for the whole app.
 *
 * WEBIS uses Sanctum's stateful (cookie) mode, so:
 *  - `withCredentials` must be true or the session cookie never travels;
 *  - a CSRF cookie must be fetched once before the first mutating request;
 *  - no token is ever stored in localStorage, which keeps the session out of
 *    reach of any XSS payload.
 */
export const api = axios.create({
  baseURL: `${BASE_URL}/api`,
  withCredentials: true,
  withXSRFToken: true,
  // A request that never settles would leave the UI on a spinner forever.
  // 20s is generous for a local API and still bounded.
  timeout: 20_000,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

let csrfPromise = null;

/**
 * Fetches Sanctum's CSRF cookie. Safe to call repeatedly - concurrent callers
 * share one in-flight request.
 */
export function ensureCsrfCookie() {
  if (!csrfPromise) {
    csrfPromise = axios
      .get(`${BASE_URL}/sanctum/csrf-cookie`, { withCredentials: true })
      .catch((error) => {
        csrfPromise = null;
        throw error;
      });
  }

  return csrfPromise;
}

/** Forces the next mutating request to re-fetch the CSRF cookie. */
export function resetCsrfCookie() {
  csrfPromise = null;
}

const MUTATING = new Set(['post', 'put', 'patch', 'delete']);

api.interceptors.request.use(async (config) => {
  if (MUTATING.has((config.method || 'get').toLowerCase())) {
    await ensureCsrfCookie();
  }

  return config;
});

/**
 * Normalises every failure into one predictable shape so UI code never has to
 * guess whether it is holding a network error, an HTML error page, or the API
 * envelope.
 *
 * @typedef {{ status: number, message: string, errors: Record<string, string[]>, isNetworkError: boolean }} ApiError
 */
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!error.response) {
      const timedOut = error.code === 'ECONNABORTED' || error.code === 'ETIMEDOUT';

      return Promise.reject({
        status: 0,
        message: timedOut
          ? 'The WEBIS server took too long to respond. Check that it is running, then try again.'
          : 'Cannot reach the WEBIS server. Check your connection and try again.',
        errors: {},
        isNetworkError: true,
      });
    }

    const { status, data } = error.response;

    // 419 = the CSRF cookie expired. Clear it so the next attempt refetches.
    if (status === 419) {
      resetCsrfCookie();
    }

    const payload = typeof data === 'object' && data !== null ? data : {};

    return Promise.reject({
      status,
      message: payload.message || defaultMessageFor(status),
      errors: payload.errors && typeof payload.errors === 'object' ? payload.errors : {},
      isNetworkError: false,
    });
  },
);

function defaultMessageFor(status) {
  switch (status) {
    case 401:
      return 'Your session has ended. Please sign in again.';
    case 403:
      return 'You are not allowed to do that.';
    case 404:
      return 'We could not find what you were looking for.';
    case 419:
      return 'Your session expired. Please try again.';
    case 422:
      return 'Please check the highlighted fields.';
    case 429:
      return 'Too many attempts. Please wait a moment.';
    default:
      return status >= 500
        ? 'Something went wrong on our end. Please try again.'
        : 'The request could not be completed.';
  }
}

/** Unwraps the WEBIS success envelope: { success, data, meta } -> data. */
export function unwrap(response) {
  return response?.data?.data;
}

/** Returns the pagination block from a list response. */
export function unwrapMeta(response) {
  return response?.data?.meta ?? null;
}

/** Pulls the first validation message for a field, if there is one. */
export function fieldError(error, field) {
  const messages = error?.errors?.[field];

  return Array.isArray(messages) ? messages[0] : undefined;
}
