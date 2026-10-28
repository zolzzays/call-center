<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Буцаасан хүсэлтийг илгээгч тодруулж дахин илгээснийг гүйцэтгэгчид мэдэгдэнэ */
class TicketResubmitted extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public string $note) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->ticket->loadMissing('user');

        return (new MailMessage)
            ->subject("№{$ticket->number} — илгээгч тодруулж дахин илгээлээ")
            ->greeting("Сайн байна уу, {$notifiable->name}!")
            ->line("Таны буцаасан №{$ticket->number} {$ticket->typeLabel()}-г илгээгч тодруулж дахин илгээлээ.")
            ->line('**Илгээгч:** '.($ticket->user?->name ?? '—'))
            ->line('**Тодруулга:** '.Str::limit($this->note, 1000))
            ->action('Дуудлагыг харах', route('tickets.show', $ticket))
            ->salutation('Диспетчерийн үндэсний төв');
    }
}
