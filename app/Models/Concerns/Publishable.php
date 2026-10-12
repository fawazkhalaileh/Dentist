<?php

namespace App\Models\Concerns;

use App\Enums\PublicationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Draft/published content. Published = status published and published_at not
 * in the future, so posts can be scheduled.
 *
 * @property PublicationStatus $status
 * @property CarbonImmutable|null $published_at
 */
trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'status' => PublicationStatus::class,
            'published_at' => 'datetime',
        ]);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), PublicationStatus::Published->value)
            ->where(fn (Builder $q) => $q
                ->whereNull($this->qualifyColumn('published_at'))
                ->orWhere($this->qualifyColumn('published_at'), '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === PublicationStatus::Published
            && ($this->published_at === null || $this->published_at->lte(now()));
    }
}
