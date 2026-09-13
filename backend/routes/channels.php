<?php

use Illuminate\Support\Facades\Broadcast;

// Each user may subscribe only to their own channel; every realtime push in
// the app (App\Support\Realtime) is addressed per-user, so this one rule is
// the entire authorization surface for broadcasting.
Broadcast::channel('App.Models.User.{id}', fn ($user, int $id) => (int) $user->id === $id);
