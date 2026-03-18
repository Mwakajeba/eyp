<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureTemplateComponent extends Model
{
    use LogsActivity;

    protected $table = 'hr_salary_structure_template_components';

    protected $fillable = [
        'template_id',
        'component_id',
        'amount',
        'percentage',
        'notes',
        'display_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'display_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SalaryStructureTemplate::class, 'template_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }
}
