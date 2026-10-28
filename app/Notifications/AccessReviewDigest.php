<?php

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** МАБ инженерт: 60 хоног хянагдаагүй идэвхтэй эрхүүдийг шалгаж, идэвхгүйг цуцлах сануулга */
class AccessReviewDigest extends Notification
{
    use Queueable;

    /** @param  Collection<int, AccessRequest>  $requests */
    public function __construct(public Collection $requests) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('МАБ — '.$this->requests->count().' эрх '.AccessRequest::REVIEW_DAYS.'+ хоног хянагдаагүй')
            ->greeting("Сайн байна уу, {$notifiable->name}!")
            ->line('Дараах идэвхтэй хандах эрхүүд '.AccessRequest::REVIEW_DAYS.' хоногоос дээш хянагдаагүй байна. Идэвхгүй бол цуцалж, ашиглагдаж байвал «Хянасан» гэж тэмдэглэнэ үү.');

        foreach ($this->requests->take(30) as $r) {
            $mail->line("• {$r->number} — {$r->user?->name} · {$r->system} · ID: ".($r->account_id ?? '—'));
        }

        return $mail
            ->action('Хяналт, аудит', route('access-requests.audit'))
            ->salutation('Диспетчерийн үндэсний төв');
    }
}
