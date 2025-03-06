<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait Publishable
{
    public function initializePublishable(): void
    {
        $this->casts['is_published'] = 'boolean';
        $this->casts['published_at'] = 'datetime';
    }

    public function getIsPublishedAttribute(): bool
    {
        if (is_null($this->published_at)) {
            return false;
        }

        return $this->published_at->isPast();
    }

    public function setIsPublishedAttribute(bool $isPublished): void
    {
        if ($this->is_published === $isPublished) {
            return;
        }
        if (! $isPublished) {
            $this->attributes['published_at'] = null;
            return;
        }

        $this->attributes['published_at'] = Carbon::now();
    }

    public function scopePublished($query): Builder
    {
        return $query->where('published_at', '<=', Carbon::now());
    }
}
