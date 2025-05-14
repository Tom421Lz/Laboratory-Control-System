<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $fillable = [
        'name',
        'laboratory_id',
        'serial_number',
        'status',
        'purchase_date',
        'warranty_expiry'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date'
    ];

    // Relationships
    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function faultReports()
    {
        return $this->hasMany(FaultReport::class);
    }

    public function maintenanceTasks()
    {
        return $this->hasMany(MaintenanceTask::class);
    }

    // Scopes
    public function scopeOperational($query)
    {
        return $query->where('status', 'operational');
    }

    public function scopeFaulty($query)
    {
        return $query->where('status', 'faulty');
    }

    public function scopeInMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeDisposed($query)
    {
        return $query->where('status', 'disposed');
    }

    public function scopeWithActiveMaintenance($query)
    {
        return $query->whereHas('maintenanceTasks', function ($q) {
            $q->whereIn('status', ['pending', 'in_progress']);
        });
    }

    public function scopeWithWarrantyExpiring($query, $days = 30)
    {
        return $query->whereNotNull('warranty_expiry')
            ->where('warranty_expiry', '<=', now()->addDays($days))
            ->where('warranty_expiry', '>', now());
    }
} 