<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'purchase_price' => (float) $this->purchase_price,
            'purchased_at' => optional($this->purchased_at)?->toISOString(),
            'course' => $this->whenLoaded('course', function () {
                return new CourseResource($this->course);
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'fullname' => $this->user->fullname,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
