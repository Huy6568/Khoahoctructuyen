<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Section;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    protected $fillable = [
        'instructor_id',
        'title',
        'category_id',
        'description',
        'objectives',
        'requirements',
        'price',
        'thumbnail_url',
        'video_preview_url',
        'status',
    ];

    protected $casts = [
        'objectives' => 'array',
        'requirements' => 'array',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lessons::class, Section::class);
    }

    protected function resolvedReviewCount(): int
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        return $this->reviews()->count();
    }

    protected function resolvedAverageRating(): float
    {
        if (array_key_exists('reviews_avg_rating', $this->attributes) && $this->attributes['reviews_avg_rating'] !== null) {
            return (float) $this->attributes['reviews_avg_rating'];
        }

        return (float) $this->reviews()->avg('rating');
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->resolvedAverageRating(), 1);
    }

    public function getRatingAttribute(): float
    {
        return $this->getAverageRatingAttribute();
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->resolvedReviewCount();
    }

    public function getReviewCountAttribute(): int
    {
        return $this->resolvedReviewCount();
    }

    public function getRatingCountAttribute(): int
    {
        return $this->resolvedReviewCount();
    }
}
