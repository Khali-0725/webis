import { Button } from '@/components/ui/Button';

/**
 * Previous/Page X of Y/Next control for any endpoint using the shared
 * `{ page, per_page, total, last_page }` meta block from `ApiResponse::paginated()`.
 * Renders nothing for a single-page result, same as the pattern this was
 * extracted from (SearchResultsPage).
 */
export function Pagination({ meta, onChange, className }) {
  if (!meta || meta.last_page <= 1) return null;

  return (
    <div className={`mt-6 flex items-center justify-center gap-2 ${className ?? ''}`}>
      <Button variant="outline" size="sm" disabled={meta.page <= 1} onClick={() => onChange(meta.page - 1)}>
        Previous
      </Button>
      <span className="text-sm text-ink-muted">
        Page {meta.page} of {meta.last_page}
      </span>
      <Button
        variant="outline"
        size="sm"
        disabled={meta.page >= meta.last_page}
        onClick={() => onChange(meta.page + 1)}
      >
        Next
      </Button>
    </div>
  );
}
