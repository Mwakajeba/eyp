<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SalaryStructureTemplate extends Model
{
    use LogsActivity;

    protected $table = 'hr_salary_structure_templates';

    protected $fillable = [
        'company_id',
        'template_name',
        'template_code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function templateComponents(): HasMany
    {
        return $this->hasMany(SalaryStructureTemplateComponent::class, 'template_id');
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(
            SalaryComponent::class,
            'hr_salary_structure_template_components',
            'template_id',
            'component_id'
        )->withPivot('amount', 'percentage', 'notes', 'display_order')
          ->withTimestamps()
          ->orderByPivot('display_order');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Apply template to an employee
     */
    public function applyToEmployee($employeeId, $effectiveDate = null, $endDate = null): array
    {
        $effectiveDate = $effectiveDate ?? now();
        $created = [];

        // Load template components if not already loaded
        if (!$this->relationLoaded('templateComponents')) {
            $this->load('templateComponents.component');
        }

        foreach ($this->templateComponents as $templateComponent) {
            $component = $templateComponent->component;
            
            if ($component) {
                EmployeeSalaryStructure::create([
                    'employee_id' => $employeeId,
                    'component_id' => $component->id,
                    'amount' => $templateComponent->amount,
                    'percentage' => $templateComponent->percentage,
                    'effective_date' => $effectiveDate,
                    'end_date' => $endDate,
                    'notes' => $templateComponent->notes,
                ]);

                $created[] = $component->component_name;
            }
        }

        return $created;
    }
}
