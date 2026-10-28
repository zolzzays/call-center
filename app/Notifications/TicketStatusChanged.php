<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Илгээгчид дуудлага хаагдсан, хойшлогдсон эсвэл буцаагдсаныг мэйлээр мэдэгдэнэ */
class TicketStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->ticket->loadMissing(['assignee', 'coAssignee', 'handler']);
        $closed = $ticket->isClosed();
        $returned = $ticket->isReturned();

        $what = match (true) {
            $closed => 'шийдвэрлэгдэж хаагдлаа',
            $returned => 'тодруулга авахаар танд буцаагдлаа',
            default => 'хойшлогдлоо',
        };

        $mail = (new MailMessage)
            ->subject("№{$ticket->number} — {$what}")
            ->greeting("Сайн байна уу, {$notifiable->name}!")
            ->line("Таны илгээсэн №{$ticket->number} {$ticket->typeLabel()} {$what}.");

        if ($closed) {
            $mail->line('**Шийдвэрлэсэн байдал:** '.Str::limit((string) $ticket->resolution, 1000));
        } elseif ($returned) {
            $reason = $ticket->activities()->where('action', 'returned')->latest('id')->value('note');
            $mail->line('**Шалтгаан:** '.Str::limit((string) $reason, 1000))
                ->line('Хүсэлтийн хуудсанд орж тодруулгаа бичээд дахин илгээнэ үү.');
        } else {
            $reason = $ticket->activities()->where('action', 'postponed')->latest('id')->value('note');
            $mail->line('**Шинэ хугацаа:** '.$ticket->postponed_until?->format('Y-m-d H:i'))
                ->line('**Шалтгаан:** '.Str::limit((string) $reason, 1000));
        }

        return $mail
            ->line('**Гүйцэтгэгч:** '.($ticket->assigneeDisplay() ?: '—'))
            ->action('Дуудлагыг харах', route('tickets.show', $ticket))
            ->salutation('Диспетчерийн үндэсний төв');
    }
}
