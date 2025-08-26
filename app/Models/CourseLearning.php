<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLearning extends Model
{
    protected $table = 'course_learning';
    
    protected $fillable = [
        'user_id',
        'webinar_id',
        'status',
        'progress',
        'enrolled_at',
        'started_at',
        'completed_at',
        'notes'
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Api\Webinar::class, 'webinar_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('webinar_id', $courseId);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now()
        ]);
    }

    public function markAsInProgress()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }

    public function updateProgress($progress)
    {
        $this->update([
            'progress' => max(0, min(100, $progress)),
            'status' => $progress >= 100 ? 'completed' : 'in_progress'
        ]);

        if ($progress >= 100 && !$this->completed_at) {
            $this->update(['completed_at' => now()]);
        }
    }
}
