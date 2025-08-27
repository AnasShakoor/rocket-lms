<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRequest extends Model
{
    protected $table = 'certificate_requests';
    public $timestamps = false;
    protected $dateFormat = 'U';
    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'course_id',
        'course_type',
        'status',
        'admin_notes',
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function course()
    {
        if ($this->course_type === 'webinar') {
            return $this->belongsTo('App\Models\Webinar', 'course_id', 'id');
        } elseif ($this->course_type === 'bundle') {
            return $this->belongsTo('App\Models\Bundle', 'course_id', 'id');
        }
        return null;
    }

    public function getCourseTitleAttribute()
    {
        if ($this->course) {
            return $this->course->title;
        }
        return 'Unknown Course';
    }

    public function getUserNameAttribute()
    {
        if ($this->user) {
            return $this->user->full_name;
        }
        return 'Unknown User';
    }
}
