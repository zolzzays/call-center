<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// МАБ-ын хүсэлтийн төрөл (Шинэ хэрэглэгч үүсгэх, Хандах эрх өөрчлөх, Хандах эрх цуцлах г.м.)
class AccessRequestType extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(AccessRequest::class, 'request_type_id');
    }
}
