<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
        'assigned_to',
        'created_by'
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class);
    }

    public function dependentTasks()
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }

    public function canBeCompleted()
    {
        $incompleteDependencies = $this->dependencies()
            ->whereHas('dependsOnTask', function ($query) {
                $query->where('status', '!=', 'completed');
            })
            ->exists();

        return !$incompleteDependencies;
    }
}
