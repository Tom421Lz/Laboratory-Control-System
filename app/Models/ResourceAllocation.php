<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceAllocation extends Model
{
    protected $fillable = [
        'resource_id',
        'user_id',
        'allocation_date',
        'return_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'allocation_date' => 'datetime',
        'return_date' => 'datetime'
    ];

    // Relationships
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->where('allocation_date', '<', now()->subDays(7));
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForResource($query, $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    // Accessors & Mutators
    public function getDurationAttribute()
    {
        if (!$this->return_date) {
            return null;
        }
        
        return $this->allocation_date->diffInDays($this->return_date);
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'active' && 
               $this->allocation_date->addDays(7)->isPast();
    }
} 