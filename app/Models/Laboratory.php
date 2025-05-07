<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laboratory extends Model
{
    protected $fillable = [
        'name',
        'location',
        'description'
    ];

    // Relationships
    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    // Scopes
    public function scopeWithAvailableResources($query)
    {
        return $query->whereHas('resources', function ($q) {
            $q->where('status', 'available');
        });
    }

    public function scopeWithFaultyEquipment($query)
    {
        return $query->whereHas('equipment', function ($q) {
            $q->where('status', 'faulty');
        });
    }

    public function scopeWithMaintenanceTasks($query)
    {
        return $query->whereHas('equipment', function ($q) {
            $q->where('status', 'maintenance');
        });
    }
} 