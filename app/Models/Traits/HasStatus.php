<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasStatus
{
    /**
     * Scope to filter by status
     */
    public function scopeWhereStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by multiple statuses
     */
    public function scopeWhereStatusIn(Builder $query, array $statuses): Builder
    {
        return $query->whereIn('status', $statuses);
    }

    /**
     * Check if status matches
     */
    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Check if status is in given array
     */
    public function hasStatusIn(array $statuses): bool
    {
        return in_array($this->status, $statuses);
    }

    /**
     * Update status
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }

    /**
     * Get status badge CSS class (override in model if needed)
     */
    public function getStatusBadgeClass(): string
    {
        return 'bg-gray-100 text-gray-800';
    }

    /**
     * Get status label (override in model if needed)
     */
    public function getStatusLabel(): string
    {
        return str_replace('_', ' ', ucwords($this->status, '_'));
    }
}
