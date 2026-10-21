<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspensionAppeal extends Model
{
    protected $fillable = ['user_id', 'suspended_at', 'explanation'];

    protected function casts(): array
    {
        return ['suspended_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
