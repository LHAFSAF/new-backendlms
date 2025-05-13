<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordLink extends Notification
{
    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = "http://localhost:3000/reset-password/confirm?token={$this->token}&email={$this->email}";

        return (new MailMessage)
            ->subject('Réinitialisation du mot de passe')
            ->line('Vous recevez cet email car vous avez demandé une réinitialisation de mot de passe.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Si vous n’avez pas demandé cette réinitialisation, ignorez ce message.');
    }
}
