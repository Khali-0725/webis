<?php

namespace App\Enums;

/**
 * How a service is priced.
 *
 * fixed   - one price for the whole job (`price` holds it)
 * hourly  - price per hour (`price` holds the rate)
 * quote   - provider quotes after inspecting the request (`price` is null;
 *           `min_price`/`max_price` may suggest a range)
 */
enum PricingType: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
    case Quote = 'quote';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed',
            self::Hourly => 'Hourly',
            self::Quote => 'Quoted on request',
        };
    }

    /**
     * Whether a search filter on price can match this service.
     */
    public function isFilterableByPrice(): bool
    {
        return $this !== self::Quote;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
