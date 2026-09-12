<?php

namespace App\Enums;

/**
 * The lifecycle of a booking.
 *
 * The legal transitions are an explicit, closed map on this enum - the
 * booking state machine (Phase 5) is the only writer of `bookings.status`
 * and refuses anything not listed here. CONFIRMED from the brief's suggested
 * state list is deliberately absent: it is indistinguishable from ACCEPTED
 * and would create a second, unenforceable path to IN_PROGRESS.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Disputed = 'disputed';

    /**
     * Statuses that reserve a provider's time slot.
     *
     * @return array<int, self>
     */
    public static function slotBlocking(): array
    {
        return [self::Pending, self::Accepted, self::InProgress];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Disputed => 'Disputed',
        };
    }

    /**
     * Transitions that may originate from this status.
     *
     * @return array<int, self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Accepted, self::Rejected, self::Cancelled],
            self::Accepted => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Disputed],
            self::Completed => [self::Disputed],
            self::Rejected, self::Cancelled, self::Disputed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /**
     * Statuses a review may be written after.
     */
    public function isReviewable(): bool
    {
        return $this === self::Completed;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
