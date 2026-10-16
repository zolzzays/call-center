<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Гүйцэтгэгчид шинэ дуудлага хуваарилагдсаныг мэйлээр мэдэгдэнэ */
class TicketAssigned extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->ticket->loadMissing(['user.department', 'repairType', 'repairCategory']);

        $category = $ticket->repairType?->name
            .($ticket->repairCategory ? ' / '.$ticket->repairCategory->name : '');

        return (new MailMessage)
            ->subject("[{$ticket->urgencyLabel()}] №{$ticket->number} — {$ticket->typeLabel()}")
            ->greeting("Сайн байна уу, {$notifiable->name}!")
            ->line('Танд шинэ дуудлага хуваарилагдлаа.')
            ->line("**Дугаар:** №{$ticket->number}")
            ->line("**Төрөл:** {$ticket->typeLabel()}")
            ->line("**Ангилал:** {$category}")
            ->line("**Яаралтай байдал:** {$ticket->urgencyLabel()}")
            ->line('**Хүссэн хугацаа:** '.($ticket->due_at?->format('Y-m-d H:i') ?? 'Заагаагүй'))
            ->line('**Илгээгч:** '.$ticket->user?->name.($ticket->user?->department ? ' ('.$ticket->user->department->name.')' : ''))
            ->line('**Тайлбар:** '.\Illuminate\Support\Str::limit($ticket->description, 500))
            ->action('Дуудлагыг харах', route('tickets.show', $ticket))
            ->salutation('Диспетчерийн үндэсний төв');
    }
}
