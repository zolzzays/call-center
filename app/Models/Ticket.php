<?php

namespace App\Models;

use App\Notifications\TicketAssigned;
use App\Notifications\TicketResubmitted;
use App\Notifications\TicketStatusChanged;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    public const TYPES = [
        'repair' => 'Засварын дуудлага',
        'change' => 'Нэмэлт, өөрчлөлтийн хүсэлт',
    ];

    public const URGENCIES = [
        'normal' => 'Энгийн',
        'urgent' => 'Яаралтай',
        'critical' => 'Нэн яаралтай',
    ];

    public const STATUSES = [
        'new' => 'Шинэ',
        'assigned' => 'Хуваарилсан',
        'postponed' => 'Хойшлуулсан',
        'returned' => 'Буцаасан',
        'closed' => 'Хаасан',
    ];

    /** Шийдвэрлэгдээгүй байгаа төлөвүүд */
    public const OPEN_STATUSES = ['new', 'assigned', 'postponed', 'returned'];

    protected $fillable = [
        'user_id', 'type', 'repair_type_id', 'repair_category_id',
        'description', 'urgency', 'due_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'assigned_at' => 'datetime',
            'postponed_until' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** Админ бүгдийг, гүйцэтгэгч өөрт ирснийг, бусад нь өөрийн илгээснийг харна */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isAdmin() => $query,
            $user->isAssignee() => $query->where(fn (Builder $q) => $q
                ->where('assignee_id', $user->id)
                ->orWhere('co_assignee_id', $user->id)),
            default => $query->where('user_id', $user->id),
        };
    }

    /**
     * Дугаарыг "ОООО-00001" хэлбэрээр жил бүр 1-ээс эхлүүлэн олгож хадгална.
     */
    public static function createWithNumber(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $prefix = now()->format('Y').'-';

            $last = static::where('number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            $ticket = new static($attributes);
            $ticket->number = $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $ticket->save();

            return $ticket;
        });
    }

    /**
     * Гүйцэтгэгчид (хамтран гүйцэтгэгчтэй бол хоёуланд нь) хуваарилж мэйлээр мэдэгдэнэ.
     * Мэйл илгээж чадаагүй ч хуваарилалт хадгалагдана.
     *
     * @return bool бүх гүйцэтгэгчид мэйл илгээгдсэн эсэх
     */
    public function assignTo(User $assignee, ?User $actor = null, ?User $coAssignee = null): bool
    {
        if ($coAssignee?->is($assignee)) {
            $coAssignee = null;
        }

        DB::transaction(function () use ($assignee, $actor, $coAssignee) {
            $this->forceFill([
                'assignee_id' => $assignee->id,
                'co_assignee_id' => $coAssignee?->id,
                // Шинээр хуваарилахад өмнөх гүйцэтгэгчийн үйлдэл хамааралгүй болно
                'handled_by_id' => null,
                'assigned_at' => now(),
                // Хойшлуулсан дуудлагыг өөр хүнд шилжүүлсэн ч хойшлуулсан хэвээр үлдэнэ
                'status' => $this->status === 'postponed' ? 'postponed' : 'assigned',
            ])->save();

            $this->activities()->create([
                'user_id' => $actor?->id,
                'action' => 'assigned',
                'assignee_id' => $assignee->id,
                'note' => $coAssignee ? 'Хамтран гүйцэтгэгч: '.$coAssignee->name : null,
            ]);
        });

        $this->setRelation('assignee', $assignee);
        $this->setRelation('coAssignee', $coAssignee);
        $this->setRelation('handler', null);

        $mailed = self::notifySafely($assignee, new TicketAssigned($this));
        if ($coAssignee) {
            $mailed = self::notifySafely($coAssignee, new TicketAssigned($this)) && $mailed;
        }

        return $mailed;
    }

    /** Гүйцэтгэгч шийдвэрлэсэн тайлбартай хаана. Илгээгчид мэдэгдэнэ. */
    public function close(User $actor, string $resolution): void
    {
        DB::transaction(function () use ($actor, $resolution) {
            $this->forceFill([
                'status' => 'closed',
                'resolution' => $resolution,
                'closed_at' => now(),
                'postponed_until' => null,
            ] + $this->handlerFields($actor, always: true))->save();

            $this->activities()->create(['user_id' => $actor->id, 'action' => 'closed', 'note' => $resolution]);
        });

        $this->notifyReporter($actor);
    }

    /** Шалтгаан, шинэ хугацаатай хойшлуулна. Илгээгчид мэдэгдэнэ. */
    public function postpone(User $actor, CarbonInterface $until, string $reason): void
    {
        DB::transaction(function () use ($actor, $until, $reason) {
            $this->forceFill(['status' => 'postponed', 'postponed_until' => $until] + $this->handlerFields($actor))->save();

            $this->activities()->create([
                'user_id' => $actor->id,
                'action' => 'postponed',
                'note' => $reason,
                'postponed_until' => $until,
            ]);
        });

        $this->notifyReporter($actor);
    }

    /** Гүйцэтгэгч тодруулга авахаар илгээгчид шалтгаантай буцаана. Илгээгчид мэдэгдэнэ. */
    public function returnToReporter(User $actor, string $reason): void
    {
        DB::transaction(function () use ($actor, $reason) {
            $this->forceFill(['status' => 'returned', 'postponed_until' => null] + $this->handlerFields($actor))->save();
            $this->activities()->create(['user_id' => $actor->id, 'action' => 'returned', 'note' => $reason]);
        });

        $this->notifyReporter($actor);
    }

    /**
     * Илгээгч тодруулга нэмж дахин илгээнэ; хүсэлт өмнөх гүйцэтгэгчид рүүгээ буцна.
     *
     * @return bool бүх гүйцэтгэгчид мэйл илгээгдсэн эсэх
     */
    public function resubmit(User $actor, string $note): bool
    {
        DB::transaction(function () use ($actor, $note) {
            $this->forceFill(['status' => $this->assignee_id ? 'assigned' : 'new'])->save();
            $this->activities()->create(['user_id' => $actor->id, 'action' => 'resubmitted', 'note' => $note]);
        });

        $this->loadMissing(['assignee', 'coAssignee']);
        $mailed = true;
        foreach (array_filter([$this->assignee, $this->coAssignee]) as $assignee) {
            $mailed = self::notifySafely($assignee, new TicketResubmitted($this, $note)) && $mailed;
        }

        return $mailed;
    }

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    /** Буцаасан хүсэлтийг илгээгч л тодруулж дахин илгээнэ */
    public function canBeResubmittedBy(User $user): bool
    {
        return $this->isReturned() && $this->user_id === $user->id;
    }

    /**
     * Үйлдлийг хуваарилагдсан гүйцэтгэгч хийсэн бол түүнийг тэмдэглэнэ (хойшлуулах, буцаахыг админ хийвэл өөрчлөхгүй).
     * Хаахад ($always) гүйцэтгэлийг бичиж хаасан хүн хэн ч байсан тэмдэглэнэ.
     */
    private function handlerFields(User $actor, bool $always = false): array
    {
        if (! $always && ! $this->isAssignedTo($actor)) {
            return [];
        }

        $this->setRelation('handler', $actor);

        return ['handled_by_id' => $actor->id];
    }

    private function notifyReporter(User $actor): void
    {
        $reporter = $this->user;

        if ($reporter && ! $reporter->is($actor)) {
            self::notifySafely($reporter, new TicketStatusChanged($this));
        }
    }

    /** Мэйл илгээж чадаагүй ч үйлдлийг зогсоохгүй */
    private static function notifySafely(User $user, Notification $notification): bool
    {
        if (blank($user->email)) {
            return false;
        }

        try {
            $user->notify($notification);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function coAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_assignee_id');
    }

    /** Хүсэлтийг сүүлд хаасан, хойшлуулсан эсвэл буцаасан гүйцэтгэгч */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }

    /** Харуулах гүйцэтгэгч: үйлдэл хийсэн гүйцэтгэгч, эс бөгөөс хуваарилагдсан бүх гүйцэтгэгч */
    public function assigneeDisplay(): string
    {
        return $this->handler?->name ?? $this->assigneeNames();
    }

    /** Гүйцэтгэгчдийн нэр: "Бат, Дорж" (хуваарилаагүй бол хоосон) */
    public function assigneeNames(): string
    {
        return collect([$this->assignee?->name, $this->coAssignee?->name])->filter()->implode(', ');
    }

    /** Үндсэн эсвэл хамтран гүйцэтгэгч мөн эсэх */
    public function isAssignedTo(User $user): bool
    {
        return in_array($user->id, array_filter([$this->assignee_id, $this->co_assignee_id]), true);
    }

    public function repairType(): BelongsTo
    {
        return $this->belongsTo(RepairType::class);
    }

    public function repairCategory(): BelongsTo
    {
        return $this->belongsTo(RepairCategory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->orderBy('created_at')->orderBy('id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function urgencyLabel(): string
    {
        return self::URGENCIES[$this->urgency] ?? $this->urgency;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isVisibleTo(User $user): bool
    {
        return match (true) {
            $user->isAdmin() => true,
            $user->isAssignee() => $this->isAssignedTo($user),
            default => $this->user_id === $user->id,
        };
    }

    /** Хаах, хойшлуулах эрх: хуваарилагдсан гүйцэтгэгч, хамтран гүйцэтгэгч болон админ */
    public function canBeWorkedBy(User $user): bool
    {
        // Илгээгчид буцаасан хүсэлтийг тодруулж дахин илгээх хүртэл хаах, хойшлуулах боломжгүй
        return ! $this->isClosed()
            && ! $this->isReturned()
            && $this->assignee_id !== null
            && ($user->isAdmin() || $this->isAssignedTo($user));
    }
}
