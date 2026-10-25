<?php

namespace App\Repositories;

use App\Models\Lessons;

class LessonRepository
{
    public function findLessonWithCourseById(int $lessonId): Lessons
    {
        return Lessons::with(['section.course.instructor'])->findOrFail($lessonId);
    }
}
