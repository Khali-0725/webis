/**
 * Values that must stay in step with the backend enums.
 *
 * @see backend/app/Enums/UserRole.php
 * @see backend/app/Enums/UserStatus.php
 */

export const ROLES = Object.freeze({
  CLIENT: 'client',
  PROVIDER: 'provider',
  ADMIN: 'admin',
});

export const ROLE_LABELS = Object.freeze({
  [ROLES.CLIENT]: 'Client',
  [ROLES.PROVIDER]: 'Service Provider',
  [ROLES.ADMIN]: 'Administrator',
});

export const USER_STATUS = Object.freeze({
  ACTIVE: 'active',
  SUSPENDED: 'suspended',
});

/** Where each role lands after signing in. Mirrors UserRole::homePath(). */
export const ROLE_HOME = Object.freeze({
  [ROLES.CLIENT]: '/client/dashboard',
  [ROLES.PROVIDER]: '/provider/dashboard',
  [ROLES.ADMIN]: '/admin/dashboard',
});

/**
 * Booking statuses.
 *
 * @see backend/app/Enums/BookingStatus.php
 */
export const BOOKING_STATUS = Object.freeze({
  PENDING: 'pending',
  ACCEPTED: 'accepted',
  REJECTED: 'rejected',
  CANCELLED: 'cancelled',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  DISPUTED: 'disputed',
});

export const BOOKING_STATUS_META = Object.freeze({
  pending: { label: 'Pending', tone: 'warning' },
  accepted: { label: 'Accepted', tone: 'success' },
  rejected: { label: 'Rejected', tone: 'danger' },
  cancelled: { label: 'Cancelled', tone: 'neutral' },
  in_progress: { label: 'In Progress', tone: 'info' },
  completed: { label: 'Completed', tone: 'success' },
  disputed: { label: 'Disputed', tone: 'danger' },
});

/**
 * Legal next statuses from each status - mirrors BookingStatus::transitions()
 * on the backend. Used only to decide which action buttons to show; the
 * backend re-checks every rule regardless, so this is UX, not the guard.
 */
export const BOOKING_TRANSITIONS = Object.freeze({
  pending: ['accepted', 'rejected', 'cancelled'],
  accepted: ['in_progress', 'cancelled'],
  in_progress: ['completed', 'disputed'],
  completed: ['disputed'],
  rejected: [],
  cancelled: [],
  disputed: [],
});

/**
 * @see backend/app/Enums/VerificationStatus.php
 */
export const VERIFICATION_STATUS = Object.freeze({
  PENDING: 'pending',
  APPROVED: 'approved',
  REJECTED: 'rejected',
});

export const VERIFICATION_STATUS_META = Object.freeze({
  pending: { label: 'Pending Review', tone: 'warning' },
  approved: { label: 'Verified', tone: 'success' },
  rejected: { label: 'Rejected', tone: 'danger' },
});

/**
 * @see backend/app/Enums/PricingType.php
 */
export const PRICING_TYPE = Object.freeze({
  FIXED: 'fixed',
  HOURLY: 'hourly',
  QUOTE: 'quote',
});

export const PRICING_TYPE_META = Object.freeze({
  fixed: { label: 'Fixed price' },
  hourly: { label: 'Per hour' },
  quote: { label: 'Quoted on request' },
});

/**
 * @see backend/app/Enums/PaymentStatus.php
 */
export const PAYMENT_STATUS = Object.freeze({
  PENDING: 'pending',
  PROOF_SUBMITTED: 'proof_submitted',
  VERIFIED: 'verified',
  REJECTED: 'rejected',
  REFUNDED: 'refunded',
});

export const PAYMENT_STATUS_META = Object.freeze({
  pending: { label: 'Awaiting Payment', tone: 'warning' },
  proof_submitted: { label: 'Proof Submitted', tone: 'info' },
  verified: { label: 'Verified', tone: 'success' },
  rejected: { label: 'Rejected', tone: 'danger' },
  refunded: { label: 'Refunded', tone: 'neutral' },
});

/**
 * @see backend/app/Enums/SettlementMethod.php
 */
export const SETTLEMENT_METHOD_META = Object.freeze({
  cash: { label: 'Cash', description: 'Pay the provider directly, in person.' },
  online: { label: 'Online Payment', description: "Pay via the provider's GCash, Maya, bank, or QR Ph." },
});

/**
 * @see backend/app/Enums/ReportReason.php
 */
export const REPORT_REASON_META = Object.freeze({
  non_payment: { label: 'Client did not pay' },
  off_platform_transaction: { label: 'Asked to transact off-platform' },
  fraud_or_scam: { label: 'Fraud or scam' },
  inappropriate_content: { label: 'Inappropriate content or behaviour' },
  poor_service_quality: { label: 'Poor service quality' },
  impersonation: { label: 'Impersonation / fake account' },
  other: { label: 'Other' },
});

/**
 * @see backend/app/Enums/PaymentMethodType.php
 */
export const PAYMENT_METHOD_TYPE = Object.freeze({
  QRPH: 'qrph',
  GCASH: 'gcash',
  MAYA: 'maya',
  BANK: 'bank',
});

export const PAYMENT_METHOD_TYPE_META = Object.freeze({
  qrph: { label: 'QR Ph', usesQrImage: true },
  gcash: { label: 'GCash', usesQrImage: true },
  maya: { label: 'Maya', usesQrImage: true },
  bank: { label: 'Bank Transfer', usesQrImage: false },
});

export const SERVICE_SORT_OPTIONS = Object.freeze([
  { value: 'newest', label: 'Newest' },
  { value: 'price_low', label: 'Price: Low to High' },
  { value: 'price_high', label: 'Price: High to Low' },
  { value: 'rating', label: 'Highest Rated' },
  { value: 'most_booked', label: 'Most Booked' },
]);

export const APP_NAME = import.meta.env.VITE_APP_NAME || 'WEBIS';
