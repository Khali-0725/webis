<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderViolation extends Model
{
    use HasFactory;

    protected $fillable = ['report_id', 'provider_profile_id', 'issued_by', 'penalty_points', 'reason'];

    protected function casts(): array
    {
        return ['penalty_points' => 'integer'];
    }

    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function providerProfile(): BelongsTo { return $this->belongsTo(ProviderProfile::class); }
    public function issuedBy(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
}
