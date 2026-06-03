<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Get the tags for the task.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    /**
     * Scope a query to filter tasks based on array parameters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        $search = $filters['search'] ?? null;
        $assigneeId = $filters['assignee_id'] ?? null;
        $tagIds = $filters['tag_ids'] ?? null;
        $dueDate = $filters['due_date'] ?? null;

        return $query
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($assigneeId, function ($query, $assigneeId) {
                if ($assigneeId === 'unassigned') {
                    $query->whereNull('assigned_to');
                } else {
                    $query->where('assigned_to', $assigneeId);
                }
            })
            ->when($tagIds, function ($query, $tagIds) {
                $query->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', (array) $tagIds);
                });
            })
            ->when($dueDate, function ($query, $dueDate) {
                if ($dueDate === 'overdue') {
                    $query->whereDate('due_date', '<', now()->toDateString());
                } elseif ($dueDate === 'today') {
                    $query->whereDate('due_date', '=', now()->toDateString());
                } elseif ($dueDate === 'week') {
                    $query->whereBetween('due_date', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString()
                    ]);
                } elseif ($dueDate === 'none') {
                    $query->whereNull('due_date');
                } else {
                    $query->whereDate('due_date', '=', $dueDate);
                }
            });
    }
}
