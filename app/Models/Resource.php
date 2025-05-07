<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = [
        'name',
        'type',
        'laboratory_id',
        'status',
        'description'
    ];

    // Relationships
    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function allocations()
    {
        return $this->hasMany(ResourceAllocation::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeInUse($query)
    {
        return $query->where('status', 'in_use');
    }

    public function scopeInMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeDisposed($query)
    {
        return $query->where('status', 'disposed');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInLaboratory($query, $laboratoryId)
    {
        return $query->where('laboratory_id', $laboratoryId);
    }
} 