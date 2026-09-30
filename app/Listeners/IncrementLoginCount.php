<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Counts the sign-ins of each account, so the panel can tell the first access
 * from the following ones.
 */
class IncrementLoginCount
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->increment('login_count');
        }
    }
}
