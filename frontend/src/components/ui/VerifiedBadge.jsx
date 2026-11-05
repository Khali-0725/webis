export function VerifiedBadge({ verified }) {
  if (!verified) return null;

  return (
    <span
      role="img"
      aria-label="Verified provider by WEBIS"
      title="WEBIS approved this provider's required documents"
      className="inline-flex h-6 w-6 shrink-0 items-center justify-center text-blue-600"
    >
      <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="m12 1.8 2.15 1.15 2.43-.2 1.43 1.97 2.31.76.43 2.4 1.61 1.83-.7 2.34.7 2.34-1.61 1.83-.43 2.4-2.31.76-1.43 1.97-2.43-.2L12 22.2l-2.15-1.15-2.43.2-1.43-1.97-2.31-.76-.43-2.4-1.61-1.83.7-2.34-.7-2.34 1.61-1.83.43-2.4 2.31-.76 1.43-1.97 2.43.2L12 1.8Z" />
        <path fill="white" d="m10.3 15.85-3.4-3.4 1.4-1.4 2 2 5.4-5.4 1.4 1.4-6.8 6.8Z" />
      </svg>
    </span>
  );
}
