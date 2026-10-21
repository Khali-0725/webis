import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { appealApi } from '@/services/api/appealApi';

export default function AppealPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [explanation, setExplanation] = useState('');
  const [mode, setMode] = useState('submit');
  const submit = useMutation({ mutationFn: appealApi.submit });
  const status = useMutation({ mutationFn: appealApi.status });
  const mutation = mode === 'submit' ? submit : status;

  const handleSubmit = (event) => {
    event.preventDefault();
    mutation.mutate(mode === 'submit' ? { email, password, explanation } : { email, password });
  };

  return <div className="mx-auto max-w-xl px-4 py-12 sm:px-6">
    <Card title="Suspension appeal">
      <p className="mb-5 text-sm text-ink-muted">Suspended clients and providers can request an administrator review here without access to business features. Sign in with your account email and password to submit or check an appeal.</p>
      <div className="mb-5 flex gap-2"><Button type="button" variant={mode === 'submit' ? 'brand' : 'outline'} onClick={() => setMode('submit')}>Submit appeal</Button><Button type="button" variant={mode === 'status' ? 'brand' : 'outline'} onClick={() => setMode('status')}>Check status</Button></div>
      {mutation.isError && <Alert tone="error" className="mb-4">{mutation.error?.message || 'Appeal request failed.'}</Alert>}
      {submit.isSuccess && mode === 'submit' && <Alert tone="success" className="mb-4">Your appeal was submitted for administrator review.</Alert>}
      {status.isSuccess && mode === 'status' && <div className="mb-4 space-y-2">{status.data.length ? status.data.map((item) => <div key={item.id} className="rounded-lg border border-line p-3 text-sm"><p className="font-semibold text-ink">Appeal #{item.id}: {item.status}</p><p className="text-ink-muted">Submitted {new Date(item.created_at).toLocaleDateString('en-PH')}</p>{item.decision_rationale && <p className="mt-1 text-ink-muted">Decision: {item.decision_rationale}</p>}</div>) : <p className="text-sm text-ink-muted">No appeals found for this account.</p>}</div>}
      <form onSubmit={handleSubmit} className="space-y-4">
        <Input label="Email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
        <Input label="Password" type="password" required value={password} onChange={(e) => setPassword(e.target.value)} />
        {mode === 'submit' && <label className="block text-sm font-medium text-ink">Why should this suspension be reviewed?<textarea required minLength={20} maxLength={3000} rows={6} value={explanation} onChange={(e) => setExplanation(e.target.value)} className="mt-1 block w-full rounded-lg border border-line bg-white p-3 text-sm" /></label>}
        <Button type="submit" loading={mutation.isPending}>{mode === 'submit' ? 'Submit appeal' : 'Check appeal status'}</Button>
      </form>
      <Link to="/login" className="mt-5 inline-block text-sm text-brand hover:underline">Back to login</Link>
    </Card>
  </div>;
}
