<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingLocationResource;
use App\Http\Resources\BookingResource;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\BookingStateMachine;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Not audience-namespaced: a client and a provider act on the same
 * `bookings` resource, scoped entirely by BookingPolicy and by who the
 * caller is - see routes/api.php for the shared `role:client,provider,admin`
 * group this sits under.
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingStateMachine $stateMachine,
    ) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create($request->user(), $request->validated());

        return ApiResponse::created(new BookingResource($booking), 'Booking request sent.');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::query()->with(['service.category', 'providerProfile.user', 'client']);

        // Only admin gets the trash view - a client/provider never sees a
        // row that was deleted from under them.
        if ($user->isAdmin()) {
            TrashFilter::apply($query, $request);
        }

        if ($user->isProvider()) {
            $profile = $user->providerProfile()->firstOrFail();
            $query->forProvider($profile->id);
        } elseif ($user->isClient()) {
            $query->forClient($user->id);
        }

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(BookingStatus::values())],
        ]);

        if (! empty($validated['status'])) {
            $query->withStatus(BookingStatus::from($validated['status']));
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated(
            $query->orderByDesc('scheduled_date')->orderByDesc('scheduled_start_time')->paginate($perPage),
            BookingResource::class
        );
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load(['service.category', 'providerProfile.user', 'client', 'statusHistories.changedByUser']);

        $data = (new BookingResource($booking))->resolve();
        $location = $request->user()->can('viewLocation', $booking)
            ? $booking->location()->with('barangay')->first()
            : null;
        $data['location'] = $location ? (new BookingLocationResource($location))->resolve() : null;

        return ApiResponse::ok($data);
    }

    public function location(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('viewLocation', $booking);

        if ($request->user()->isAdmin()) {
            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'booking.location.viewed',
                'auditable_type' => Booking::class,
                'auditable_id' => $booking->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $location = $booking->location()->with('barangay')->firstOrFail();

        return ApiResponse::ok(new BookingLocationResource($location));
    }

    public function accept(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('accept', $booking);

        $updated = $this->stateMachine->transition($booking, BookingStatus::Accepted, $request->user());

        return $this->respond($updated, 'Booking accepted.');
    }

    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('reject', $booking);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $updated = $this->stateMachine->transition($booking, BookingStatus::Rejected, $request->user(), $validated['reason'] ?? null);

        return $this->respond($updated, 'Booking rejected.');
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $updated = $this->stateMachine->transition($booking, BookingStatus::Cancelled, $request->user(), $validated['reason'] ?? null);

        return $this->respond($updated, 'Booking cancelled.');
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('updateStatus', $booking);

        $validated = $request->validate([
            'to' => ['required', Rule::in([BookingStatus::InProgress->value, BookingStatus::Completed->value, BookingStatus::Disputed->value])],
        ]);

        $updated = $this->stateMachine->transition($booking, BookingStatus::from($validated['to']), $request->user());

        return $this->respond($updated, 'Booking status updated.');
    }

    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $updated = $this->bookingService->update($booking, $request->validated());

        return $this->respond($updated, 'Booking updated.');
    }

    /**
     * Soft delete - see BookingPolicy::delete for who may. The payment,
     * status history and location rows stay attached for the audit trail.
     */
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('delete', $booking);

        $booking->delete();

        if ($request->user()->isAdmin()) {
            AuditLogger::record($request->user(), 'booking.deleted', $booking, [], $request);
        }

        return ApiResponse::noContent('Booking moved to trash.');
    }

    public function restore(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('restore', $booking);

        if (! $booking->trashed()) {
            throw DomainException::conflict('This booking is not deleted.');
        }

        $booking->restore();

        AuditLogger::record($request->user(), 'booking.restored', $booking, [], $request);

        return $this->respond($booking, 'Booking restored.');
    }

    private function respond(Booking $booking, string $message): JsonResponse
    {
        return ApiResponse::ok(
            new BookingResource($booking->load(['service.category', 'providerProfile.user', 'client'])),
            $message
        );
    }
}
