<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Points the reset link at the React SPA's reset-password screen instead of
 * a Laravel Blade route, which does not exist in this API-only application.
 */
class ResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return config('webis.frontend_url')
            .'/reset-password?token='.$this->token
            .'&email='.urlencode($notifiable->getEmailForPasswordReset());
    }
}
