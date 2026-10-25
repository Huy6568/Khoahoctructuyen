<?php

namespace App\Http\Controllers;

use App\Http\Resources\EnrollmentResource;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        protected CourseService $courseService
    ) {
    }

    public function index(): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $enrollments = $this->courseService->getMyLearningEnrollments(auth()->id());

        return response()->json([
            'data' => EnrollmentResource::collection($enrollments->getCollection()),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
            ],
        ]);
    }
}
