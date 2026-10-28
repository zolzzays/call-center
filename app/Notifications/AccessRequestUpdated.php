<?php

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * МАБ-ын хандах эрхийн хүсэлтийн мэйл.
 * Батлагчид: pending. Системийн инженерт: grant (эрх үүсгэх), disable (системээс хаах).
 * Хүсэгчид: approved, rejected, granted (ID, түр нууц үг, заавар), revoked, expired, password (нууц үг солих).
 */
class AccessRequestUpdated extends Notification
{
    use Queueable;

    /** @param  array{password?: ?string}  $extra  түр нууц үг зөвхөн мэйлд орно, хадгалагдахгүй */
    public function __construct(public AccessRequest $request, public string $event, public array $extra = []) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->request->loadMissing(['user.department', 'steps', 'modules.parent']);
        $last = $r->steps->last();

        [$subject, $intro] = match ($this->event) {
            'pending' => ['батлах хүсэлт ирлээ', 'Танд '.AccessRequest::STAGES[$r->stage()].'-ын шатанд батлах МАБ-ын хандах эрхийн хүсэлт ирлээ.'],
            'approved' => ['батлагдлаа', 'Таны хандах эрхийн хүсэлт бүх шатанд батлагдлаа. Системийн инженер эрхийг үүсгэж олгоно.'],
            'grant' => ['эрх үүсгэнэ үү', 'Батлагдсан хандах эрхийн хүсэлтийн дагуу эрх үүсгэж, ID олгоод системд тэмдэглэнэ үү.'],
            'rejected' => ['татгалзлаа', 'Таны хандах эрхийн хүсэлтийг татгалзлаа.'],
            'granted' => ['эрх олгогдлоо', 'Таны хүссэн хандах эрхийг үүсгэж олголоо.'],
            'revoked' => ['эрх цуцлагдлаа', 'Таны хандах эрхийг цуцаллаа.'],
            'expired' => ['эрхийн хугацаа дууслаа', 'Таны хандах эрхийн хугацаа дууссан тул цуцлагдлаа.'],
            'disable' => ['эрхийг системээс хаана уу', 'Дараах хандах эрх цуцлагдсан тул системээс хааж, идэвхгүй болгоно уу.'],
            'password' => ['нууц үгээ солино уу', 'МАБ-ын журмын дагуу 6 сар тутам нууц үгээ солих шаардлагатай. Дараах системийн нууц үгээ солино уу.'],
        };

        $mail = (new MailMessage)
            ->subject("{$r->number} — {$subject}")
            ->greeting("Сайн байна уу, {$notifiable->name}!")
            ->line($intro)
            ->line("**Дугаар:** {$r->number}")
            ->line('**Хүсэгч:** '.$r->user?->name.($r->user?->department ? ' ('.$r->user->department->name.')' : ''))
            ->line("**Хүсэлтийн төрөл:** {$r->requestTypeLabel()}")
            ->line("**Системийн нэр:** {$r->system}")
            ->lineIf($r->modules->isNotEmpty(), '**Модулиуд:** '.implode(', ', $r->moduleNames()))
            ->line("**Эрхийн төрөл:** {$r->accessTypeLabel()}")
            ->line("**Хугацаа:** {$r->periodLabel()}");

        if (in_array($this->event, ['pending', 'grant'], true)) {
            $mail->line('**Шалтгаан:** '.Str::limit($r->reason, 500));
        }

        if (in_array($this->event, ['granted', 'disable', 'password'], true) && $r->account_id) {
            $mail->line("**Хэрэглэгчийн нэр / ID:** {$r->account_id}");
        }

        if ($this->event === 'granted') {
            if (filled($this->extra['password'] ?? null)) {
                $mail->line("**Түр нууц үг:** {$this->extra['password']}")
                    ->line('Анх нэвтрэхдээ түр нууц үгээ заавал солино уу. Нууц үгээ бусдад дамжуулахгүй байна уу.');
            }
            if ($r->instructions) {
                $mail->line('**Заавар:** '.Str::limit($r->instructions, 2000));
            }
        }

        if (in_array($this->event, ['rejected', 'revoked', 'expired', 'disable'], true) && $last?->note) {
            $mail->line('**Шалтгаан:** '.Str::limit($last->note, 1000));
        }

        return $mail
            ->action('Хүсэлтийг харах', route('access-requests.show', $r))
            ->salutation('Диспетчерийн үндэсний төв');
    }
}
