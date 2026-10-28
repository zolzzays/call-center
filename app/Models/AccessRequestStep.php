<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// МАБ хүсэлтийн явц: илгээсэн, шат бүрийн батлах/татгалзах, эрх олгосон
class AccessRequestStep extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'submitted' => 'Илгээсэн',
        'approved' => 'Зөвшөөрсөн',
        'rejected' => 'Татгалзсан',
        'skipped' => 'Алгассан',
        'granted' => 'Системийн инженер эрх үүсгэж олгосон',
        'reviewed' => 'МАБ инженер хянасан',
        'reminded' => 'Нууц үг солих сануулга',
        'revoked' => 'Эрх цуцалсан',
        'expired' => 'Хугацаа дууссан',
    ];

    protected $fillable = ['user_id', 'stage', 'action', 'note'];

    public function accessRequest(): BelongsTo
    {
        return $this->belongsTo(AccessRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        $action = self::ACTIONS[$this->action] ?? $this->action;

        return $this->stage ? AccessRequest::STAGES[$this->stage].' · '.$action : $action;
    }
}
