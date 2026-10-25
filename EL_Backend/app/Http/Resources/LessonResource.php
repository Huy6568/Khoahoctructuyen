<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'content_type' => $this->content_type,
            'video_url' => $this->video_url,
            'article_content' => $this->article_content,
            'order_index' => $this->order_index,
            'is_completed' => (bool) ($this->is_completed ?? false),
            'completed' => (bool) ($this->is_completed ?? false),
        ];
    }
}
