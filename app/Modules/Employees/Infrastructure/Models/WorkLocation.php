<?php

namespace App\Modules\Employees\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'address',
    'latitude',
    'longitude',
    'geofence_radius_m',
    'timezone',
    'is_active',
])]
class WorkLocation extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius_m' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
