/**
 * Small inline icon set.
 *
 * Kept local rather than pulling an icon package: the app needs about twenty
 * glyphs, and inlining them keeps the bundle small and removes a dependency.
 * Every icon is decorative - labels come from the surrounding element.
 */
const PATHS = {
  dashboard: 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
  calendar:
    'M7 3v2M17 3v2M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
  bookings: 'M6 3h9l5 5v13a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1zM14 3v6h6',
  search: 'M11 4a7 7 0 105.2 11.7L21 20.5 20.5 21l-4.8-4.8A7 7 0 0011 4z',
  wrench: 'M15 3a5 5 0 00-4.6 7L3 17.4 6.6 21l7.4-7.4A5 5 0 1015 3z',
  message: 'M4 5h16a1 1 0 011 1v9a1 1 0 01-1 1H9l-5 4V6a1 1 0 011-1z',
  wallet: 'M4 7h14a2 2 0 012 2v8a2 2 0 01-2 2H4a1 1 0 01-1-1V8a1 1 0 011-1zM3 7l12-3M17 13h2',
  star: 'M12 3l2.6 5.6 6.1.8-4.5 4.2 1.2 6L12 16.8 6.6 19.6l1.2-6L3.3 9.4l6.1-.8z',
  user: 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
  users: 'M9 12a4 4 0 100-8 4 4 0 000 8zM2 21a7 7 0 0114 0M17 8a3 3 0 100-6M18 21a6 6 0 00-2-4.5',
  shield: 'M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z',
  chart: 'M4 20V10M10 20V4M16 20v-7M22 20H2',
  settings:
    'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.6 1.6 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.6 1.6 0 00-1.8-.3 1.6 1.6 0 00-1 1.5V21a2 2 0 11-4 0v-.1A1.6 1.6 0 008 19.4a1.6 1.6 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.6 1.6 0 00.3-1.8 1.6 1.6 0 00-1.5-1H2a2 2 0 110-4h.1A1.6 1.6 0 003.6 8a1.6 1.6 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.6 1.6 0 001.8.3H8a1.6 1.6 0 001-1.5V2a2 2 0 114 0v.1a1.6 1.6 0 001 1.5 1.6 1.6 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.6 1.6 0 00-.3 1.8V8a1.6 1.6 0 001.5 1H22a2 2 0 110 4h-.1a1.6 1.6 0 00-1.5 1z',
  logout: 'M15 17l5-5-5-5M20 12H9M12 3H5a1 1 0 00-1 1v16a1 1 0 001 1h7',
  bell: 'M18 8a6 6 0 10-12 0c0 7-2 9-2 9h16s-2-2-2-9M13.7 21a2 2 0 01-3.4 0',
  pin: 'M12 21s7-6.3 7-11a7 7 0 10-14 0c0 4.7 7 11 7 11z M12 12a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
  tag: 'M3 12l9-9h8v8l-9 9zM16.5 7.5h.01',
  clipboard:
    'M9 4h6v3H9zM7 5H6a1 1 0 00-1 1v14a1 1 0 001 1h12a1 1 0 001-1V6a1 1 0 00-1-1h-1',
  flag: 'M5 21V4M5 4h12l-2 4 2 4H5',
  menu: 'M4 7h16M4 12h16M4 17h16',
  close: 'M6 6l12 12M18 6L6 18',
  zap: 'M13 2L4 14h6l-1 8 9-12h-6l1-8z',
  thermometer: 'M14 4a2 2 0 00-4 0v9.34a4 4 0 104 0V4z M12 8v6',
  hammer: 'M15 4l5 5-2.5 2.5L12.5 6.5zM11 8L3 16v4h4l8-8',
  sparkles:
    'M12 3l1.8 4.7L18 9.5l-4.2 1.8L12 16l-1.8-4.7L6 9.5l4.2-1.8zM18 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z',
  paintbrush: 'M9 11l6-6 3 3-6 6zM8 12l-3 7c-.4 1 .4 2 1.4 1.6L13 17',
  home: 'M4 11l8-7 8 7M6 10v10h5v-6h2v6h5V10',
};

export function Icon({ name, className = 'h-5 w-5', strokeWidth = 1.8 }) {
  const d = PATHS[name];

  if (!d) return null;

  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
      aria-hidden="true"
      focusable="false"
    >
      <path d={d} />
    </svg>
  );
}
