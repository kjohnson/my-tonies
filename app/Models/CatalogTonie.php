<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A Tonie from the us.tonies.com store catalog.
 */
#[Fillable(['sku', 'skus', 'handle', 'title', 'category', 'series', 'ages', 'price', 'image', 'available', 'owned', 'published_at'])]
class CatalogTonie extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skus' => 'array',
            'ages' => 'array',
            'price' => 'float',
            'available' => 'boolean',
            'owned' => 'boolean',
            'published_at' => 'date',
            'wishlisted_at' => 'datetime',
        ];
    }

    /**
     * Tonies on the wishlist that we still don't own.
     */
    public function scopeWishlist(Builder $query): void
    {
        $query->whereNotNull('wishlisted_at')->where('owned', false);
    }

    public function url(): string
    {
        return config('tonies.store_url').'/products/'.$this->handle;
    }

    public function note(): ?string
    {
        return config('tonies.notes')[$this->sku] ?? null;
    }

    /**
     * Shopify CDN image resized for cards.
     */
    public function thumbnail(int $width = 400): ?string
    {
        if (! $this->image) {
            return null;
        }

        return $this->image.(str_contains($this->image, '?') ? '&' : '?').'width='.$width;
    }

    /**
     * Series and ages, e.g. "PAW Patrol · Ages 3-4".
     */
    public function meta(): string
    {
        return collect([
            $this->series !== $this->title ? $this->series : null,
            $this->ages ? 'Ages '.implode(', ', $this->ages) : null,
        ])->filter()->implode(' · ') ?: $this->category;
    }
}
