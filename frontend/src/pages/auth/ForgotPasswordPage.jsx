import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Icon } from '@/components/ui/Icon';
import { useForgotPassword, useResetPassword, useVerifyResetCode } from '@/hooks/useAuth';

const emailSchema = z.object({ email: z.string().min(1, 'Email address is required.').email('Enter a valid email address.') });
const codeSchema = z.object({ code: z.string().regex(/^\d{6}$/, 'Enter the six-digit code from your email.') });
const passwordSchema = z.object({
  password: z.string().min(8, 'Use at least 8 characters.').regex(/[A-Za-z]/, 'Include at least one letter.').regex(/\d/, 'Include at least one number.'),
  password_confirmation: z.string(),
}).refine((value) => value.password === value.password_confirmation, {
  path: ['password_confirmation'], message: 'Passwords do not match.',
});

export default function ForgotPasswordPage() {
  const navigate = useNavigate();
  const sendCode = useForgotPassword();
  const verifyCode = useVerifyResetCode();
  const resetPassword = useResetPassword();
  const [step, setStep] = useState('email');
  const [email, setEmail] = useState('');
  const [grant, setGrant] = useState('');
  const [formError, setFormError] = useState(null);
  const [secondsLeft, setSecondsLeft] = useState(0);

  const emailForm = useForm({ resolver: zodResolver(emailSchema), defaultValues: { email: '' } });
  const codeForm = useForm({ resolver: zodResolver(codeSchema), defaultValues: { code: '' } });
  const passwordForm = useForm({ resolver: zodResolver(passwordSchema), defaultValues: { password: '', password_confirmation: '' } });

  useEffect(() => {
    if (step !== 'code' || secondsLeft === 0) return undefined;
    const timer = window.setInterval(() => setSecondsLeft((value) => Math.max(0, value - 1)), 1000);
    return () => window.clearInterval(timer);
  }, [secondsLeft, step]);

  const request = async ({ email: address }) => {
    setFormError(null);
    try {
      await sendCode.mutateAsync({ email: address });
      setEmail(address.trim());
      setSecondsLeft(60);
      setStep('code');
    } catch (error) {
      setFormError(error?.message ?? 'Unable to send a code right now.');
    }
  };

  const resend = async () => {
    setFormError(null);
    try {
      await sendCode.mutateAsync({ email });
      codeForm.reset();
      setSecondsLeft(60);
    } catch (error) {
      setFormError(error?.message ?? 'Unable to resend the code right now.');
    }
  };

  const verify = async ({ code }) => {
    setFormError(null);
    try {
      const result = await verifyCode.mutateAsync({ email, code });
      setGrant(result.grant);
      setStep('password');
    } catch (error) {
      setFormError(error?.message ?? 'Invalid or expired code. Request a new one.');
    }
  };

  const reset = async (values) => {
    setFormError(null);
    try {
      await resetPassword.mutateAsync({ email, grant, ...values });
      navigate('/login', { replace: true, state: { resetSuccess: true } });
    } catch (error) {
      const messages = error?.errors ?? {};
      if (messages.password?.[0]) {
        passwordForm.setError('password', { type: 'server', message: messages.password[0] });
      } else {
        setFormError(error?.message ?? 'Reset session expired. Request a new code.');
      }
    }
  };

  return (
    <>
      <div className="mb-5 text-center">
        <span className="mx-auto mb-3 grid h-10 w-10 place-items-center rounded-xl bg-navy-700 font-display font-bold text-white" aria-hidden="true">W</span>
        <h1 className="font-display text-2xl font-bold text-navy-800">
          {step === 'email' ? 'Reset your password' : step === 'code' ? 'Enter your email code' : 'Set a new password'}
        </h1>
        <p className="mt-1 text-sm text-ink-muted">
          {step === 'email' ? 'We will email you a six-digit code.' : step === 'code' ? `Enter the code sent to ${email}. It expires in 10 minutes.` : `For ${email}`}
        </p>
      </div>

      {formError && <Alert tone="error" className="mb-4">{formError}</Alert>}

      {step === 'email' && (
        <form onSubmit={emailForm.handleSubmit(request)} noValidate className="space-y-4">
          <Input label="Email Address" type="email" autoComplete="email" placeholder="you@email.com" icon={<Icon name="user" className="h-4 w-4" />} error={emailForm.formState.errors.email?.message} required {...emailForm.register('email')} />
          <Button type="submit" fullWidth loading={sendCode.isPending}>Send code</Button>
        </form>
      )}

      {step === 'code' && (
        <>
          <form onSubmit={codeForm.handleSubmit(verify)} noValidate className="space-y-4">
            <Input label="Six-digit code" type="text" inputMode="numeric" autoComplete="one-time-code" maxLength={6} placeholder="000000" error={codeForm.formState.errors.code?.message} required {...codeForm.register('code')} />
            <Button type="submit" fullWidth loading={verifyCode.isPending}>Verify code</Button>
          </form>
          <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
            <button type="button" onClick={() => { setFormError(null); setStep('email'); }} className="font-medium text-navy-700 hover:underline">Change email</button>
            <button type="button" onClick={resend} disabled={secondsLeft > 0 || sendCode.isPending} className="font-medium text-brand hover:underline disabled:cursor-not-allowed disabled:text-ink-muted">
              {secondsLeft > 0 ? `Resend in ${secondsLeft}s` : 'Resend code'}
            </button>
          </div>
        </>
      )}

      {step === 'password' && (
        <form onSubmit={passwordForm.handleSubmit(reset)} noValidate className="space-y-4">
          <Input label="New Password" type="password" autoComplete="new-password" hint="At least 8 characters, with a letter and a number." error={passwordForm.formState.errors.password?.message} required {...passwordForm.register('password')} />
          <Input label="Confirm New Password" type="password" autoComplete="new-password" error={passwordForm.formState.errors.password_confirmation?.message} required {...passwordForm.register('password_confirmation')} />
          <Button type="submit" fullWidth loading={resetPassword.isPending}>Reset password</Button>
        </form>
      )}

      <p className="mt-6 text-center text-sm text-ink-muted">
        <Link to="/login" className="font-semibold text-brand hover:text-brand-600">Back to sign in</Link>
      </p>
    </>
  );
}
