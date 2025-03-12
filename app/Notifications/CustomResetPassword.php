<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends Notification
{
    public $token;
    public $role; // Add role property

    public function __construct($token, $role)
    {
        $this->token = $token;
        $this->role = $role;
    }
    /**
     * Specify which channels the notification will be sent through.
     */
    public function via($notifiable)
    {
        return ['mail']; // Ensure this method exists
    }

    /**
     * Build the mail notification.
     */
    public function toMail($notifiable)
    {
        $frontendUrl = env('APP_URL'); // React app URL
    
        // Map role to correct reset path
        $resetPaths = [
            'user' => 'worker/reset-password',
            'client' => 'client/reset-password',
            'admin' => 'admin/reset-password'
        ];

        // Default to 'user' if role is unknown
        $resetPath = $resetPaths[$this->role] ?? $resetPaths['user'];

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('You requested a password reset. Click the button below to reset your password.')
            ->action('Reset Password', "$frontendUrl/$resetPath?token={$this->token}&email=" . urlencode($notifiable->email))
            ->line('If you did not request this, please ignore this email.');
    }
    
}
