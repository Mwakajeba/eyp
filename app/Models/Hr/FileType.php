<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileType extends Model
{
    protected $table = 'hr_file_types';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'allowed_extensions',
        'max_file_size',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'allowed_extensions' => 'array',
        'max_file_size' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'file_type_id');
    }

    public function getAllowedExtensionsStringAttribute(): string
    {
        return $this->allowed_extensions ? implode(', ', $this->allowed_extensions) : '';
    }

    public function getMaxFileSizeHumanAttribute(): string
    {
        if (!$this->max_file_size) {
            return 'No limit';
        }

        $bytes = $this->max_file_size * 1024; // Convert KB to bytes
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
