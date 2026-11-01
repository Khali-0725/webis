import { Button } from './Button';
import { Icon } from './Icon';

/** Compact, neutral action with a visible hover title and screen-reader name. */
export function IconActionButton({ icon, label, loading = false, className = '', ...props }) {
  return (
    <Button
      size="sm"
      variant="subtle"
      className={`h-9 w-9 shrink-0 p-0 ${className}`}
      aria-label={label}
      title={label}
      loading={loading}
      {...props}
    >
      {!loading && <Icon name={icon} className="h-4 w-4" />}
    </Button>
  );
}
