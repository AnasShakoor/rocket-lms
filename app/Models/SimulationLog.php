<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationLog extends Model
{
    protected $fillable = [
        'rule_id',
        'user_id',
        'course_id',
        'purchase_date',
        'fake_enroll_date',
        'fake_completion_date',
        'status',
        'notes',
        'triggered_by_admin_id'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'fake_enroll_date' => 'date',
        'fake_completion_date' => 'date'
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SimulationRule::class, 'rule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Api\Webinar::class, 'course_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'triggered_by_admin_id');
    }
}

