<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Програм хангамжийн системийн модуль. parent_id хоосон бол бүлэг (жишээ нь «Шуурхай ажиллагаа»).
class SystemModule extends Model
{
    protected $fillable = ['repair_category_id', 'parent_id', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(RepairCategory::class, 'repair_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
