<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPasswordBase
{
    public $token;

    public function __construct($token)
    {
        parent::__construct($token);
        $this->token = $token;
    }

    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Recupera tu contraseña - Minera Española S.A.C.')
            ->greeting('¡Hola!')
            ->line('Recibiste este correo porque solicitamos un restablecimiento de contraseña para tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line('Este enlace expirará en ' . config('auth.passwords.'.config('auth.defaults.passwords').'.expire') . ' minutos.')
            ->line('Si no solicitaste este cambio, no necesitas hacer nada más.')
            ->salutation('Saludos, Minera Española S.A.C.');
    }
}