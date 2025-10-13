<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Get searchable fields (override in model)
     */
    public function getSearchableFields(): array
    {
        return ['name'];
    }

    /**
     * Scope for searching across searchable fields
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $searchableFields = $this->getSearchableFields();

        return $query->where(function ($q) use ($search, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Scope for exact search
     */
    public function scopeSearchExact(Builder $query, string $field, string $value): Builder
    {
        return $query->where($field, $value);
    }

    /**
     * Scope for searching with specific field
     */
    public function scopeSearchByField(Builder $query, string $field, string $search): Builder
    {
        return $query->where($field, 'like', "%{$search}%");
    }
}
