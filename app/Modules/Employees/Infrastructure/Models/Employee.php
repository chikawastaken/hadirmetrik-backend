<?php

namespace App\Modules\Employees\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'employee_number',
    'name',
    'work_email',
    'phone',
    'position',
    'division',
    'is_active',
])]
class Employee extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
