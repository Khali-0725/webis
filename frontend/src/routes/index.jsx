import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import { PublicLayout } from '@/layouts/PublicLayout';
import { AuthLayout } from '@/layouts/AuthLayout';
import { DashboardLayout } from '@/layouts/DashboardLayout';
import { ProtectedRoute, GuestRoute } from '@/routes/ProtectedRoute';
import { Spinner } from '@/components/ui/Spinner';
import { ROLES } from '@/constants';

const LandingPage = lazy(() => import('@/pages/public/LandingPage'));
const LoginPage = lazy(() => import('@/pages/auth/LoginPage'));
const RegisterPage = lazy(() => import('@/pages/auth/RegisterPage'));
const ForgotPasswordPage = lazy(() => import('@/pages/auth/ForgotPasswordPage'));
const ResetPasswordPage = lazy(() => import('@/pages/auth/ResetPasswordPage'));
const ClientDashboard = lazy(() => import('@/pages/client/ClientDashboard'));
const ProviderDashboard = lazy(() => import('@/pages/provider/ProviderDashboard'));
const AdminDashboard = lazy(() => import('@/pages/admin/AdminDashboard'));
const NotFoundPage = lazy(() => import('@/pages/NotFoundPage'));
const ProfilePage = lazy(() => import('@/pages/settings/ProfilePage'));

const ProviderVerificationPage = lazy(() => import('@/pages/provider/verification/VerificationPage'));
const AdminVerificationPage = lazy(() => import('@/pages/admin/verification/VerificationPage'));

const SearchResultsPage = lazy(() => import('@/pages/public/SearchResultsPage'));
const ServiceDetailPage = lazy(() => import('@/pages/public/ServiceDetailPage'));
const PublicProviderProfilePage = lazy(() => import('@/pages/public/ProviderProfilePage'));
const ProvidersPage = lazy(() => import('@/pages/public/ProvidersPage'));
const AboutPage = lazy(() => import('@/pages/public/AboutPage'));
const ContactPage = lazy(() => import('@/pages/public/ContactPage'));
const PrivacyPolicyPage = lazy(() => import('@/pages/public/PrivacyPolicyPage'));

const ProviderProfilePage = lazy(() => import('@/pages/provider/profile/ProviderProfilePage'));
const ServiceListPage = lazy(() => import('@/pages/provider/services/ServiceListPage'));
const ServiceFormPage = lazy(() => import('@/pages/provider/services/ServiceFormPage'));

const CategoryPage = lazy(() => import('@/pages/admin/categories/CategoryPage'));
const UserListPage = lazy(() => import('@/pages/admin/users/UserListPage'));
const ProviderListPage = lazy(() => import('@/pages/admin/providers/ProviderListPage'));
const ProviderDetailPage = lazy(() => import('@/pages/admin/providers/ProviderDetailPage'));
const AdminServiceListPage = lazy(() => import('@/pages/admin/services/ServiceListPage'));
const BarangayPage = lazy(() => import('@/pages/admin/barangays/BarangayPage'));
const AdminBookingListPage = lazy(() => import('@/pages/admin/bookings/BookingListPage'));
const AdminPaymentListPage = lazy(() => import('@/pages/admin/payments/PaymentListPage'));
const ViolationListPage = lazy(() => import('@/pages/admin/violations/ViolationListPage'));
const ViolationDetailPage = lazy(() => import('@/pages/admin/violations/ViolationDetailPage'));
const ReportListPage = lazy(() => import('@/pages/admin/reports/ReportListPage'));
const AuditLogPage = lazy(() => import('@/pages/admin/audit-logs/AuditLogPage'));
const SettingsPage = lazy(() => import('@/pages/admin/settings/SettingsPage'));
const AnalyticsPage = lazy(() => import('@/pages/admin/analytics/AnalyticsPage'));

const ClientBookingListPage = lazy(() => import('@/pages/client/bookings/BookingListPage'));
const ProviderBookingListPage = lazy(() => import('@/pages/provider/bookings/BookingListPage'));
const BookingDetailPage = lazy(() => import('@/pages/bookings/BookingDetailPage'));
const AvailabilityPage = lazy(() => import('@/pages/provider/availability/AvailabilityPage'));

const ConversationListPage = lazy(() => import('@/pages/messaging/ConversationListPage'));
const ConversationThreadPage = lazy(() => import('@/pages/messaging/ConversationThreadPage'));

const PaymentMethodsPage = lazy(() => import('@/pages/provider/payment-methods/PaymentMethodsPage'));
const EarningsPage = lazy(() => import('@/pages/provider/earnings/EarningsPage'));

const ClientReviewListPage = lazy(() => import('@/pages/client/reviews/ReviewListPage'));
const ProviderReviewListPage = lazy(() => import('@/pages/provider/reviews/ReviewListPage'));
const MyReportsPage = lazy(() => import('@/pages/reports/MyReportsPage'));

function RouteFallback() {
  return (
    <div className="grid min-h-[50vh] place-items-center">
      <Spinner className="h-6 w-6 text-navy-600" />
    </div>
  );
}

export function AppRoutes() {
  return (
    <Suspense fallback={<RouteFallback />}>
      <Routes>
        {/* Public */}
        <Route element={<PublicLayout />}>
          <Route index element={<LandingPage />} />
          <Route path="search" element={<SearchResultsPage />} />
          <Route path="services/:id" element={<ServiceDetailPage />} />
          <Route path="providers" element={<ProvidersPage />} />
          <Route path="providers/:id" element={<PublicProviderProfilePage />} />
          <Route path="about" element={<AboutPage />} />
          <Route path="contact" element={<ContactPage />} />
          <Route path="privacy" element={<PrivacyPolicyPage />} />
        </Route>

        {/* Guest-only */}
        <Route element={<GuestRoute />}>
          <Route element={<AuthLayout />}>
            <Route path="login" element={<LoginPage />} />
            <Route path="register" element={<RegisterPage />} />
            <Route path="forgot-password" element={<ForgotPasswordPage />} />
            <Route path="reset-password" element={<ResetPasswordPage />} />
          </Route>
        </Route>

        {/* Client portal */}
        <Route element={<ProtectedRoute allow={[ROLES.CLIENT]} />}>
          <Route path="client" element={<DashboardLayout title="Dashboard" />}>
            <Route path="dashboard" element={<ClientDashboard />} />
          </Route>
          <Route path="client/bookings" element={<DashboardLayout title="My Bookings" />}>
            <Route index element={<ClientBookingListPage />} />
          </Route>
          <Route path="client/bookings/:id" element={<DashboardLayout title="Booking Details" />}>
            <Route index element={<BookingDetailPage />} />
          </Route>
          <Route path="client/messages" element={<DashboardLayout title="Messages" />}>
            <Route index element={<ConversationListPage />} />
          </Route>
          <Route path="client/messages/:id" element={<DashboardLayout title="Conversation" />}>
            <Route index element={<ConversationThreadPage />} />
          </Route>
          <Route path="client/reviews" element={<DashboardLayout title="My Reviews" />}>
            <Route index element={<ClientReviewListPage />} />
          </Route>
          <Route path="client/reports" element={<DashboardLayout title="My Reports" />}>
            <Route index element={<MyReportsPage />} />
          </Route>
        </Route>

        {/* Provider portal */}
        <Route element={<ProtectedRoute allow={[ROLES.PROVIDER]} />}>
          <Route path="provider" element={<DashboardLayout title="Dashboard" />}>
            <Route path="dashboard" element={<ProviderDashboard />} />
          </Route>
          <Route path="provider/profile" element={<DashboardLayout title="My Profile" />}>
            <Route index element={<ProviderProfilePage />} />
          </Route>
          <Route path="provider/services" element={<DashboardLayout title="My Services" />}>
            <Route index element={<ServiceListPage />} />
          </Route>
          <Route path="provider/services/new" element={<DashboardLayout title="Create Service" />}>
            <Route index element={<ServiceFormPage />} />
          </Route>
          <Route path="provider/services/:id/edit" element={<DashboardLayout title="Edit Service" />}>
            <Route index element={<ServiceFormPage />} />
          </Route>
          <Route path="provider/bookings" element={<DashboardLayout title="Bookings" />}>
            <Route index element={<ProviderBookingListPage />} />
          </Route>
          <Route path="provider/bookings/:id" element={<DashboardLayout title="Booking Details" />}>
            <Route index element={<BookingDetailPage />} />
          </Route>
          <Route path="provider/availability" element={<DashboardLayout title="Availability" />}>
            <Route index element={<AvailabilityPage />} />
          </Route>
          <Route path="provider/payment-methods" element={<DashboardLayout title="Payment Methods" />}>
            <Route index element={<PaymentMethodsPage />} />
          </Route>
          <Route path="provider/earnings" element={<DashboardLayout title="Earnings" />}>
            <Route index element={<EarningsPage />} />
          </Route>
          <Route path="provider/messages" element={<DashboardLayout title="Messages" />}>
            <Route index element={<ConversationListPage />} />
          </Route>
          <Route path="provider/messages/:id" element={<DashboardLayout title="Conversation" />}>
            <Route index element={<ConversationThreadPage />} />
          </Route>
          <Route path="provider/reviews" element={<DashboardLayout title="Reviews" />}>
            <Route index element={<ProviderReviewListPage />} />
          </Route>
          <Route path="provider/reports" element={<DashboardLayout title="My Reports" />}>
            <Route index element={<MyReportsPage />} />
          </Route>
        </Route>

        {/* Admin portal */}
        <Route element={<ProtectedRoute allow={[ROLES.ADMIN]} />}>
          <Route path="admin" element={<DashboardLayout title="Dashboard" />}>
            <Route path="dashboard" element={<AdminDashboard />} />
          </Route>
          <Route path="admin/categories" element={<DashboardLayout title="Service Categories" />}>
            <Route index element={<CategoryPage />} />
          </Route>
          <Route path="admin/users" element={<DashboardLayout title="Users" />}>
            <Route index element={<UserListPage />} />
          </Route>
          <Route path="admin/providers" element={<DashboardLayout title="Providers" />}>
            <Route index element={<ProviderListPage />} />
          </Route>
          <Route path="admin/providers/:id" element={<DashboardLayout title="Provider Details" />}>
            <Route index element={<ProviderDetailPage />} />
          </Route>
          <Route path="admin/services" element={<DashboardLayout title="Services" />}>
            <Route index element={<AdminServiceListPage />} />
          </Route>
          <Route path="admin/barangays" element={<DashboardLayout title="Barangays" />}>
            <Route index element={<BarangayPage />} />
          </Route>
          <Route path="admin/bookings" element={<DashboardLayout title="All Bookings" />}>
            <Route index element={<AdminBookingListPage />} />
          </Route>
          <Route path="admin/bookings/:id" element={<DashboardLayout title="Booking Details" />}>
            <Route index element={<BookingDetailPage />} />
          </Route>
          <Route path="admin/payments" element={<DashboardLayout title="All Payments" />}>
            <Route index element={<AdminPaymentListPage />} />
          </Route>
          <Route path="admin/violations" element={<DashboardLayout title="Chat Violations" />}>
            <Route index element={<ViolationListPage />} />
          </Route>
          <Route path="admin/violations/:id" element={<DashboardLayout title="Flagged Message" />}>
            <Route index element={<ViolationDetailPage />} />
          </Route>
          <Route path="admin/reports" element={<DashboardLayout title="Reports" />}>
            <Route index element={<ReportListPage />} />
          </Route>
          <Route path="admin/audit-logs" element={<DashboardLayout title="Audit Logs" />}>
            <Route index element={<AuditLogPage />} />
          </Route>
          <Route path="admin/settings" element={<DashboardLayout title="Platform Settings" />}>
            <Route index element={<SettingsPage />} />
          </Route>
          <Route path="admin/analytics" element={<DashboardLayout title="Analytics" />}>
            <Route index element={<AnalyticsPage />} />
          </Route>
        </Route>

        {/* Settings */}
        <Route element={<ProtectedRoute allow={[ROLES.CLIENT, ROLES.PROVIDER, ROLES.ADMIN]} />}>
          <Route path="profile" element={<DashboardLayout title="Profile Settings" />}>
            <Route index element={<ProfilePage />} />
          </Route>
        </Route>

        {/* Admin Verification */}
        <Route element={<ProtectedRoute allow={[ROLES.ADMIN]} />}>
          <Route path="admin/verification" element={<DashboardLayout title="Verifications" />}>
            <Route index element={<AdminVerificationPage />} />
          </Route>
        </Route>

        {/* Provider Verification */}
        <Route element={<ProtectedRoute allow={[ROLES.PROVIDER]} />}>
          <Route path="provider/verification" element={<DashboardLayout title="Verification" />}>
            <Route index element={<ProviderVerificationPage />} />
          </Route>
        </Route>

        <Route element={<PublicLayout />}>
          <Route path="*" element={<NotFoundPage />} />
        </Route>
      </Routes>
    </Suspense>
  );
}
