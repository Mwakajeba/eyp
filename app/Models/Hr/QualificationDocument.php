<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualificationDocument extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_qualification_documents';

    protected $fillable = [
        'qualification_id',
        'document_name',
        'document_type',
        'is_required',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }
}
