export function Divider({ children }) {
  return (
    <div className="flex items-center gap-3" role="separator">
      <span className="h-px flex-1 bg-line" aria-hidden="true" />
      {children && (
        <span className="text-xs font-medium uppercase tracking-wide text-ink-muted">{children}</span>
      )}
      <span className="h-px flex-1 bg-line" aria-hidden="true" />
    </div>
  );
}
