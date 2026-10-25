<?php

namespace App\Services;

use App\Repositories\LessonRepository;

class LessonService
{
    public function __construct(
        protected LessonRepository $lessonRepository
    ) {
    }

    public function getLessonDetail(int $lessonId): array
    {
        $lesson = $this->lessonRepository->findLessonWithCourseById($lessonId);

        return [
            'lesson' => $lesson,
            'course' => $lesson->section->course,
        ];
    }
}
