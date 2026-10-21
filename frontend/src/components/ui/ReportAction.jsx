import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { useAuth } from '@/hooks/useAuth';
import { reportApi } from '@/services/api/reportApi';
import { REPORT_REASON_META, ROLES } from '@/constants';

const REASONS = Object.entries(REPORT_REASON_META).filter(([value]) => value !== 'non_payment');

export function ReportAction({ type, id, label }) {
  const { isAuthenticated, role } = useAuth();
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [reason, setReason] = useState('other');
  const [details, setDetails] = useState('');
  const mutation = useMutation({
    mutationFn: () => reportApi.submit({ reportable_type: type, reportable_id: id, reason, details }),
    onSuccess: () => { setSubmitted(true); setOpen(false); },
  });

  if (isAuthenticated && role !== ROLES.CLIENT) return null;

  return (
    <section className="mt-6 rounded-lg border border-line bg-white p-4" aria-label={`Report ${label}`}>
      <p className="text-sm text-ink-muted">See a problem with this {label}? Send its details to the WEBIS administrator.</p>
      {submitted ? <Alert tone="success" className="mt-3">Report submitted for administrator review.</Alert> : !isAuthenticated ? (
        <Link to="/login" state={{ from: location.pathname }} className="mt-3 inline-block text-sm font-semibold text-brand hover:underline">Sign in to report {label}</Link>
      ) : !open ? (
        <Button className="mt-3" variant="outline" size="sm" onClick={() => setOpen(true)}>Report {label}</Button>
      ) : (
        <form className="mt-4 space-y-3" onSubmit={(event) => { event.preventDefault(); mutation.mutate(); }}>
          {mutation.error && <Alert tone="error">{mutation.error.message ?? 'Failed to submit the report.'}</Alert>}
          <label className="block text-sm font-medium text-ink">Reason
            <select required value={reason} onChange={(event) => setReason(event.target.value)} className="mt-1 block h-11 w-full rounded-lg border border-line bg-white px-3 text-sm sm:max-w-xs">
              {REASONS.map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}
            </select>
          </label>
          <label className="block text-sm font-medium text-ink">Explanation
            <textarea required minLength={10} maxLength={2000} rows={3} value={details} onChange={(event) => setDetails(event.target.value)} placeholder="Explain what happened (at least 10 characters)" className="mt-1 block w-full rounded-lg border border-line bg-white px-3 py-2 text-sm" />
          </label>
          <div className="flex flex-wrap gap-2">
            <Button type="submit" loading={mutation.isPending} disabled={details.trim().length < 10}>Submit report</Button>
            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
          </div>
        </form>
      )}
    </section>
  );
}
