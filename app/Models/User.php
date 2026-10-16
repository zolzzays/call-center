<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role_id',
        'position_id',
        'department_id',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isAssignee(): bool
    {
        return $this->hasRole(Role::ASSIGNEE);
    }

    /** Идэвхтэй гүйцэтгэгчид. $includeId-г идэвхгүй болсон ч жагсаалтад үлдээнэ. */
    public function scopeAssignees(Builder $query, ?int $includeId = null): Builder
    {
        return $query->whereHas('role', fn ($q) => $q->where('slug', Role::ASSIGNEE))
            ->where(fn ($q) => $q->where('is_active', true)->when($includeId, fn ($q) => $q->orWhere('id', $includeId)));
    }

    /**
     * Дуудлагыг зөвхөн энгийн хэрэглэгч илгээнэ.
     * Админ хянаж, тохиргоог удирдана; гүйцэтгэгч өөрт ирснийг гүйцэтгэнэ.
 */
    public function canSubmitTickets(): bool
    {
        return ! $this->isAdmin() && ! $this->isAssignee();
    }
}
