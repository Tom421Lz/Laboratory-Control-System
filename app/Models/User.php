<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'username',
        'password',
        'email',
        'role'
    ];

    protected $hidden = [
        'password'
    ];

    // Relationships
    public function faultReports()
    {
        return $this->hasMany(FaultReport::class, 'reported_by');
    }

    public function maintenanceTasks()
    {
        return $this->hasMany(MaintenanceTask::class, 'assigned_to');
    }

    public function resourceAllocations()
    {
        return $this->hasMany(ResourceAllocation::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    // Scopes
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeSuppliers($query)
    {
        return $query->where('role', 'supplier');
    }
} 