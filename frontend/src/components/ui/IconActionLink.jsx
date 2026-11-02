import { Link } from 'react-router-dom';
import { Icon } from './Icon';

/** Compact navigation action with a hover title and screen-reader name. */
export function IconActionLink({ to, icon, label }) {
  return (
    <Link
      to={to}
      className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line text-navy-700 hover:bg-canvas"
      aria-label={label}
      title={label}
    >
      <Icon name={icon} className="h-4 w-4" />
    </Link>
  );
}
