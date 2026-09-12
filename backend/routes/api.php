<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\HealthController;
use App\Support\Api\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEBIS API
|--------------------------------------------------------------------------
|
| Route groups mirror the audience that owns them, which is the same boundary
| the Policies enforce:
|
|   public/*     - no authentication
|   /me/*        - any signed-in user, acting on themselves
|   /client/*    - role:client
|   /provider/*  - role:provider
|   /admin/*     - role:admin
|
| Every group below is additionally guarded per-record by a Policy. Route
| middleware is the coarse gate, never the only gate.
|
*/

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------

Route::get('/health', HealthController::class)->name('api.health');

Route::get('/files/avatar/{user}', [FileController::class, 'avatar'])
    ->name('api.files.avatar');

Route::get('/barangays', [App\Http\Controllers\Api\Public\BarangayController::class, 'index'])
    ->name('api.barangays.index');

Route::get('/service-categories', [App\Http\Controllers\Api\Public\ServiceCategoryController::class, 'index'])
    ->name('api.service-categories.index');

Route::get('/services', [App\Http\Controllers\Api\Public\ServiceController::class, 'index'])
    ->name('api.services.index');

Route::get('/services/{service}', [App\Http\Controllers\Api\Public\ServiceController::class, 'show'])
    ->name('api.services.show');

Route::get('/providers', [App\Http\Controllers\Api\Public\ProviderController::class, 'index'])
    ->name('api.providers.index');

Route::get('/providers/{providerProfile}', [App\Http\Controllers\Api\Public\ProviderController::class, 'show'])
    ->name('api.providers.show');

Route::get('/providers/{providerProfile}/availability', [App\Http\Controllers\Api\Public\AvailabilityController::class, 'show'])
    ->name('api.providers.availability');

Route::get('/providers/{providerProfile}/reviews', [App\Http\Controllers\Api\Public\ReviewController::class, 'index'])
    ->name('api.providers.reviews');

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------

Route::prefix('auth')->name('api.auth.')->group(function () {
    // No `guest` middleware here: it issues an HTTP redirect, which is wrong
    // for an API. RegisterRequest::authorize() rejects signed-in callers with
    // a 403 inside the JSON envelope instead.
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('register');

    Route::post('/forgot-password', [App\Http\Controllers\Api\Auth\PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:auth')
        ->name('password.email');

    Route::post('/reset-password', [App\Http\Controllers\Api\Auth\PasswordResetController::class, 'reset'])
        ->middleware('throttle:auth')
        ->name('password.update');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

Route::get('/auth/email/verify/{id}/{hash}', [App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/auth/email/verification-notification', [App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

// ---------------------------------------------------------------------------
// Signed-in users (any role)
// ---------------------------------------------------------------------------

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // -----------------------------------------------------------------------
    // Profile Management
    // -----------------------------------------------------------------------
    Route::prefix('me')->name('api.me.')->group(function () {
        Route::patch('/profile', [App\Http\Controllers\Api\Auth\ProfileController::class, 'update'])->name('update');
        Route::post('/avatar', [App\Http\Controllers\Api\Auth\AvatarController::class, 'store'])->name('avatar.store');
        Route::get('/reviews', [App\Http\Controllers\Api\ReviewController::class, 'myReviews'])->name('reviews.index');
    });

    // -----------------------------------------------------------------------
    // Client
    // -----------------------------------------------------------------------
    Route::prefix('client')->name('api.client.')->middleware('role:client')->group(function () {
        Route::get('/ping', fn () => response()->json(['success' => true]));
    });

    // -----------------------------------------------------------------------
    // Provider
    // -----------------------------------------------------------------------
    Route::prefix('provider')->name('api.provider.')->middleware('role:provider')->group(function () {
        Route::get('/ping', fn () => response()->json(['success' => true]));
        Route::post('/verification/documents', [App\Http\Controllers\Api\Provider\VerificationDocumentController::class, 'store'])->name('verification.store');

        Route::get('/profile', [App\Http\Controllers\Api\Provider\ProfileController::class, 'show'])->name('profile.show');
        Route::patch('/profile', [App\Http\Controllers\Api\Provider\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/skills', [App\Http\Controllers\Api\Provider\ProfileController::class, 'updateSkills'])->name('skills.update');
        Route::put('/service-areas', [App\Http\Controllers\Api\Provider\ProfileController::class, 'updateServiceAreas'])->name('service-areas.update');

        Route::get('/services', [App\Http\Controllers\Api\Provider\ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{service}', [App\Http\Controllers\Api\Provider\ServiceController::class, 'show'])->name('services.show');
        Route::post('/services', [App\Http\Controllers\Api\Provider\ServiceController::class, 'store'])->name('services.store');
        Route::patch('/services/{service}', [App\Http\Controllers\Api\Provider\ServiceController::class, 'update'])->name('services.update');
        Route::patch('/services/{service}/publish', [App\Http\Controllers\Api\Provider\ServiceController::class, 'publish'])->name('services.publish');
        Route::patch('/services/{service}/deactivate', [App\Http\Controllers\Api\Provider\ServiceController::class, 'deactivate'])->name('services.deactivate');

        Route::get('/availability/rules', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'rules'])->name('availability.rules.index');
        Route::put('/availability/rules', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'updateRules'])->name('availability.rules.update');
        Route::get('/availability/exceptions', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'exceptions'])->name('availability.exceptions.index');
        Route::post('/availability/exceptions', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'storeException'])->name('availability.exceptions.store');
        Route::delete('/availability/exceptions/{exception}', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'destroyException'])->name('availability.exceptions.destroy');

        Route::get('/payment-methods', [App\Http\Controllers\Api\Provider\PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [App\Http\Controllers\Api\Provider\PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::patch('/payment-methods/{method}', [App\Http\Controllers\Api\Provider\PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::patch('/payment-methods/{method}/toggle', [App\Http\Controllers\Api\Provider\PaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');

        Route::get('/reviews', [App\Http\Controllers\Api\ReviewController::class, 'providerReviews'])->name('reviews.index');

        Route::get('/earnings/summary', [App\Http\Controllers\Api\Provider\EarningsController::class, 'summary'])->name('earnings.summary');
        Route::get('/earnings/over-time', [App\Http\Controllers\Api\Provider\EarningsController::class, 'overTime'])->name('earnings.over-time');
    });

    // -----------------------------------------------------------------------
    // Bookings - shared between clients and providers, scoped by BookingPolicy
    // -----------------------------------------------------------------------
    Route::prefix('bookings')->name('api.bookings.')->middleware('role:client,provider,admin')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\BookingController::class, 'store'])
            ->middleware('throttle:writes')
            ->name('store');
        Route::get('/', [App\Http\Controllers\Api\BookingController::class, 'index'])->name('index');
        Route::get('/{booking}', [App\Http\Controllers\Api\BookingController::class, 'show'])->name('show');
        Route::get('/{booking}/location', [App\Http\Controllers\Api\BookingController::class, 'location'])->name('location');
        Route::post('/{booking}/accept', [App\Http\Controllers\Api\BookingController::class, 'accept'])->name('accept');
        Route::post('/{booking}/reject', [App\Http\Controllers\Api\BookingController::class, 'reject'])->name('reject');
        Route::post('/{booking}/cancel', [App\Http\Controllers\Api\BookingController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/status', [App\Http\Controllers\Api\BookingController::class, 'updateStatus'])->name('status');

        Route::get('/{booking}/payment', [App\Http\Controllers\Api\PaymentController::class, 'show'])->name('payment.show');
        Route::post('/{booking}/payment/proof', [App\Http\Controllers\Api\PaymentController::class, 'submitProof'])
            ->middleware('throttle:uploads')
            ->name('payment.proof');

        Route::get('/{booking}/review', [App\Http\Controllers\Api\ReviewController::class, 'forBooking'])->name('review.show');
        Route::post('/{booking}/review', [App\Http\Controllers\Api\ReviewController::class, 'store'])
            ->middleware('throttle:writes')
            ->name('review.store');
    });

    // -----------------------------------------------------------------------
    // Reports - any signed-in client/provider may file one
    // -----------------------------------------------------------------------
    Route::post('/reports', [App\Http\Controllers\Api\ReportController::class, 'store'])
        ->middleware(['role:client,provider', 'throttle:writes'])
        ->name('api.reports.store');

    // -----------------------------------------------------------------------
    // Reviews - provider acts on reviews of their own profile
    // -----------------------------------------------------------------------
    Route::prefix('reviews')->name('api.reviews.')->middleware('role:provider')->group(function () {
        Route::post('/{review}/reply', [App\Http\Controllers\Api\ReviewController::class, 'reply'])->name('reply');
        Route::patch('/{review}/visibility', [App\Http\Controllers\Api\ReviewController::class, 'setVisibility'])->name('visibility');
    });

    // -----------------------------------------------------------------------
    // Payments - shared between clients, providers and admin, scoped by PaymentPolicy
    // -----------------------------------------------------------------------
    Route::prefix('payments')->name('api.payments.')->middleware('role:client,provider,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\PaymentController::class, 'index'])->name('index');
        Route::post('/{payment}/verify', [App\Http\Controllers\Api\PaymentController::class, 'verify'])->name('verify');
        Route::post('/{payment}/reject', [App\Http\Controllers\Api\PaymentController::class, 'reject'])->name('reject');
    });

    // -----------------------------------------------------------------------
    // Private file serving - every route here is policy-gated, never public
    // -----------------------------------------------------------------------
    Route::get('/files/payment-proof/{proof}', [FileController::class, 'paymentProof'])->name('api.files.payment-proof');
    Route::get('/files/payment-qr/{method}', [FileController::class, 'paymentQr'])->name('api.files.payment-qr');

    // -----------------------------------------------------------------------
    // Messaging - shared between clients and providers, scoped by ConversationPolicy
    // -----------------------------------------------------------------------
    Route::middleware('role:client,provider')->group(function () {
        Route::prefix('conversations')->name('api.conversations.')->group(function () {
            Route::post('/', [App\Http\Controllers\Api\ConversationController::class, 'store'])->name('store');
            Route::get('/', [App\Http\Controllers\Api\ConversationController::class, 'index'])->name('index');
            Route::get('/{conversation}/messages', [App\Http\Controllers\Api\ConversationController::class, 'messages'])->name('messages');
            Route::post('/{conversation}/messages', [App\Http\Controllers\Api\ConversationController::class, 'sendMessage'])
                ->middleware('throttle:writes')
                ->name('messages.store');
            Route::post('/{conversation}/read', [App\Http\Controllers\Api\ConversationController::class, 'markRead'])->name('read');
        });

        Route::get('/messages/unread-count', [App\Http\Controllers\Api\ConversationController::class, 'unreadCount'])
            ->name('api.messages.unread-count');
    });

    // -----------------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------------
    Route::prefix('admin')->name('api.admin.')->middleware('role:admin')->group(function () {
        Route::get('/ping', fn () => response()->json(['success' => true]));
        Route::get('/users', [App\Http\Controllers\Api\Admin\UserController::class, 'index'])->name('users.index');

        Route::patch('/users/{user}/suspend', [App\Http\Controllers\Api\Admin\UserController::class, 'suspend'])->name('users.suspend');
        Route::patch('/users/{user}/activate', [App\Http\Controllers\Api\Admin\UserController::class, 'activate'])->name('users.activate');
        
        Route::get('/users/{user}', [App\Http\Controllers\Api\Admin\UserController::class, 'show'])->name('users.show');

        // Verification routes
        Route::get('/verification/documents', [App\Http\Controllers\Api\Admin\VerificationController::class, 'index'])->name('verification.index');
        Route::patch('/verification/documents/{document}/approve', [App\Http\Controllers\Api\Admin\VerificationController::class, 'approve'])->name('verification.approve');
        Route::patch('/verification/documents/{document}/reject', [App\Http\Controllers\Api\Admin\VerificationController::class, 'reject'])->name('verification.reject');

        // Service category management
        Route::get('/categories', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'toggle'])->name('categories.toggle');

        // Providers (oversight/drill-down)
        Route::get('/providers', [App\Http\Controllers\Api\Admin\ProviderController::class, 'index'])->name('providers.index');
        Route::get('/providers/{providerProfile}', [App\Http\Controllers\Api\Admin\ProviderController::class, 'show'])->name('providers.show');

        // Services
        Route::get('/services', [App\Http\Controllers\Api\Admin\ServiceController::class, 'index'])->name('services.index');
        Route::patch('/services/{service}/toggle', [App\Http\Controllers\Api\Admin\ServiceController::class, 'toggle'])->name('services.toggle');

        // Barangays
        Route::get('/barangays', [App\Http\Controllers\Api\Admin\BarangayController::class, 'index'])->name('barangays.index');
        Route::post('/barangays', [App\Http\Controllers\Api\Admin\BarangayController::class, 'store'])->name('barangays.store');
        Route::patch('/barangays/{barangay}', [App\Http\Controllers\Api\Admin\BarangayController::class, 'update'])->name('barangays.update');
        Route::patch('/barangays/{barangay}/toggle', [App\Http\Controllers\Api\Admin\BarangayController::class, 'toggle'])->name('barangays.toggle');

        // Chat violations - per §9.3, never exposes the surrounding conversation
        Route::get('/chat-violations', [App\Http\Controllers\Api\Admin\ChatViolationController::class, 'index'])->name('chat-violations.index');
        Route::get('/chat-violations/{violation}', [App\Http\Controllers\Api\Admin\ChatViolationController::class, 'show'])->name('chat-violations.show');
        Route::post('/chat-violations/{violation}/warn', [App\Http\Controllers\Api\Admin\ChatViolationController::class, 'warn'])->name('chat-violations.warn');
        Route::post('/chat-violations/{violation}/suspend', [App\Http\Controllers\Api\Admin\ChatViolationController::class, 'suspend'])->name('chat-violations.suspend');
        Route::post('/chat-violations/{violation}/dismiss', [App\Http\Controllers\Api\Admin\ChatViolationController::class, 'dismiss'])->name('chat-violations.dismiss');

        // Reports
        Route::get('/reports', [App\Http\Controllers\Api\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/{report}/resolve', [App\Http\Controllers\Api\Admin\ReportController::class, 'resolve'])->name('reports.resolve');

        // Audit logs
        Route::get('/audit-logs', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        // Settings
        Route::get('/settings', [App\Http\Controllers\Api\Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [App\Http\Controllers\Api\Admin\SettingsController::class, 'update'])->name('settings.update');

        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/summary', [App\Http\Controllers\Api\Admin\AnalyticsController::class, 'summary'])->name('summary');
            Route::get('/bookings-over-time', [App\Http\Controllers\Api\Admin\AnalyticsController::class, 'bookingsOverTime'])->name('bookings-over-time');
            Route::get('/earnings-over-time', [App\Http\Controllers\Api\Admin\AnalyticsController::class, 'earningsOverTime'])->name('earnings-over-time');
            Route::get('/bookings-by-category', [App\Http\Controllers\Api\Admin\AnalyticsController::class, 'bookingsByCategory'])->name('bookings-by-category');
            Route::get('/top-providers', [App\Http\Controllers\Api\Admin\AnalyticsController::class, 'topProviders'])->name('top-providers');
            Route::get('/payment-summary', [App\Http\Controllers\Api\Admin\AnalyticsController::class, 'paymentSummary'])->name('payment-summary');
        });
    });
});

// ---------------------------------------------------------------------------
// Fallback - keeps unknown API paths inside the JSON envelope
// ---------------------------------------------------------------------------

Route::fallback(fn () => ApiResponse::error('The requested endpoint does not exist.', [], 404));
