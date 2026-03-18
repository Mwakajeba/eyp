<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LeaveAttachment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'leave_request_id',
        'path',
        'original_name',
        'type',
        'size_kb',
        'mime_type',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    /**
     * Get the full URL of the attachment
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        if ($this->size_kb < 1024) {
            return $this->size_kb . ' KB';
        }

        return round($this->size_kb / 1024, 2) . ' MB';
    }

    /**
     * Delete the file from storage when the model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if (Storage::exists($attachment->path)) {
                Storage::delete($attachment->path);
            }
        });
    }
}

