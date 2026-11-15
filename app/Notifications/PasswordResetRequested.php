<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetRequested extends Notification
{
    use Queueable;

    public function __construct(private readonly string $accountEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('طلب استعادة كلمة مرور')
            ->greeting('مرحباً')
            ->line('طلب صاحب الحساب التالي رابطاً لإعادة تعيين كلمة المرور:')
            ->line($this->accountEmail)
            ->line('أُرسل رابط إعادة التعيين إلى بريد صاحب الحساب فقط. لا تشارك كلمة المرور أو رمز إعادة التعيين مع أي شخص.');
    }
}
