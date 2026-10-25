<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'instructor_id' => $this->instructor_id,
            'objectives' => $this->objectives ?? [],
            'requirements' => $this->requirements ?? [],

            // 1. ĐÓNG GÓI GIÁ TIỀN (Khớp với course.price.raw và course.price.formatted bên Vue)
            'price' => [
                'raw' => (float) $this->price,
                'formatted' => number_format((float) $this->price, 0, ',', '.') . 'đ',
            ],

            'status' => $this->status,

            // 2. ĐỔI TÊN THUMBNAIL (Khớp với course.thumbnail bên Vue)
            'thumbnail' => $this->thumbnail_url ?? 'https://picsum.photos/640/360',
            'video_preview_url' => $this->video_preview_url,

            // 3. GOM NHÓM ĐÁNH GIÁ (Khớp với course.rating.average và course.rating.count)
            'rating' => [
                'average' => round((float) ($this->reviews_avg_rating ?? $this->average_rating ?? 0), 1),
                'count' => (int) ($this->reviews_count ?? 0),
            ],

            'enrollments_count' => (int) ($this->enrollments_count ?? 0),

            // 4. CHUẨN HÓA GIẢNG VIÊN (Sử dụng key 'name' thay vì 'fullname' cho Vue)
            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id' => $this->instructor->id,
                    'name' => $this->instructor->fullname ?? $this->instructor->name ?? 'Đang cập nhật...',
                    'email' => $this->instructor->email,
                ];
            }, [
                // Fallback mặc định nếu chưa eager load relationship 'instructor'
                'id' => $this->instructor_id,
                'name' => 'Đang cập nhật...',
            ]),

            // Các sections giữ nguyên để dùng cho trang Chi tiết khóa học
            'sections' => $this->whenLoaded('sections', function () {
                return $this->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'order_index' => $section->order_index,
                        'lessons' => $section->relationLoaded('lessons')
                            ? LessonResource::collection($section->lessons)
                            : [],
                    ];
                })->values();
            }),

            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}
