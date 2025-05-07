<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceTask extends Model
{
    protected $fillable = [
        'fault_report_id',
        'assigned_to',
        'priority',
        'status',
        'start_date',
        'completion_date',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date'
    ];

    // Relationships
    public function faultReport()
    {
        return $this->belongsTo(FaultReport::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOfPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress'])
            ->where('start_date', '<', now()->subDays(7));
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Accessors & Mutators
    public function getDurationAttribute()
    {
        if (!$this->completion_date) {
            return null;
        }
        
        return $this->start_date->diffInDays($this->completion_date);
    }

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'completed' && 
               $this->start_date->addDays(7)->isPast();
    }
} 