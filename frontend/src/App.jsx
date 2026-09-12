import { Component } from 'react';
import { BrowserRouter } from 'react-router-dom';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/services/api/queryClient';
import { AppRoutes } from '@/routes';
import { useAuthBootstrap } from '@/hooks/useAuth';
import { Button } from '@/components/ui/Button';

/**
 * Catches render-time crashes so a broken subtree shows a recoverable message
 * instead of a blank white page.
 */
class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidCatch(error, info) {
    // Replaced by a real reporter (Sentry or a Laravel log endpoint) in Phase 10.
    console.error('WEBIS render error', error, info);
  }

  render() {
    if (!this.state.hasError) return this.props.children;

    return (
      <div className="grid min-h-screen place-items-center bg-canvas px-4 text-center">
        <div>
          <h1 className="font-display text-xl font-semibold text-navy-800">
            Something went wrong
          </h1>
          <p className="mt-1 text-sm text-ink-muted">
            The page could not be displayed. Reloading usually fixes it.
          </p>
          <Button className="mt-4" onClick={() => window.location.reload()}>
            Reload page
          </Button>
        </div>
      </div>
    );
  }
}

/** Resolves the session once before the router renders anything protected. */
function AuthBootstrap({ children }) {
  useAuthBootstrap();

  return children;
}

export default function App() {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter>
          <AuthBootstrap>
            <AppRoutes />
          </AuthBootstrap>
        </BrowserRouter>
      </QueryClientProvider>
    </ErrorBoundary>
  );
}
