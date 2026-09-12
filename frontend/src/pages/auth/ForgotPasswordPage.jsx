import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Icon } from '@/components/ui/Icon';
import { useForgotPassword } from '@/hooks/useAuth';

const schema = z.object({
  email: z.string().min(1, 'Email address is required.').email('Enter a valid email address.'),
});

export default function ForgotPasswordPage() {
  const forgotPassword = useForgotPassword();
  const [sent, setSent] = useState(false);
  const [formError, setFormError] = useState(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { email: '' },
  });

  const onSubmit = async (values) => {
    setFormError(null);

    try {
      await forgotPassword.mutateAsync(values);
      setSent(true);
    } catch (error) {
      setFormError(error?.message ?? 'Unable to send a reset link right now.');
    }
  };

  if (sent) {
    return (
      <>
        <div className="mb-6 text-center">
          <span
            className="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-xl bg-navy-700 font-display text-lg font-bold text-white"
            aria-hidden="true"
          >
            W
          </span>
          <h1 className="font-display text-2xl font-bold text-navy-800">Check your email</h1>
        </div>

        <Alert tone="success">
          If an account exists for that address, we&apos;ve sent a link to reset your password.
          It expires in 60 minutes.
        </Alert>

        <p className="mt-6 text-center text-sm text-ink-muted">
          <Link to="/login" className="font-semibold text-brand hover:text-brand-600">
            Back to sign in
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
        <h1 className="font-display text-2xl font-bold text-navy-800">Reset your password</h1>
        <p className="mt-1 text-sm text-ink-muted">
          Enter the email on your account and we&apos;ll send you a reset link.
        </p>
      </div>

      {formError && (
        <Alert tone="error" className="mb-4">
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
        <Input
          label="Email Address"
          type="email"
          autoComplete="email"
          placeholder="you@email.com"
          icon={<Icon name="user" className="h-4 w-4" />}
          error={errors.email?.message}
          required
          {...register('email')}
        />

        <Button type="submit" fullWidth loading={isSubmitting || forgotPassword.isPending}>
          Send reset link
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-ink-muted">
        Remembered your password?{' '}
        <Link to="/login" className="font-semibold text-brand hover:text-brand-600">
          Sign in
        </Link>
      </p>
    </>
  );
}
