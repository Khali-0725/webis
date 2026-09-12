import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { cn } from '@/utils/cn';
import { useRegister } from '@/hooks/useAuth';
import { ROLES, ROLE_HOME } from '@/constants';

const schema = z
  .object({
    first_name: z.string().trim().min(2, 'Enter your first name.').max(100),
    last_name: z.string().trim().min(2, 'Enter your last name.').max(100),
    email: z.string().min(1, 'Email address is required.').email('Enter a valid email address.'),
    phone: z
      .string()
      .trim()
      .regex(/^(\+?63|0)9\d{9}$/, 'Enter a valid Philippine mobile number (e.g. 09171234567).')
      .or(z.literal('')),
    role: z.enum([ROLES.CLIENT, ROLES.PROVIDER]),
    password: z
      .string()
      .min(8, 'Use at least 8 characters.')
      .regex(/[A-Za-z]/, 'Include at least one letter.')
      .regex(/\d/, 'Include at least one number.'),
    password_confirmation: z.string(),
    accepted_terms: z.literal(true, {
      errorMap: () => ({ message: 'You must accept the terms of use.' }),
    }),
  })
  .refine((data) => data.password === data.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match.',
  });

const ROLE_OPTIONS = [
  { value: ROLES.CLIENT, title: 'I need a service', caption: 'Book verified providers near you' },
  { value: ROLES.PROVIDER, title: 'I offer a service', caption: 'List your skills and get booked' },
];

export default function RegisterPage() {
  const navigate = useNavigate();
  const registerMutation = useRegister();
  const [formError, setFormError] = useState(null);

  const {
    register,
    handleSubmit,
    control,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      role: ROLES.CLIENT,
      password: '',
      password_confirmation: '',
      accepted_terms: false,
    },
  });

  // useWatch, not watch(): watch() returns a fresh function on every render,
  // which the React Compiler cannot memoize safely. useWatch subscribes to the
  // single field and re-renders only when it changes.
  const selectedRole = useWatch({ control, name: 'role' });

  const onSubmit = async (values) => {
    setFormError(null);

    try {
      const user = await registerMutation.mutateAsync({
        ...values,
        phone: values.phone || null,
      });

      navigate(ROLE_HOME[user.role] ?? '/', { replace: true });
    } catch (error) {
      const serverErrors = error?.errors ?? {};
      let attached = false;

      Object.entries(serverErrors).forEach(([field, messages]) => {
        setError(field, { type: 'server', message: messages[0] });
        attached = true;
      });

      if (!attached) setFormError(error?.message ?? 'Unable to create your account right now.');
    }
  };

  return (
    <>
      <div className="mb-6 text-center">
        <h1 className="font-display text-2xl font-bold text-navy-800">Create your account</h1>
        <p className="mt-1 text-sm text-ink-muted">Join WEBIS in under a minute</p>
      </div>

      {formError && (
        <Alert tone="error" className="mb-4">
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
        <fieldset>
          <legend className="mb-2 text-sm font-medium text-ink">I am signing up as</legend>
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
            {ROLE_OPTIONS.map((option) => {
              const active = selectedRole === option.value;

              return (
                <button
                  key={option.value}
                  type="button"
                  aria-pressed={active}
                  onClick={() => setValue('role', option.value, { shouldValidate: true })}
                  className={cn(
                    'rounded-lg border p-3 text-left transition-colors',
                    active
                      ? 'border-navy-600 bg-navy-50 ring-1 ring-navy-600'
                      : 'border-line hover:border-navy-300',
                  )}
                >
                  <span className="block text-sm font-semibold text-navy-800">{option.title}</span>
                  <span className="mt-0.5 block text-xs text-ink-muted">{option.caption}</span>
                </button>
              );
            })}
          </div>
          {errors.role && (
            <p role="alert" className="mt-1 text-xs font-medium text-red-600">
              {errors.role.message}
            </p>
          )}
        </fieldset>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input
            label="First Name"
            autoComplete="given-name"
            error={errors.first_name?.message}
            required
            {...register('first_name')}
          />
          <Input
            label="Last Name"
            autoComplete="family-name"
            error={errors.last_name?.message}
            required
            {...register('last_name')}
          />
        </div>

        <Input
          label="Email Address"
          type="email"
          autoComplete="email"
          placeholder="you@email.com"
          error={errors.email?.message}
          required
          {...register('email')}
        />

        <Input
          label="Mobile Number"
          type="tel"
          autoComplete="tel"
          placeholder="09171234567"
          hint="Optional. Used only for booking coordination."
          error={errors.phone?.message}
          {...register('phone')}
        />

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input
            label="Password"
            type="password"
            autoComplete="new-password"
            hint="At least 8 characters, with a letter and a number."
            error={errors.password?.message}
            required
            {...register('password')}
          />
          <Input
            label="Confirm Password"
            type="password"
            autoComplete="new-password"
            error={errors.password_confirmation?.message}
            required
            {...register('password_confirmation')}
          />
        </div>

        <div>
          <label className="flex cursor-pointer items-start gap-2 text-sm text-ink-muted">
            <input
              type="checkbox"
              className="mt-0.5 h-4 w-4 rounded border-line text-navy-700 focus:ring-brand"
              {...register('accepted_terms')}
            />
            <span>I agree to the WEBIS terms of use and privacy notice.</span>
          </label>
          {errors.accepted_terms && (
            <p role="alert" className="mt-1 text-xs font-medium text-red-600">
              {errors.accepted_terms.message}
            </p>
          )}
        </div>

        <Button type="submit" fullWidth loading={isSubmitting || registerMutation.isPending}>
          Create account
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-ink-muted">
        Already have an account?{' '}
        <Link to="/login" className="font-semibold text-brand hover:text-brand-600">
          Sign in
        </Link>
      </p>
    </>
  );
}
