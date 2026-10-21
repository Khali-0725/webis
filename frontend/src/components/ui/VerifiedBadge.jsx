export function VerifiedBadge({ verified }) {
  if (!verified) return null;
  return <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-800" title="Provider documents and account approved by WEBIS"><span aria-hidden="true">✓</span> Verified</span>;
}
