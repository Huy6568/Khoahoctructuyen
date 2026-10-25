<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Section;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lessons extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'section_id',
        'title',
        'content_type',
        'video_url',
        'article_content',
        'order_index',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
