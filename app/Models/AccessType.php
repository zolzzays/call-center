<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// МАБ-ын хандах эрхийн хүсэлтийн эрхийн төрөл (Унших, Унших засах, Админ г.м.)
class AccessType extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(AccessRequest::class);
    }
}
