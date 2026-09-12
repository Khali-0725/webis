import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Icon } from '@/components/ui/Icon';
import { useResetPassword } from '@/hooks/useAuth';

const schema = z
  .object({
    password: z
      .string()
      .min(8, 'Use at least 8 characters.')
      .regex(/[A-Za-z]/, 'Include at least one letter.')
      .regex(/\d/, 'Include at least one number.'),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match.',
  });

export default function ResetPasswordPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const resetPassword = useResetPassword();
  const [formError, setFormError] = useState(null);

  const token = searchParams.get('token') ?? '';
  const email = searchParams.get('email') ?? '';

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { password: '', password_confirmation: '' },
  });

  const onSubmit = async (values) => {
    setFormError(null);

    try {
      await resetPassword.mutateAsync({ ...values, token, email });
      navigate('/login', {
        replace: true,
        state: { resetSuccess: true },
      });
    } catch (error) {
      const serverErrors = error?.errors ?? {};
      let attached = false;

      Object.entries(serverErrors).forEach(([field, messages]) => {
        if (['password', 'password_confirmation'].includes(field)) {
          setError(field, { type: 'server', message: messages[0] });
          attached = true;
        }
      });

      if (!attached) {
        setFormError(error?.message ?? 'This reset link is invalid or has expired.');
      }
    }
  };

  if (!token || !email) {
    return (
      <>
        <div className="mb-6 text-center">
          <h1 className="font-display text-2xl font-bold text-navy-800">Invalid reset link</h1>
        </div>

        <Alert tone="error">
          This password reset link is missing information. Request a new one below.
        </Alert>

        <p className="mt-6 text-center text-sm text-ink-muted">
          <Link to="/forgot-password" className="font-semibold text-brand hover:text-brand-600">
            Request a new link
          </Link>
        </p>
      </>
    );
  }

  return (
    <>
      <div className="mb-6 text-center">
        <span
          className="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-xl bg-navy-700 font-display text-lg font-bold text-white"
          aria-hidden="true"
        >
          W
        </span>
        <h1 className="font-display text-2xl font-bold text-navy-800">Set a new password</h1>
        <p className="mt-1 text-sm text-ink-muted">for {email}</p>
      </div>

      {formError && (
        <Alert tone="error" className="mb-4">
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
        <Input
          label="New Password"
          type="password"
          autoComplete="new-password"
          hint="At least 8 characters, with a letter and a number."
          icon={<Icon name="shield" className="h-4 w-4" />}
          error={errors.password?.message}
          required
          {...register('password')}
        />

        <Input
          label="Confirm New Password"
          type="password"
          autoComplete="new-password"
          icon={<Icon name="shield" className="h-4 w-4" />}
          error={errors.password_confirmation?.message}
          required
          {...register('password_confirmation')}
        />

        <Button type="submit" fullWidth loading={isSubmitting || resetPassword.isPending}>
          Reset password
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-ink-muted">
        <Link to="/login" className="font-semibold text-brand hover:text-brand-600">
          Back to sign in
        </Link>
      </p>
    </>
  );
}
