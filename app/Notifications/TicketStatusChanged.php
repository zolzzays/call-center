<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Илгээгчид дуудлага хаагдсан эсвэл хойшлогдсоныг мэйлээр мэдэгдэнэ */
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
        $ticket = $this->ticket->loadMissing('assignee');
        $closed = $ticket->isClosed();

        $mail = (new MailMessage)
            ->subject("№{$ticket->number} — ".($closed ? 'шийдвэрлэгдэж хаагдлаа' : 'хойшлогдлоо'))
            ->greeting("Сайн байна уу, {$notifiable->name}!")
            ->line($closed
                ? "Таны илгээсэн №{$ticket->number} {$ticket->typeLabel()} шийдвэрлэгдэж хаагдлаа."
                : "Таны илгээсэн №{$ticket->number} {$ticket->typeLabel()} хойшлогдлоо.");

        if ($closed) {
            $mail->line('**Шийдвэрлэсэн байдал:** '.Str::limit((string) $ticket->resolution, 1000));
        } else {
            $reason = $ticket->activities()->where('action', 'postponed')->latest('id')->value('note');
            $mail->line('**Шинэ хугацаа:** '.$ticket->postponed_until?->format('Y-m-d H:i'))
                ->line('**Шалтгаан:** '.Str::limit((string) $reason, 1000));
        }

        return $mail
            ->line('**Гүйцэтгэгч:** '.($ticket->assignee?->name ?? '—'))
            ->action('Дуудлагыг харах', route('tickets.show', $ticket))
            ->salutation('Диспетчерийн үндэсний төв');
    }
}
