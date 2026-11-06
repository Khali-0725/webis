import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';

export default function ResetPasswordPage() {
  return (
    <>
      <h1 className="mb-4 text-center font-display text-2xl font-bold text-navy-800">Request a new code</h1>
      <Alert tone="warning">Password reset links have been replaced with email codes. Request a new code to continue.</Alert>
      <p className="mt-6 text-center text-sm">
        <Link to="/forgot-password" className="font-semibold text-brand hover:text-brand-600">Send me a code</Link>
      </p>
    </>
  );
}
