<?php

namespace App\Models;

use App\Notifications\AccessRequestUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * МАБ-ын хандах эрхийн хүсэлт:
 * 1. Хүсэлт гаргах → 2. Нэгжийн удирдлага → 3. МАБ инженер → 4. Ерөнхий диспетчер
 * → 5–6. Системийн инженер эрх үүсгэж хэрэглэгчид олгоно → 7. МАБ инженер тогтмол хянаж, аудит хийнэ.
 * Батлагчийг албан тушаалын access_role-оор автоматаар олно.
 */
class AccessRequest extends Model
{
    /** Батлах шатууд, дарааллаар */
    public const STAGES = [
        'head' => 'Нэгжийн удирдлага',
        'security' => 'МАБ инженер',
        'chief' => 'Ерөнхий диспетчер',
    ];

    public const STATUSES = [
        'pending_head' => 'Нэгжийн удирдлага хянаж байна',
        'pending_security' => 'МАБ инженер хянаж байна',
        'pending_chief' => 'Ерөнхий диспетчер хянаж байна',
        'approved' => 'Батлагдсан · системийн инженер эрх үүсгэнэ',
        'granted' => 'Эрх олгосон · идэвхтэй',
        'rejected' => 'Татгалзсан',
        'revoked' => 'Цуцалсан',
        'expired' => 'Хугацаа дууссан',
    ];

    /** МАБ инженер эрх цуцлах шалтгаан */
    public const REVOKE_REASONS = [
        'inactive' => '60 хоног идэвхгүй',
        'left' => 'Ажлаас гарсан',
        'transferred' => 'Өөр алба, хэлтэс рүү шилжсэн',
        'not_needed' => 'Эрх шаардлагагүй болсон',
        'other' => 'Бусад',
    ];

    /** Идэвхгүй эрхийг хянах хугацаа (хоног), нууц үг солих сануулгын давтамж (сар) */
    public const REVIEW_DAYS = 60;

    public const PASSWORD_MONTHS = 6;

    protected $fillable = ['user_id', 'request_type_id', 'repair_category_id', 'system', 'access_type_id', 'starts_on', 'ends_on', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'granted_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'password_reminded_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AccessRequestStep::class)->orderBy('id');
    }

    /** Хүсэлтийг дугаар олгож бүртгээд эхний батлах шат руу илгээнэ */
    /** @param  list<int>  $moduleIds  хандах эрх хүссэн модулиуд */
    public static function submit(User $user, array $data, array $moduleIds = []): self
    {
        $request = DB::transaction(function () use ($user, $data, $moduleIds) {
            $prefix = 'МАБ-'.now()->format('Y').'-';
            $last = static::where('number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('number')->value('number');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            $request = new static($data + ['user_id' => $user->id]);
            $request->number = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $request->status = 'pending_head';
            $request->save();
            $request->modules()->sync($moduleIds);
            $request->steps()->create(['user_id' => $user->id, 'action' => 'submitted']);

            return $request;
        });

        $request->moveTo('head');

        return $request;
    }

    /** Одоогийн батлах шат (pending_* төлөвт), эс бөгөөс null */
    public function stage(): ?string
    {
        return str_starts_with($this->status, 'pending_') ? substr($this->status, 8) : null;
    }

    /**
     * Тухайн шатны батлагчид: албан тушаал нь тэр үүрэгтэй идэвхтэй хэрэглэгчид.
     * Албаны даргыг илгээгчийн алба, хэлтсээс хайна. Илгээгч өөрөө батлагч болохгүй.
     */
    public function approvers(?string $stage = null): Collection
    {
        $stage ??= $this->stage();
        if ($stage === null) {
            return new Collection;
        }
        if ($stage === 'head' && ! $this->user?->department_id) {
            return new Collection;
        }

        return User::query()
            ->where('is_active', true)
            ->where('id', '!=', $this->user_id)
            ->whereHas('position', fn (Builder $q) => $q->where('access_role', $stage))
            ->when($stage === 'head', fn (Builder $q) => $q->where('department_id', $this->user->department_id))
            ->orderBy('name')
            ->get();
    }

    /** Тухайн үүрэгтэй (security — МАБ инженер, system — системийн инженер) идэвхтэй хэрэглэгчид */
    public static function staff(string $role): Collection
    {
        return User::where('is_active', true)
            ->whereHas('position', fn (Builder $q) => $q->where('access_role', $role))
            ->orderBy('name')
            ->get();
    }

    public function canBeApprovedBy(User $user): bool
    {
        return $this->stage() !== null
            && $user->id !== $this->user_id
            && ($user->isAdmin() || $this->approvers()->contains('id', $user->id));
    }

    /** 5–6. Системийн инженер эрх үүсгэж олгоно */
    public function canBeGrantedBy(User $user): bool
    {
        return $this->status === 'approved'
            && ($user->isAdmin() || $user->position?->access_role === 'system');
    }

    /** 7. МАБ инженер идэвхтэй эрхийг хянаж, цуцална */
    public function canBeAuditedBy(User $user): bool
    {
        return $this->status === 'granted' && self::isAuditor($user);
    }

    public static function isAuditor(User $user): bool
    {
        return $user->isAdmin() || $user->position?->access_role === 'security';
    }

    /** Сүүлд хянаснаас (эсвэл олгосноос) хойш 60 хоног өнгөрсөн идэвхтэй эрх */
    public function needsReview(): bool
    {
        $since = $this->last_reviewed_at ?? $this->granted_at;

        return $this->status === 'granted' && $since && $since->lte(now()->subDays(self::REVIEW_DAYS));
    }

    /** Илгээгч, админ, МАБ/систем/Ерөнхий диспетчер, дарга нь зөвхөн өөрийн хэлтсийнхийг харна */
    public function isVisibleTo(User $user): bool
    {
        $role = $user->position?->access_role;

        return $user->isAdmin()
            || $this->user_id === $user->id
            || in_array($role, ['security', 'chief', 'system'], true)
            || ($role === 'head' && $user->department_id && $user->department_id === $this->user?->department_id);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $role = $user->position?->access_role;

        return match (true) {
            $user->isAdmin(), in_array($role, ['security', 'chief', 'system'], true) => $query,
            $role === 'head' && $user->department_id !== null => $query->where(fn (Builder $q) => $q
                ->where('user_id', $user->id)
                ->orWhereHas('user', fn (Builder $u) => $u->where('department_id', $user->department_id))),
            default => $query->where('user_id', $user->id),
        };
    }

    /** Тухайн хэрэглэгчийн батлах эсвэл эрх үүсгэх ёстой хүсэлтүүд */
    public function scopeAwaiting(Builder $query, User $user): Builder
    {
        $role = $user->position?->access_role;

        if ($user->isAdmin()) {
            return $query->whereIn('status', ['pending_head', 'pending_security', 'pending_chief', 'approved']);
        }

        return match ($role) {
            'security' => $query->where('status', 'pending_security')->where('user_id', '!=', $user->id),
            'chief' => $query->where('status', 'pending_chief')->where('user_id', '!=', $user->id),
            'system' => $query->where('status', 'approved'),
            'head' => $query->where('status', 'pending_head')->where('user_id', '!=', $user->id)
                ->whereHas('user', fn (Builder $u) => $u->where('department_id', $user->department_id)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function approve(User $actor, ?string $note = null): void
    {
        $stage = $this->stage();

        DB::transaction(fn () => $this->steps()->create([
            'user_id' => $actor->id, 'stage' => $stage, 'action' => 'approved', 'note' => $note,
        ]));

        $next = array_keys(self::STAGES)[array_search($stage, array_keys(self::STAGES), true) + 1] ?? null;
        $next ? $this->moveTo($next) : $this->finishApproval();
    }

    public function reject(User $actor, string $reason): void
    {
        DB::transaction(function () use ($actor, $reason) {
            $this->steps()->create(['user_id' => $actor->id, 'stage' => $this->stage(), 'action' => 'rejected', 'note' => $reason]);
            $this->forceFill(['status' => 'rejected'])->save();
        });

        $this->notify([$this->user], 'rejected');
    }

    /**
     * 5–6. Системийн инженер эрх үүсгэж ID олгоод хэрэглэгчид түр нууц үг, заавар илгээнэ.
     * Түр нууц үгийг зөвхөн мэйлээр дамжуулж, системд хадгалахгүй.
     */
    public function grant(User $actor, string $accountId, ?string $instructions = null, ?string $temporaryPassword = null): void
    {
        DB::transaction(function () use ($actor, $accountId, $instructions) {
            $this->forceFill([
                'status' => 'granted',
                'account_id' => $accountId,
                'instructions' => $instructions,
                'granted_at' => now(),
                'last_reviewed_at' => null,
                'password_reminded_at' => null,
            ])->save();
            $this->steps()->create(['user_id' => $actor->id, 'action' => 'granted', 'note' => 'ID: '.$accountId]);
        });

        $this->notify([$this->user], 'granted', ['password' => $temporaryPassword]);
    }

    /** 7. МАБ инженер эрх идэвхтэй ашиглагдаж байгааг шалгасан гэж тэмдэглэнэ (60 хоногийн хяналт) */
    public function markReviewed(User $actor): void
    {
        DB::transaction(function () use ($actor) {
            $this->forceFill(['last_reviewed_at' => now()])->save();
            $this->steps()->create(['user_id' => $actor->id, 'action' => 'reviewed']);
        });
    }

    /**
     * 7. Эрхийг цуцална (МАБ инженер, эсвэл ажлаас гарсан/шилжсэн, хугацаа дууссан үед автоматаар).
     * Хэрэглэгч болон системийн инженерт (системээс хаахаар) мэдэгдэнэ.
     */
    public function revoke(?User $actor, string $reason, string $action = 'revoked'): void
    {
        DB::transaction(function () use ($actor, $reason, $action) {
            $this->forceFill([
                'status' => $action === 'expired' ? 'expired' : 'revoked',
                'revoked_at' => now(),
                'revoke_reason' => $reason,
            ])->save();
            $this->steps()->create(['user_id' => $actor?->id, 'action' => $action, 'note' => $reason]);
        });

        $this->notify([$this->user], $action);
        $this->notify(self::staff('system'), 'disable');
    }

    /** 7. 6 сар тутам нууц үг солих сануулга */
    public function remindPassword(): void
    {
        DB::transaction(function () {
            $this->forceFill(['password_reminded_at' => now()])->save();
            $this->steps()->create(['action' => 'reminded', 'note' => 'Нууц үг солих сануулга илгээсэн.']);
        });

        $this->notify([$this->user], 'password');
    }

    /** Шат руу шилжүүлж батлагчдад мэдэгдэнэ. Албаны дарга олдохгүй бол тэр шатыг алгасна. */
    private function moveTo(string $stage): void
    {
        $approvers = $this->approvers($stage);

        if ($stage === 'head' && $approvers->isEmpty()) {
            $this->steps()->create(['stage' => 'head', 'action' => 'skipped', 'note' => 'Илгээгчийн алба, хэлтэст дарга бүртгэлгүй тул алгассан.']);
            $this->moveTo('security');

            return;
        }

        $this->forceFill(['status' => 'pending_'.$stage])->save();
        $this->notify($approvers, 'pending');
    }

    /** Бүх шат батлагдсан: илгээгчид мэдэгдэж, системийн инженерт эрх үүсгүүлнэ */
    private function finishApproval(): void
    {
        $this->forceFill(['status' => 'approved'])->save();
        $this->notify([$this->user], 'approved');
        $this->notify(self::staff('system'), 'grant');
    }

    /** Мэйл илгээж чадаагүй ч үйлдлийг зогсоохгүй */
    private function notify(iterable $users, string $event, array $extra = []): void
    {
        foreach ($users as $user) {
            if (! $user || blank($user->email)) {
                continue;
            }
            try {
                $user->notify(new AccessRequestUpdated($this, $event, $extra));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Эрхийн төрөл (Тохиргоо → Эрхийн төрөл) */
    public function accessType(): BelongsTo
    {
        return $this->belongsTo(AccessType::class);
    }

    /** Систем (Тохиргоо → Засвар ангилал) */
    public function systemCategory(): BelongsTo
    {
        return $this->belongsTo(RepairCategory::class, 'repair_category_id');
    }

    /** Хандах эрх хүссэн модулиуд (Тохиргоо → Системийн модуль) */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(SystemModule::class, 'access_request_module')->orderBy('system_modules.id');
    }

    /** «Бүлэг › модуль» хэлбэрийн нэрс */
    public function moduleNames(): array
    {
        return $this->modules->map(fn (SystemModule $m) => ($m->parent ? $m->parent->name.' › ' : '').$m->name)->all();
    }

    /** Хүсэлтийн төрөл (Тохиргоо → Хүсэлтийн төрөл) */
    public function requestType(): BelongsTo
    {
        return $this->belongsTo(AccessRequestType::class, 'request_type_id');
    }

    public function requestTypeLabel(): string
    {
        return $this->requestType?->name ?? '—';
    }

    public function accessTypeLabel(): string
    {
        return $this->accessType?->name ?? '—';
    }

    public function periodLabel(): string
    {
        return $this->starts_on->format('Y-m-d').' — '.($this->ends_on?->format('Y-m-d') ?? 'Байнгын');
    }
}
