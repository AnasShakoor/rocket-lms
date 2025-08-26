<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationRule extends Model
{
    protected $fillable = [
        'target_type',
        'enrollment_offset_days',
        'completion_offset_days',
        'inter_course_gap_days',
        'course_order',
        'status',
        'created_by'
    ];

    protected $casts = [
        'course_order' => 'array',
        'enrollment_offset_days' => 'integer',
        'completion_offset_days' => 'integer',
        'inter_course_gap_days' => 'integer',
        'status' => 'string'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SimulationLog::class, 'rule_id');
    }

    public function getTargetAttribute()
    {
        switch ($this->target_type) {
            case 'course':
                return \App\Models\Api\Webinar::find($this->target_id);
            case 'student':
                return \App\User::find($this->target_id);
            case 'bundle':
                return Bundle::find($this->target_id);
            default:
                return null;
        }
    }

    public function getTargetNameAttribute()
    {
        $target = $this->target;
        if (!$target) return 'Unknown';
        
        switch ($this->target_type) {
            case 'course':
                return $target->title ?? 'Unknown Course';
            case 'student':
                return $target->name ?? 'Unknown Student';
            case 'bundle':
                return $target->name ?? 'Unknown Bundle';
            default:
                return 'Unknown';
        }
    }
}
