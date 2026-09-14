<?php

use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\ChatViolationController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\VerificationController;
use App\Http\Controllers\Api\Auth\AccountController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\AvatarController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Provider\EarningsController;
use App\Http\Controllers\Api\Provider\PaymentMethodController;
use App\Http\Controllers\Api\Provider\VerificationDocumentController;
use App\Http\Controllers\Api\Public\AvailabilityController;
use App\Http\Controllers\Api\Public\BarangayController;
use App\Http\Controllers\Api\Public\ProviderController;
use App\Http\Controllers\Api\Public\ServiceCategoryController;
use App\Http\Controllers\Api\Public\ServiceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
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

Route::get('/barangays', [BarangayController::class, 'index'])
    ->name('api.barangays.index');

Route::get('/service-categories', [ServiceCategoryController::class, 'index'])
    ->name('api.service-categories.index');

Route::get('/services', [ServiceController::class, 'index'])
    ->name('api.services.index');

Route::get('/services/{service}', [ServiceController::class, 'show'])
    ->name('api.services.show');

Route::get('/providers', [ProviderController::class, 'index'])
    ->name('api.providers.index');

Route::get('/providers/{providerProfile}', [ProviderController::class, 'show'])
    ->name('api.providers.show');

Route::get('/providers/{providerProfile}/availability', [AvailabilityController::class, 'show'])
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

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:auth')
        ->name('password.email');

    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
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

Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/auth/email/verification-notification', [EmailVerificationController::class, 'resend'])
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
        Route::patch('/profile', [ProfileController::class, 'update'])->name('update');
        Route::post('/avatar', [AvatarController::class, 'store'])->name('avatar.store');
        Route::get('/reviews', [ReviewController::class, 'myReviews'])->name('reviews.index');
        Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
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
        Route::post('/verification/documents', [VerificationDocumentController::class, 'store'])->name('verification.store');

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
        Route::delete('/services/{service}', [App\Http\Controllers\Api\Provider\ServiceController::class, 'destroy'])->name('services.destroy');
        Route::post('/services/{service}/restore', [App\Http\Controllers\Api\Provider\ServiceController::class, 'restore'])->withTrashed()->name('services.restore');

        Route::get('/availability/rules', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'rules'])->name('availability.rules.index');
        Route::put('/availability/rules', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'updateRules'])->name('availability.rules.update');
        Route::get('/availability/exceptions', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'exceptions'])->name('availability.exceptions.index');
        Route::post('/availability/exceptions', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'storeException'])->name('availability.exceptions.store');
        Route::patch('/availability/exceptions/{exception}', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'updateException'])->name('availability.exceptions.update');
        Route::delete('/availability/exceptions/{exception}', [App\Http\Controllers\Api\Provider\AvailabilityController::class, 'destroyException'])->name('availability.exceptions.destroy');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::patch('/payment-methods/{method}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::patch('/payment-methods/{method}/toggle', [PaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');
        Route::get('/payment-methods/{method}', [PaymentMethodController::class, 'show'])->name('payment-methods.show');
        Route::delete('/payment-methods/{method}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

        Route::get('/reviews', [ReviewController::class, 'providerReviews'])->name('reviews.index');

        Route::get('/earnings/summary', [EarningsController::class, 'summary'])->name('earnings.summary');
        Route::get('/earnings/over-time', [EarningsController::class, 'overTime'])->name('earnings.over-time');
    });

    // -----------------------------------------------------------------------
    // Bookings - shared between clients and providers, scoped by BookingPolicy
    // -----------------------------------------------------------------------
    Route::prefix('bookings')->name('api.bookings.')->middleware('role:client,provider,admin')->group(function () {
        Route::post('/', [BookingController::class, 'store'])
            ->middleware('throttle:writes')
            ->name('store');
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
        Route::patch('/{booking}', [BookingController::class, 'update'])->name('update');
        Route::delete('/{booking}', [BookingController::class, 'destroy'])->name('destroy');
        Route::post('/{booking}/restore', [BookingController::class, 'restore'])->withTrashed()->name('restore');
        Route::get('/{booking}/location', [BookingController::class, 'location'])->name('location');
        Route::post('/{booking}/accept', [BookingController::class, 'accept'])->name('accept');
        Route::post('/{booking}/reject', [BookingController::class, 'reject'])->name('reject');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/status', [BookingController::class, 'updateStatus'])->name('status');

        Route::get('/{booking}/payment', [PaymentController::class, 'show'])->name('payment.show');
        Route::post('/{booking}/payment/proof', [PaymentController::class, 'submitProof'])
            ->middleware('throttle:uploads')
            ->name('payment.proof');

        Route::get('/{booking}/review', [ReviewController::class, 'forBooking'])->name('review.show');
        Route::post('/{booking}/review', [ReviewController::class, 'store'])
            ->middleware('throttle:writes')
            ->name('review.store');
    });

    // -----------------------------------------------------------------------
    // Reports - any signed-in client/provider may file one
    // -----------------------------------------------------------------------
    Route::post('/reports', [ReportController::class, 'store'])
        ->middleware(['role:client,provider', 'throttle:writes'])
        ->name('api.reports.store');
    Route::middleware('role:client,provider')->prefix('reports')->name('api.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
        Route::patch('/{report}', [ReportController::class, 'update'])->name('update');
        Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
    });

    // -----------------------------------------------------------------------
    // Reviews - provider acts on reviews of their own profile; the client
    // who wrote a review may edit or withdraw it (ReviewPolicy)
    // -----------------------------------------------------------------------
    Route::prefix('reviews')->name('api.reviews.')->group(function () {
        Route::middleware('role:provider')->group(function () {
            Route::post('/{review}/reply', [ReviewController::class, 'reply'])->name('reply');
            Route::delete('/{review}/reply', [ReviewController::class, 'removeReply'])->name('reply.destroy');
            Route::patch('/{review}/visibility', [ReviewController::class, 'setVisibility'])->name('visibility');
        });

        Route::middleware('role:client')->group(function () {
            Route::patch('/{review}', [ReviewController::class, 'update'])->name('update');
            Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
        });
    });

    // -----------------------------------------------------------------------
    // Payments - shared between clients, providers and admin, scoped by PaymentPolicy
    // -----------------------------------------------------------------------
    Route::prefix('payments')->name('api.payments.')->middleware('role:client,provider,admin')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/{payment}/verify', [PaymentController::class, 'verify'])->name('verify');
        Route::post('/{payment}/reject', [PaymentController::class, 'reject'])->name('reject');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
        Route::post('/{payment}/restore', [PaymentController::class, 'restore'])->withTrashed()->name('restore');
    });

    // -----------------------------------------------------------------------
    // Private file serving - every route here is policy-gated, never public
    // -----------------------------------------------------------------------
    Route::get('/files/payment-proof/{proof}', [FileController::class, 'paymentProof'])->name('api.files.payment-proof');
    Route::get('/files/payment-qr/{method}', [FileController::class, 'paymentQr'])->withTrashed()->name('api.files.payment-qr');

    // -----------------------------------------------------------------------
    // Messaging - shared between clients and providers, scoped by ConversationPolicy
    // -----------------------------------------------------------------------
    Route::middleware('role:client,provider')->group(function () {
        Route::prefix('conversations')->name('api.conversations.')->group(function () {
            Route::post('/', [ConversationController::class, 'store'])->name('store');
            Route::get('/', [ConversationController::class, 'index'])->name('index');
            Route::get('/{conversation}/messages', [ConversationController::class, 'messages'])->name('messages');
            Route::post('/{conversation}/messages', [ConversationController::class, 'sendMessage'])
                ->middleware('throttle:writes')
                ->name('messages.store');
            Route::patch('/{conversation}/messages/{message}', [ConversationController::class, 'updateMessage'])
                ->scopeBindings()
                ->name('messages.update');
            Route::delete('/{conversation}/messages/{message}', [ConversationController::class, 'destroyMessage'])
                ->scopeBindings()
                ->name('messages.destroy');
            Route::post('/{conversation}/read', [ConversationController::class, 'markRead'])->name('read');
        });

        Route::get('/messages/unread-count', [ConversationController::class, 'unreadCount'])
            ->name('api.messages.unread-count');
    });

    // -----------------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------------
    Route::prefix('admin')->name('api.admin.')->middleware('role:admin')->group(function () {
        Route::get('/ping', fn () => response()->json(['success' => true]));
        // Users - full CRUD, delete is soft (restore lives under /trash)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->withTrashed()->name('users.show');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/restore', [UserController::class, 'restore'])->withTrashed()->name('users.restore');
        Route::patch('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
        Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');

        // Verification routes
        Route::get('/verification/documents', [VerificationController::class, 'index'])->name('verification.index');
        Route::patch('/verification/documents/{document}/approve', [VerificationController::class, 'approve'])->name('verification.approve');
        Route::patch('/verification/documents/{document}/reject', [VerificationController::class, 'reject'])->name('verification.reject');

        // Service category management
        Route::get('/categories', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'toggle'])->name('categories.toggle');
        Route::get('/categories/{category}', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'show'])->withTrashed()->name('categories.show');
        Route::delete('/categories/{category}', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/{category}/restore', [App\Http\Controllers\Api\Admin\ServiceCategoryController::class, 'restore'])->withTrashed()->name('categories.restore');

        // Providers (oversight/drill-down)
        Route::get('/providers', [App\Http\Controllers\Api\Admin\ProviderController::class, 'index'])->name('providers.index');
        Route::get('/providers/{providerProfile}', [App\Http\Controllers\Api\Admin\ProviderController::class, 'show'])->withTrashed()->name('providers.show');
        Route::patch('/providers/{providerProfile}', [App\Http\Controllers\Api\Admin\ProviderController::class, 'update'])->name('providers.update');
        Route::delete('/providers/{providerProfile}', [App\Http\Controllers\Api\Admin\ProviderController::class, 'destroy'])->name('providers.destroy');
        Route::post('/providers/{providerProfile}/restore', [App\Http\Controllers\Api\Admin\ProviderController::class, 'restore'])->withTrashed()->name('providers.restore');

        // Services
        Route::get('/services', [App\Http\Controllers\Api\Admin\ServiceController::class, 'index'])->name('services.index');
        Route::patch('/services/{service}/toggle', [App\Http\Controllers\Api\Admin\ServiceController::class, 'toggle'])->name('services.toggle');
        Route::get('/services/{service}', [App\Http\Controllers\Api\Admin\ServiceController::class, 'show'])->withTrashed()->name('services.show');
        Route::patch('/services/{service}', [App\Http\Controllers\Api\Admin\ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [App\Http\Controllers\Api\Admin\ServiceController::class, 'destroy'])->name('services.destroy');
        Route::post('/services/{service}/restore', [App\Http\Controllers\Api\Admin\ServiceController::class, 'restore'])->withTrashed()->name('services.restore');

        // Barangays
        Route::get('/barangays', [App\Http\Controllers\Api\Admin\BarangayController::class, 'index'])->name('barangays.index');
        Route::post('/barangays', [App\Http\Controllers\Api\Admin\BarangayController::class, 'store'])->name('barangays.store');
        Route::patch('/barangays/{barangay}', [App\Http\Controllers\Api\Admin\BarangayController::class, 'update'])->name('barangays.update');
        Route::patch('/barangays/{barangay}/toggle', [App\Http\Controllers\Api\Admin\BarangayController::class, 'toggle'])->name('barangays.toggle');
        Route::get('/barangays/{barangay}', [App\Http\Controllers\Api\Admin\BarangayController::class, 'show'])->withTrashed()->name('barangays.show');
        Route::delete('/barangays/{barangay}', [App\Http\Controllers\Api\Admin\BarangayController::class, 'destroy'])->name('barangays.destroy');
        Route::post('/barangays/{barangay}/restore', [App\Http\Controllers\Api\Admin\BarangayController::class, 'restore'])->withTrashed()->name('barangays.restore');

        // Chat violations - per §9.3, never exposes the surrounding conversation
        Route::get('/chat-violations', [ChatViolationController::class, 'index'])->name('chat-violations.index');
        Route::get('/chat-violations/{violation}', [ChatViolationController::class, 'show'])->name('chat-violations.show');
        Route::post('/chat-violations/{violation}/warn', [ChatViolationController::class, 'warn'])->name('chat-violations.warn');
        Route::post('/chat-violations/{violation}/suspend', [ChatViolationController::class, 'suspend'])->name('chat-violations.suspend');
        Route::post('/chat-violations/{violation}/dismiss', [ChatViolationController::class, 'dismiss'])->name('chat-violations.dismiss');
        Route::delete('/chat-violations/{violation}', [ChatViolationController::class, 'destroy'])->name('chat-violations.destroy');
        Route::post('/chat-violations/{violation}/restore', [ChatViolationController::class, 'restore'])->withTrashed()->name('chat-violations.restore');

        // Reports
        Route::get('/reports', [App\Http\Controllers\Api\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [App\Http\Controllers\Api\Admin\ReportController::class, 'show'])->withTrashed()->name('reports.show');
        Route::post('/reports/{report}/resolve', [App\Http\Controllers\Api\Admin\ReportController::class, 'resolve'])->name('reports.resolve');
        Route::delete('/reports/{report}', [App\Http\Controllers\Api\Admin\ReportController::class, 'destroy'])->name('reports.destroy');
        Route::post('/reports/{report}/restore', [App\Http\Controllers\Api\Admin\ReportController::class, 'restore'])->withTrashed()->name('reports.restore');

        // Audit logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/summary', [AnalyticsController::class, 'summary'])->name('summary');
            Route::get('/bookings-over-time', [AnalyticsController::class, 'bookingsOverTime'])->name('bookings-over-time');
            Route::get('/earnings-over-time', [AnalyticsController::class, 'earningsOverTime'])->name('earnings-over-time');
            Route::get('/bookings-by-category', [AnalyticsController::class, 'bookingsByCategory'])->name('bookings-by-category');
            Route::get('/top-providers', [AnalyticsController::class, 'topProviders'])->name('top-providers');
            Route::get('/payment-summary', [AnalyticsController::class, 'paymentSummary'])->name('payment-summary');
        });
    });
});

// ---------------------------------------------------------------------------
// Fallback - keeps unknown API paths inside the JSON envelope
// ---------------------------------------------------------------------------

Route::fallback(fn () => ApiResponse::error('The requested endpoint does not exist.', [], 404));
