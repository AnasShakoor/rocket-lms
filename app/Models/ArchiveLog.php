<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveLog extends Model
{
    protected $fillable = [
        'table_name',
        'record_id',
        'record_data',
        'action',
        'admin_id',
        'archive_reason'
    ];

    protected $casts = [
        'record_data' => 'array'
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'admin_id');
    }

    public function scopeArchived($query)
    {
        return $query->where('action', 'archive');
    }

    public function scopeRestored($query)
    {
        return $query->where('action', 'restore');
    }
}

