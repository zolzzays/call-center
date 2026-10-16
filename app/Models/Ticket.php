<?php

namespace App\Models;

use App\Notifications\TicketAssigned;
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
        'closed' => 'Хаасан',
    ];

    /** Шийдвэрлэгдээгүй байгаа төлөвүүд */
    public const OPEN_STATUSES = ['new', 'assigned', 'postponed'];

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
            $user->isAssignee() => $query->where('assignee_id', $user->id),
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
     * Гүйцэтгэгчид хуваарилж мэйлээр мэдэгдэнэ.
     * Мэйл илгээж чадаагүй ч хуваарилалт хадгалагдана.
     *
     * @return bool мэйл илгээгдсэн эсэх
     */
    public function assignTo(User $assignee, ?User $actor = null): bool
    {
        DB::transaction(function () use ($assignee, $actor) {
            $this->forceFill([
                'assignee_id' => $assignee->id,
                'assigned_at' => now(),
                // Хойшлуулсан дуудлагыг өөр хүнд шилжүүлсэн ч хойшлуулсан хэвээр үлдэнэ
                'status' => $this->status === 'postponed' ? 'postponed' : 'assigned',
            ])->save();

            $this->activities()->create([
                'user_id' => $actor?->id,
                'action' => 'assigned',
                'assignee_id' => $assignee->id,
            ]);
        });

        $this->setRelation('assignee', $assignee);

        return self::notifySafely($assignee, new TicketAssigned($this));
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
            ])->save();

            $this->activities()->create(['user_id' => $actor->id, 'action' => 'closed', 'note' => $resolution]);
        });

        $this->notifyReporter($actor);
    }

    /** Шалтгаан, шинэ хугацаатай хойшлуулна. Илгээгчид мэдэгдэнэ. */
    public function postpone(User $actor, CarbonInterface $until, string $reason): void
    {
        DB::transaction(function () use ($actor, $until, $reason) {
            $this->forceFill(['status' => 'postponed', 'postponed_until' => $until])->save();

            $this->activities()->create([
                'user_id' => $actor->id,
                'action' => 'postponed',
                'note' => $reason,
                'postponed_until' => $until,
            ]);
        });

        $this->notifyReporter($actor);
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
            $user->isAssignee() => $this->assignee_id === $user->id,
            default => $this->user_id === $user->id,
        };
    }

    /** Хаах, хойшлуулах эрх: хуваарилагдсан гүйцэтгэгч болон админ */
    public function canBeWorkedBy(User $user): bool
    {
        return ! $this->isClosed()
            && $this->assignee_id !== null
            && ($user->isAdmin() || $this->assignee_id === $user->id);
    }
}
