<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your WEBIS password was changed')
            ->greeting('Password changed successfully')
            ->line('The password for your WEBIS account was changed successfully.')
            ->line('You can now sign in using your new password.')
            ->action('Sign in to WEBIS', rtrim(config('webis.frontend_url'), '/').'/login')
            ->line('If you did not make this change, use Forgot Password on the sign-in page to secure your account.');
    }
}
