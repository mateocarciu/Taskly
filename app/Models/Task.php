<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'column_id',
        'column_updated_at',
        'time_spent_in_columns',
        'order',
        'title',
        'description',
        'due_date',
        'created_by',
        'assigned_to',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'column_updated_at' => 'datetime',
            'due_date' => 'datetime',
            'time_spent_in_columns' => 'array',
        ];
    }

    /**
     * Get the team that owns the task.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the column that owns the task.
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    /**
     * Get the comments for the task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    /**
     * Get the activity events for the task.
     */
    public function events(): HasMany
    {
        return $this->hasMany(TaskEvent::class)->orderBy('created_at');
    }
}
