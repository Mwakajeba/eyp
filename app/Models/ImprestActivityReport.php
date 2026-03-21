<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprestActivityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'imprest_request_id',
        'uploaded_by',
        'description',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(ImprestRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
