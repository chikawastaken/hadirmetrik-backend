<?php

namespace App\Modules\Employees\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'currency',
    'base_salary',
    'transport_allowance',
    'meal_allowance',
    'effective_from',
    'effective_to',
    'created_by',
])]
class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';
    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
