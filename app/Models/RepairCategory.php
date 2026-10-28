<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairCategory extends Model
{
    protected $fillable = ['repair_type_id', 'assignee_id', 'co_assignee_id', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(SystemModule::class);
    }

    public function repairType(): BelongsTo
    {
        return $this->belongsTo(RepairType::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    // Хамтран гүйцэтгэгч: ангиллын хүсэлтийг үндсэн гүйцэтгэгчтэй хамт хариуцна
    public function coAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_assignee_id');
    }
}
