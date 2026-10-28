<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    /** МАБ-ын хандах эрхийн хүсэлтийг батлах үүрэг */
    public const ACCESS_ROLES = [
        'head' => 'Албаны дарга (өөрийн хэлтсийн хүсэлтийг батална)',
        'security' => 'МАБ инженер (хянаж, эрх олгоно)',
        'chief' => 'Ерөнхий диспетчер (эцэслэн батална)',
        'system' => 'Системийн инженер (эрх үүсгэж, ID олгоно)',
    ];

    protected $fillable = ['department_id', 'name', 'access_role', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getAccessRoleLabelAttribute(): ?string
    {
        return $this->access_role ? explode(' (', self::ACCESS_ROLES[$this->access_role] ?? $this->access_role)[0] : null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
