<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;


class CourseController extends Controller
{
    public function __construct(
        protected CourseService $courseService
    ) {
    }

    public function home(): JsonResponse
    {
        $courses = $this->courseService->getHomeCourses();

        return response()->json([
            'data' => CourseResource::collection($courses),
        ]);
    }

    public function myLearning(Request $request): JsonResponse
    {
        $enrollments = $this->courseService->getMyLearningEnrollments(
            auth()->id(),
            $request->only(['search', 'sort', 'per_page'])
        );

        return response()->json([
            'data' => EnrollmentResource::collection($enrollments->getCollection()),
            'meta' => $this->paginationMeta($enrollments),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->courseService->getCourseIndexData($request->only(['search', 'categories']));
        $courses = $data['courses'];
        $categories = $data['categories'];

        return response()->json([
            'data' => [
                'courses' => CourseResource::collection($courses->getCollection()),
                'categories' => CategoryResource::collection($categories),
            ],
            'meta' => $this->paginationMeta($courses),
        ]);
    }

    public function show($id)
    {
        $data = $this->courseService->getCourseShowDataById((int) $id, auth('sanctum')->id());
        $course = $data['course'];
        $isEnrolled = $data['isEnrolled'];

        $userId = auth('sanctum')->id();
        if ($course->status !== 'published') {
            // Nếu không đăng nhập, hoặc đăng nhập nhưng không phải là người tạo ra khóa học này
            if (!$userId || $userId !== $course->instructor_id) {
                return response()->json(['message' => 'Khóa học này chưa được xuất bản.'], 403);
            }
        }

        return response()->json([
            'data' => [
                'course' => $course,
                'is_enrolled' => $isEnrolled // Cờ này cực kỳ quan trọng để Vue nhận diện
            ]
        ]);
    }

    public function reviews(Request $request, Course $course): JsonResponse
    {
        $perPage = max(1, min(50, $request->integer('per_page', 10)));

        $reviews = $course->reviews()
            ->with(['user:id,fullname,avatar'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $reviews->getCollection()->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'user' => [
                        'id' => $review->user?->id,
                        'fullname' => $review->user?->fullname,
                        'avatar' => $review->user?->avatar,
                    ],
                    'created_at' => optional($review->created_at)?->toISOString(),
                    'updated_at' => optional($review->updated_at)?->toISOString(),
                ];
            })->values(),
            'meta' => $this->paginationMeta($reviews),
        ]);
    }

    public function learning(Course $course): JsonResponse
    {
        abort_unless(auth()->check(), 403);
        $data = $this->courseService->getCourseLearningData($course, auth()->id());
        $course = $data['course'];
        $lesson = $data['lesson'];
        $completedLessonIds = $data['completedLessonIds'] ?? [];

        if (!$lesson) {
            return response()->json([
                'message' => 'Khóa học này chưa có nội dung.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'course' => new CourseResource($course),
                'lesson' => new LessonResource($lesson),
                'completed_lesson_ids' => $completedLessonIds,
            ],
        ]);
    }

    public function instructorIndex(Request $request): JsonResponse
    {
        $courses = $this->courseService->getInstructorCoursesData($request->only(['search', 'sort']), auth()->id());

        return response()->json([
            'data' => CourseResource::collection($courses->getCollection()),
            'meta' => $this->paginationMeta($courses),
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $course = $this->courseService->getCourseForEdit($id);

        return $this->builder($course);
    }

    public function builder(Course $course = null): JsonResponse
    {
        $data = $this->courseService->getBuilderData($course, auth()->id());
        $categories = $data['categories'];
        $draft = $data['draft'];
        $isEdit = $data['isEdit'];
        $course = $data['course'];

        return response()->json([
            'data' => [
                'categories' => CategoryResource::collection($categories),
                'draft' => $draft,
                'is_edit' => $isEdit,
                'course' => $course ? new CourseResource($course) : null,
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->courseService->getCourseIndexData([])['categories'];

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function saveBuilder(Request $request, Course $course = null): JsonResponse
    {
        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'objectives'      => ['nullable'],
            'requirements'    => ['nullable'],
            'category_id'     => ['required', 'exists:categories,id'],
            'pricing_model'   => ['required', 'in:free,paid'],
            'price'           => ['nullable', 'numeric', 'min:0'],
            'thumbnail_url'   => ['nullable', 'string'],
            'video_preview_url' => ['nullable', 'string'],
        ]);

        $result = $this->courseService->saveBuilder($validated, $course, auth()->id());

        return response()->json([
            'message' => 'Builder data saved.',
            'data' => [
                'next_step' => $result['next_step'],
                'is_edit' => $result['is_edit'],
                'course' => isset($result['course']) ? new CourseResource($result['course']) : null,
                'draft' => $result['draft'] ?? null,
            ],
        ]);
    }

    public function storeFromInstructorCourses(Request $request): JsonResponse
    {
        if ($request->exists('curriculum_data')) {
            return $this->store($request);
        }

        return $this->saveBuilder($request);
    }

    public function curriculum(Course $course = null): JsonResponse
    {
        $data = $this->courseService->getCurriculumData($course, auth()->id());

        return response()->json([
            'data' => [
                'is_edit' => $data['is_edit'],
                'course' => $data['course'] ? new CourseResource($data['course']) : null,
                'curriculum_template' => $data['curriculum_template'] ?? [],
            ],
        ]);
    }

    public function learn($course_id, $lesson_id = null): JsonResponse
    {
        abort_unless(auth()->check(), 403);
        $data = $this->courseService->getLearnDataByIds((int) $course_id, $lesson_id ? (int) $lesson_id : null, auth()->id());
        $course = $data['course'];
        $lesson = $data['lesson'];
        $completedLessonIds = $data['completedLessonIds'] ?? [];

        if (!$lesson) {
            return response()->json([
                'message' => 'Khóa học này chưa có nội dung.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'course' => new CourseResource($course),
                'lesson' => new LessonResource($lesson),
                'completed_lesson_ids' => $completedLessonIds,
            ],
        ]);
    }

    public function store(Request $request, Course $course = null): JsonResponse
    {
        $isEdit = (bool) $course;

        $curriculumData = $request->input('curriculum_data');
        if (is_string($curriculumData)) {
            $curriculumData = json_decode($curriculumData, true);
        }

        if (!is_array($curriculumData)) {
            throw ValidationException::withMessages([
                'curriculum_data' => ['The curriculum data field must be an array.'],
            ]);
        }

        $validated = $request->validate([
            'title'           => [$course ? 'nullable' : 'required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'objectives'      => ['nullable'],
            'requirements'    => ['nullable'],
            'category_id'     => [$course ? 'nullable' : 'required', 'exists:categories,id'],
            'pricing_model'   => ['nullable', 'in:free,paid'],
            'price'           => ['nullable', 'numeric', 'min:0'],
            'thumbnail_url'   => ['nullable', 'string'],
            'video_preview_url' => ['nullable', 'string'],
            'curriculum_data' => ['required'],
        ]);

        $builderPayload = [
            'title' => $validated['title'] ?? $course?->title,
            'description' => $validated['description'] ?? $course?->description,
            'objectives' => $validated['objectives'] ?? $course?->objectives ?? [],
            'requirements' => $validated['requirements'] ?? $course?->requirements ?? [],
            'category_id' => $validated['category_id'] ?? $course?->category_id,
            'pricing_model' => $validated['pricing_model'] ?? (($course && $course->price > 0) ? 'paid' : 'free'),
            'price' => $validated['price'] ?? $course?->price ?? 0,
            'thumbnail_url' => $validated['thumbnail_url'] ?? $course?->thumbnail_url,
            'video_preview_url' => $validated['video_preview_url'] ?? $course?->video_preview_url,
        ];

        $course = $this->courseService->storeCourseWithCurriculum(
            $builderPayload,
            $curriculumData,
            $course,
            auth()->id()
        );

        return response()->json([
            'message' => $isEdit ? 'Khóa học đã được cập nhật thành công!' : 'Khóa học đã được tạo thành công!',
            'data' => new CourseResource($course),
        ], $isEdit ? 200 : 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $course = $this->courseService->updateCourseInfo((int) $id, $request->all(), auth()->id());

        return response()->json([
            'message' => 'Khóa học đã được cập nhật thành công!',
            'data' => new CourseResource($course->fresh(['instructor'])),
        ]);
    }

    public function destroy(Course $course): JsonResponse
    {
        $deleted = $this->courseService->destroyCourse($course, auth()->id());

        if (!$deleted) {
            return response()->json([
                'message' => 'Không thể xóa khóa học đã có học viên đăng ký.',
            ], 422);
        }

        return response()->json([
            'message' => 'Khóa học đã được xóa.',
        ]);
    }

    public function toggleVisibility(Course $course): JsonResponse
    {
        $status = $this->courseService->toggleCourseVisibility($course, auth()->id());

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công.',
            'data' => [
                'status' => $status,
            ],
        ]);
    }

    public function storeReview(Request $request, Course $course): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->courseService->storeReview($course, $validated, auth()->id());

        return response()->json([
            'message' => 'Đánh giá khóa học đã được lưu.',
        ]);
    }

    public function myReview(Course $course): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $review = $this->courseService->getUserReview($course, auth()->id());

        return response()->json([
            'data' => $review ? [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'created_at' => optional($review->created_at)?->toISOString(),
                'updated_at' => optional($review->updated_at)?->toISOString(),
            ] : null,
        ]);
    }

    public function updateReview(Request $request, Course $course): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->courseService->storeReview($course, $validated, auth()->id());

        return response()->json([
            'message' => 'Đánh giá khóa học đã được cập nhật.',
        ]);
    }

    public function deleteReview(Course $course): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $deleted = $this->courseService->deleteUserReview($course, auth()->id());

        if (!$deleted) {
            return response()->json([
                'message' => 'Không tìm thấy đánh giá để xóa.',
            ], 404);
        }

        return response()->json([
            'message' => 'Đánh giá khóa học đã được xóa.',
        ]);
    }

    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
