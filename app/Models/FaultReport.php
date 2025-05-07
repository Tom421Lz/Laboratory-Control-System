<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaultReport extends Model
{
    protected $fillable = [
        'equipment_id',
        'reported_by',
        'severity',
        'description',
        'status'
    ];

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function maintenanceTasks()
    {
        return $this->hasMany(MaintenanceTask::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeOfSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeWithActiveMaintenance($query)
    {
        return $query->whereHas('maintenanceTasks', function ($q) {
            $q->whereIn('status', ['pending', 'in_progress']);
        });
    }
} 