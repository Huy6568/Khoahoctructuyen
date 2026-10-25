<?php

namespace App\Repositories;

use App\Models\Course;
use App\Models\Enrollment;

class CheckoutRepository
{
    public function findCourseById(int $courseId): Course
    {
        return Course::findOrFail($courseId);
    }

    public function upsertEnrollment(int $userId, int $courseId, float $purchasePrice): Enrollment
    {
        return Enrollment::updateOrCreate(
            [
                'user_id' => $userId,
                'course_id' => $courseId,
            ],
            [
                'purchase_price' => $purchasePrice,
                'purchased_at' => now(),
            ]
        );
    }
}
