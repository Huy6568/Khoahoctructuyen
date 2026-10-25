<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{
    public function __construct(
        protected LessonService $lessonService
    ) {
    }

    public function show(int $lesson): JsonResponse
    {
        $data = $this->lessonService->getLessonDetail($lesson);

        return response()->json([
            'data' => [
                'lesson' => new LessonResource($data['lesson']),
                'course' => new CourseResource($data['course']),
            ],
        ]);
    }
}
