import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { IconActionButton } from '@/components/ui/IconActionButton';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { LoadingState, EmptyState } from '@/components/ui/States';
import { fieldError } from '@/services/api/client';
import { availabilityApi } from '@/services/api/availabilityApi';
import { queryKeys } from '@/services/api/queryClient';
import { todayLocal } from '@/utils/date';

const DAYS = [
  { value: 0, label: 'Sunday' },
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
];

const DEFAULT_ROW = { enabled: false, start_time: '08:00', end_time: '17:00', slot_minutes: 60 };

function buildRowsFromRules(rules) {
  const rows = DAYS.map(() => ({ ...DEFAULT_ROW }));

  for (const rule of rules) {
    rows[rule.day_of_week] = {
      enabled: true,
      start_time: rule.start_time?.slice(0, 5) ?? '08:00',
      end_time: rule.end_time?.slice(0, 5) ?? '17:00',
      slot_minutes: rule.slot_minutes ?? 60,
    };
  }

  return rows;
}

export default function AvailabilityPage() {
  const queryClient = useQueryClient();
  const [notice, setNotice] = useState(null);
  const [rows, setRows] = useState(null);
  const [syncedAt, setSyncedAt] = useState(0);

  const [exceptionDate, setExceptionDate] = useState('');
  const [exceptionClosed, setExceptionClosed] = useState(true);
  const [exceptionStart, setExceptionStart] = useState('08:00');
  const [exceptionEnd, setExceptionEnd] = useState('17:00');
  const [exceptionReason, setExceptionReason] = useState('');
  const [editingException, setEditingException] = useState(null);
  const [holidayYear, setHolidayYear] = useState(new Date().getFullYear());
  const [confirmHolidays, setConfirmHolidays] = useState(false);

  const holidaysQuery = useQuery({ queryKey: ['provider', 'holidays', holidayYear], queryFn: () => availabilityApi.holidays(holidayYear) });
  const holidayMutation = useMutation({
    mutationFn: ({ enabled, confirmed }) => availabilityApi.updateHolidays(enabled, confirmed),
    onSuccess: () => {
      setConfirmHolidays(false);
      queryClient.invalidateQueries({ queryKey: ['provider', 'holidays'] });
      setNotice({ tone: 'success', message: 'Holiday preference updated. Existing bookings remain in place.' });
    },
    onError: (error) => {
      if (error?.status === 409) setConfirmHolidays(true);
      else setNotice({ tone: 'error', message: error?.message ?? 'Could not update holiday preference.' });
    },
  });

  const rulesQuery = useQuery({
    queryKey: queryKeys.provider.availabilityRules,
    queryFn: availabilityApi.getRules,
  });

  const exceptionsQuery = useQuery({
    queryKey: queryKeys.provider.availabilityExceptions,
    queryFn: availabilityApi.listExceptions,
  });

  // Adjust-during-render, not an effect - see ProviderProfilePage.jsx for why.
  if (rulesQuery.data && rulesQuery.dataUpdatedAt !== syncedAt) {
    setSyncedAt(rulesQuery.dataUpdatedAt);
    setRows(buildRowsFromRules(rulesQuery.data));
  }

  const rulesMutation = useMutation({
    mutationFn: availabilityApi.updateRules,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.provider.availabilityRules });
      setNotice({ tone: 'success', message: 'Weekly availability updated.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to update availability.' }),
  });

  const createExceptionMutation = useMutation({
    mutationFn: availabilityApi.createException,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.provider.availabilityExceptions });
      setExceptionDate('');
      setExceptionReason('');
      setNotice({ tone: 'success', message: 'Exception saved.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to save the exception.' }),
  });

  const deleteExceptionMutation = useMutation({
    mutationFn: availabilityApi.deleteException,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.provider.availabilityExceptions }),
  });

  if (!rows) {
    return <LoadingState label="Loading availability…" className="mt-10" />;
  }

  const updateRow = (index, patch) => {
    setRows((current) => current.map((row, i) => (i === index ? { ...row, ...patch } : row)));
  };

  const handleRulesSubmit = (event) => {
    event.preventDefault();
    setNotice(null);

    const rules = rows
      .map((row, index) => ({ ...row, day_of_week: index }))
      .filter((row) => row.enabled)
      .map(({ day_of_week, start_time, end_time, slot_minutes }) => ({
        day_of_week,
        start_time,
        end_time,
        slot_minutes: Number(slot_minutes),
      }));

    rulesMutation.mutate(rules);
  };

  const handleExceptionSubmit = (event) => {
    event.preventDefault();
    setNotice(null);

    createExceptionMutation.mutate({
      date: exceptionDate,
      is_closed: exceptionClosed,
      start_time: exceptionClosed ? undefined : exceptionStart,
      end_time: exceptionClosed ? undefined : exceptionEnd,
      reason: exceptionReason || undefined,
    });
  };

  return (
    <div className="min-w-0 space-y-4">
      {notice && <Alert tone={notice.tone}>{notice.message}</Alert>}

      <Card className="min-w-0 p-3 sm:p-5">
        <p className="mb-4 text-sm text-ink-muted">Choose working days and hours. Add unavailable dates below.</p>
        <section aria-labelledby="weekly-hours-heading">
          <h2 id="weekly-hours-heading" className="mb-3 text-base font-semibold text-navy-800">Weekly days and times</h2>
        <form onSubmit={handleRulesSubmit} className="min-w-0 divide-y divide-line rounded-lg border border-line px-2 sm:px-3">
          {DAYS.map((day, index) => {
            const row = rows[index];
            return (
              <div key={day.value} className="min-w-0 py-3 sm:flex sm:items-center sm:gap-3">
                <label className="flex items-center gap-2 text-sm font-medium text-ink sm:w-28 sm:shrink-0">
                  <input
                    type="checkbox"
                    checked={row.enabled}
                    onChange={(event) => updateRow(index, { enabled: event.target.checked })}
                    className="h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
                  />
                  {day.label}
                </label>
                <div className="mt-2 grid min-w-0 grid-cols-2 gap-2 sm:mt-0 sm:flex sm:flex-1 sm:items-center">
                <label className="grid min-w-0 gap-1 text-xs text-ink-muted sm:flex-1">From
                <input
                  type="time"
                  disabled={!row.enabled}
                  value={row.start_time}
                  onChange={(event) => updateRow(index, { start_time: event.target.value })}
                  className="h-9 w-full min-w-0 max-w-full rounded-lg border border-line bg-white px-1 text-xs text-ink disabled:bg-slate-50 disabled:text-ink-muted sm:px-2 sm:text-sm"
                />
                </label>
                <label className="grid min-w-0 gap-1 text-xs text-ink-muted sm:flex-1">Until
                <input
                  type="time"
                  disabled={!row.enabled}
                  value={row.end_time}
                  onChange={(event) => updateRow(index, { end_time: event.target.value })}
                  className="h-9 w-full min-w-0 max-w-full rounded-lg border border-line bg-white px-1 text-xs text-ink disabled:bg-slate-50 disabled:text-ink-muted sm:px-2 sm:text-sm"
                />
                </label>
                <label className="col-span-2 grid min-w-0 gap-1 text-xs text-ink-muted sm:flex-1">Slot length
                <select
                  disabled={!row.enabled}
                  value={row.slot_minutes}
                  onChange={(event) => updateRow(index, { slot_minutes: event.target.value })}
                  className="h-9 w-full min-w-0 max-w-full rounded-lg border border-line bg-white px-2 text-sm text-ink disabled:bg-slate-50 disabled:text-ink-muted"
                >
                  {[30, 60, 90, 120].map((minutes) => (
                    <option key={minutes} value={minutes}>
                      {minutes} min slots
                    </option>
                  ))}
                </select>
                </label>
                </div>
              </div>
            );
          })}

          <div className="py-3"><Button type="submit" loading={rulesMutation.isPending} className="w-full sm:w-auto">Save weekly availability</Button></div>
        </form>
        </section>

        <section aria-labelledby="unavailable-dates-heading" className="mt-8 border-t border-line pt-6">
          <h2 id="unavailable-dates-heading" className="mb-1 text-base font-semibold text-navy-800">Unavailable dates</h2>
          <p className="mb-4 text-sm text-ink-muted">Block a full date or set different available hours for that date. Existing bookings stay scheduled.</p>
        <form onSubmit={handleExceptionSubmit} className="mb-5 grid gap-3 border-b border-line pb-5 sm:grid-cols-2">
          <Input
            label="Date"
            type="date"
            required
            min={todayLocal()}
            value={exceptionDate}
            onChange={(event) => setExceptionDate(event.target.value)}
          />

          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Availability on this date</label>
            <select
              value={exceptionClosed ? 'closed' : 'partial'}
              onChange={(event) => setExceptionClosed(event.target.value === 'closed')}
              className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink"
            >
              <option value="closed">Unavailable all day</option>
              <option value="partial">Custom available hours</option>
            </select>
          </div>

          {!exceptionClosed && (
            <>
              <Input
                label="Start time"
                type="time"
                value={exceptionStart}
                onChange={(event) => setExceptionStart(event.target.value)}
              />
              <Input
                label="End time"
                type="time"
                value={exceptionEnd}
                onChange={(event) => setExceptionEnd(event.target.value)}
              />
            </>
          )}

          <Input
            label="Reason"
            hint="Optional"
            value={exceptionReason}
            onChange={(event) => setExceptionReason(event.target.value)}
            placeholder="e.g. Holiday, family event"
          />

          <div className="flex items-end">
            <Button type="submit" loading={createExceptionMutation.isPending} className="w-full sm:w-auto">
              Add date
            </Button>
          </div>
        </form>

        {exceptionsQuery.data?.length === 0 && (
          <EmptyState title="No unavailable dates" description="Add a date above to block a day or set custom hours." />
        )}

        {exceptionsQuery.data?.length > 0 && (
          <ul className="divide-y divide-line">
            {exceptionsQuery.data.map((exception) => (
              <li key={exception.id} className="flex min-w-0 flex-wrap items-center justify-between gap-2 py-3 text-sm">
                <div className="min-w-0 break-words">
                  <p className="font-medium text-ink">
                    {exception.date} — {exception.is_closed ? 'Unavailable all day' : `Available ${exception.start_time}–${exception.end_time}`}
                  </p>
                  {exception.reason && <p className="text-xs text-ink-muted [overflow-wrap:anywhere]">{exception.reason}</p>}
                </div>
                <div className="flex gap-2">
                  <IconActionButton icon="edit" label={`Edit ${exception.date}`} onClick={() => setEditingException(exception)} />
                  <IconActionButton
                    icon="trash"
                    label={`Remove ${exception.date}`}
                    onClick={() => deleteExceptionMutation.mutate(exception.id)}
                    loading={deleteExceptionMutation.isPending && deleteExceptionMutation.variables === exception.id}
                  />
                </div>
              </li>
            ))}
          </ul>
        )}
        </section>

        <details className="mt-6 border-t border-line pt-4">
          <summary className="cursor-pointer text-base font-semibold text-navy-800">Holiday exclusions</summary>
          <div className="mt-3">
          <label className="flex items-center gap-3 text-sm font-medium text-ink"><input type="checkbox" checked={Boolean(holidaysQuery.data?.exclude_all_holidays)} disabled={holidaysQuery.isPending || holidayMutation.isPending} onChange={(event) => holidayMutation.mutate({ enabled: event.target.checked, confirmed: false })} className="h-5 w-5 rounded border-line text-navy-700" /> Exclude all holidays</label>
          <p className="mt-2 text-xs text-ink-muted">Blocks new bookings on configured Philippine national regular and special non-working days. Existing bookings stay scheduled.</p>
          <label className="mt-4 block text-sm text-ink-muted">Calendar year<select value={holidayYear} onChange={(event) => setHolidayYear(Number(event.target.value))} className="ml-2 h-9 rounded-lg border border-line bg-white px-2 text-ink"><option value={2026}>2026</option><option value={2027}>2027</option></select></label>
          {holidaysQuery.data && <div className="mt-3 max-h-36 overflow-y-auto text-xs text-ink-muted">{holidaysQuery.data.calendar_available ? holidaysQuery.data.dates.map((holiday) => <span key={holiday.date} className="mr-3 inline-block py-1">{holiday.date} · {holiday.name}</span>) : 'No configured calendar for this year.'}</div>}
          </div>
        </details>
      </Card>
      <ConfirmDialog open={confirmHolidays} onClose={() => setConfirmHolidays(false)} onConfirm={() => holidayMutation.mutate({ enabled: true, confirmed: true })} title="Exclude holidays despite existing bookings?" description={holidayMutation.error?.message} confirmLabel="Exclude future holiday slots" loading={holidayMutation.isPending}>
        <p className="text-sm text-ink-muted">Existing bookings will remain scheduled. New bookings on the listed holidays will be blocked.</p>
      </ConfirmDialog>

      {editingException && (
        <ExceptionEditor
          exception={editingException}
          onClose={() => setEditingException(null)}
          onSaved={() => {
            setEditingException(null);
            queryClient.invalidateQueries({ queryKey: queryKeys.provider.availabilityExceptions });
            setNotice({ tone: 'success', message: 'Exception updated.' });
          }}
        />
      )}
    </div>
  );
}

function ExceptionEditor({ exception, onClose, onSaved }) {
  const [closed, setClosed] = useState(Boolean(exception.is_closed));
  const [start, setStart] = useState(exception.start_time?.slice(0, 5) ?? '08:00');
  const [end, setEnd] = useState(exception.end_time?.slice(0, 5) ?? '17:00');
  const [reason, setReason] = useState(exception.reason ?? '');

  const mutation = useMutation({
    mutationFn: (payload) => availabilityApi.updateException(exception.id, payload),
    onSuccess: onSaved,
  });

  const err = mutation.error;

  return (
    <Modal
      open
      onClose={onClose}
      title={`Edit ${exception.date}`}
      description="The date stays fixed - remove the exception and add another to move it."
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="exception-editor-form" loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <form
        id="exception-editor-form"
        className="grid gap-4"
        onSubmit={(event) => {
          event.preventDefault();
          mutation.mutate({
            is_closed: closed,
            start_time: closed ? null : start,
            end_time: closed ? null : end,
            reason: reason || null,
          });
        }}
      >
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <div>
          <label htmlFor="exception-editor-type" className="mb-1.5 block text-sm font-medium text-ink">
            Availability on this day
          </label>
          <select
            id="exception-editor-type"
            className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
            value={closed ? 'closed' : 'partial'}
            onChange={(event) => setClosed(event.target.value === 'closed')}
          >
            <option value="closed">Closed all day</option>
            <option value="partial">Special hours</option>
          </select>
        </div>
        {!closed && (
          <div className="grid min-w-0 grid-cols-1 gap-3 min-[400px]:grid-cols-2">
            <Input label="From" type="time" value={start} onChange={(e) => setStart(e.target.value)} error={fieldError(err, 'start_time')} />
            <Input label="To" type="time" value={end} onChange={(e) => setEnd(e.target.value)} error={fieldError(err, 'end_time')} />
          </div>
        )}
        <Input label="Reason" value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Optional" error={fieldError(err, 'reason')} />
      </form>
    </Modal>
  );
}
