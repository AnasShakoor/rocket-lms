<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationRule extends Model
{
    protected $fillable = [
        'target_type',
        'target_id',
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
        if (!$this->target_id) return null;

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
        if (!$target) return 'Not Set';

        switch ($this->target_type) {
            case 'course':
                return $target->title ?? 'Unknown Course';
            case 'student':
                return $target->name ?? 'Unknown Student';
            case 'bundle':
                return $target->title ?? 'Unknown Bundle';
            default:
                return 'Unknown';
        }
    }

    public function getTargetDescriptionAttribute()
    {
        if (!$this->target_id) return 'No target selected';

        switch ($this->target_type) {
            case 'course':
                return 'Single course simulation';
            case 'student':
                return 'Student-specific simulation';
            case 'bundle':
                return 'Bundle simulation with sequential courses';
            default:
                return 'Unknown target type';
        }
    }

    public function getSimulationSummaryAttribute()
    {
        $summary = "Simulate {$this->target_type} completions";

        if ($this->enrollment_offset_days < 0) {
            $summary .= " with enrollment " . abs($this->enrollment_offset_days) . " days before purchase";
        } else {
            $summary .= " with enrollment " . $this->enrollment_offset_days . " days after purchase";
        }

        $summary .= ", completion in " . $this->completion_offset_days . " days";

        if ($this->inter_course_gap_days > 0) {
            $summary .= ", with " . $this->inter_course_gap_days . " day gap between courses";
        }

        return $summary;
    }
}
