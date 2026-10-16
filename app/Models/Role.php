<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'admin';

    public const USER = 'user';

    public const ASSIGNEE = 'assignee';

    protected $fillable = ['name', 'slug', 'description'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
