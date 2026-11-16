export function formatPresence(participant) {
  if (!participant) return '';
  if (participant.is_online) return 'Active now';
  if (!participant.last_seen_at) return 'Last online unavailable';

  const minutes = Math.max(0, Math.floor((Date.now() - new Date(participant.last_seen_at).getTime()) / 60000));
  if (minutes < 1) return 'Last online just now';

  return `Last online ${minutes} minute${minutes === 1 ? '' : 's'} ago`;
}
