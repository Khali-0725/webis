/**
 * Today as `YYYY-MM-DD` in the browser's local time zone, for `<input type="date">`
 * min/max. `toISOString()` is UTC, which in the Philippines (UTC+8) is still
 * yesterday until 8 AM.
 */
export function todayLocal() {
  const now = new Date();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${now.getFullYear()}-${month}-${day}`;
}
