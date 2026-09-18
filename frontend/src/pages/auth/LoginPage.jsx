import { useCallback, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Icon } from '@/components/ui/Icon';
import { Divider } from '@/components/ui/Divider';
import { GoogleButton } from '@/components/ui/GoogleButton';
import { useLogin, useGoogleAuth } from '@/hooks/useAuth';
import { googleSignInEnabled } from '@/services/auth/googleIdentity';
import { ROLE_HOME } from '@/constants';

const schema = z.object({
  email: z.string().min(1, 'Email address is required.').email('Enter a valid email address.'),
  password: z.string().min(1, 'Password is required.'),
  remember: z.boolean().optional(),
});

export default function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const login = useLogin();
  const googleAuth = useGoogleAuth();
  const [formError, setFormError] = useState(null);
  const resetNotice = Boolean(location.state?.resetSuccess);

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', remember: false },
  });

  const onSubmit = async (values) => {
    setFormError(null);

    try {
      const user = await login.mutateAsync(values);
      const intended = location.state?.from;

      navigate(intended || ROLE_HOME[user.role] || '/', { replace: true });
    } catch (error) {
      // Field-level messages from the server win over the generic banner.
      const serverErrors = error?.errors ?? {};
      let attached = false;

      Object.entries(serverErrors).forEach(([field, messages]) => {
        if (['email', 'password'].includes(field)) {
          setError(field, { type: 'server', message: messages[0] });
          attached = true;
        }
      });

      if (!attached) setFormError(error?.message ?? 'Unable to sign in right now.');
    }
  };

  const handleGoogleCredential = useCallback(
    async (credential) => {
      setFormError(null);

      try {
        const user = await googleAuth.mutateAsync({ credential });
        const intended = location.state?.from;

        navigate(intended || ROLE_HOME[user.role] || '/', { replace: true });
      } catch (error) {
        setFormError(error?.message ?? 'Unable to continue with Google right now.');
      }
    },
    [googleAuth, location.state, navigate],
  );

  return (
    <>
      <div className="mb-6 text-center">
        <span
          className="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-xl bg-navy-700 font-display text-lg font-bold text-white"
          aria-hidden="true"
        >
          W
        </span>
        <h1 className="font-display text-2xl font-bold text-navy-800">Welcome</h1>
        <p className="mt-1 text-sm text-ink-muted">Sign in to your WEBIS account</p>
      </div>

      {resetNotice && (
        <Alert tone="success" className="mb-4">
          Your password has been reset. Sign in with your new password.
        </Alert>
      )}

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

        <Input
          label="Password"
          type="password"
          autoComplete="current-password"
          placeholder="Enter password"
          icon={<Icon name="shield" className="h-4 w-4" />}
          error={errors.password?.message}
          required
          {...register('password')}
        />

        <div className="flex items-center justify-between">
          <label className="flex cursor-pointer items-center gap-2 text-sm text-ink-muted">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
              {...register('remember')}
            />
            Remember me
          </label>

          <Link to="/forgot-password" className="text-sm font-medium text-brand hover:text-brand-600">
            Forgot password?
          </Link>
        </div>

        <Button type="submit" fullWidth loading={isSubmitting || login.isPending}>
          Login
        </Button>

        {googleSignInEnabled && (
          <>
            <Divider>Or</Divider>
            <GoogleButton onCredential={handleGoogleCredential} disabled={googleAuth.isPending || isSubmitting} />
          </>
        )}
      </form>

      <p className="mt-6 text-center text-sm text-ink-muted">
        Don&apos;t have an account?{' '}
        <Link to="/register" className="font-semibold text-brand hover:text-brand-600">
          Register
        </Link>
      </p>
    </>
  );
}
