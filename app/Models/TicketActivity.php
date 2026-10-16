<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'assigned' => 'Гүйцэтгэгчид хуваарилсан',
        'postponed' => 'Хойшлуулсан',
        'closed' => 'Хаасан',
    ];

    protected $fillable = ['user_id', 'action', 'note', 'postponed_until', 'assignee_id'];

    protected function casts(): array
    {
        return ['postponed_until' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function label(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
