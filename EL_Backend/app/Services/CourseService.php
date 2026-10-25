<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use App\Repositories\CourseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public function __construct(
        protected CourseRepository $courseRepository
    ) {
    }

    public function getHomeCourses(): Collection
    {
        return $this->courseRepository->getHomeCourses();
    }

    public function getMyLearningEnrollments(int $userId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 16)));

        return $this->courseRepository->getMyLearningEnrollments($userId, $filters, $perPage);
    }

    public function getCourseIndexData(array $filters): array
    {
        $query = $this->courseRepository->makePublicCoursesQuery();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('instructor', function ($instructorQuery) use ($search) {
                        $instructorQuery->where('fullname', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $categoryIds = collect($filters['categories'])
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        return [
            'courses' => $this->courseRepository->paginateCourses($query->latest()),
            'categories' => $this->courseRepository->getCategories(),
        ];
    }

    public function getCourseShowData(Course $course, ?int $userId): array
    {
        $course = $this->courseRepository->loadCourseDetail($course);
        $isEnrolled = $userId
            ? $this->courseRepository->isUserEnrolledInCourse($course->id, $userId)
            : false;

        return compact('course', 'isEnrolled');
    }

    public function getCourseShowDataById(int $courseId, ?int $userId): array
    {
        $course = $this->courseRepository->findCourseById($courseId);

        return $this->getCourseShowData($course, $userId);
    }

    public function getCourseLearningData(Course $course, int $userId): array
    {
        abort_unless($this->canUserViewCourseContent($course, $userId), 403);

        $course = $this->courseRepository->loadCourseForLearning($course);
        $lesson = $this->courseRepository->getFirstLesson($course);
        $completedLessonIds = $this->attachCompletionState($course, $lesson, $userId);

        return compact('course', 'lesson', 'completedLessonIds');
    }

    public function getInstructorCoursesData(array $filters, int $instructorId): LengthAwarePaginator
    {
        $query = $this->courseRepository->makeInstructorCoursesQuery($instructorId);

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        $sort = $filters['sort'] ?? 'newest';
        if ($sort === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        return $this->courseRepository->paginateCourses($query);
    }

    public function getCourseForEdit(int $courseId): Course
    {
        return $this->courseRepository->findCourseById($courseId);
    }

    public function getBuilderData(?Course $course, int $userId): array
    {
        $categories = $this->courseRepository->getCategories();

        if ($course) {
            abort_unless($userId === $course->instructor_id, 403);

            $course = $this->courseRepository->loadCourseForBuilderEdit($course);
            $draft = [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'objectives' => $course->objectives ?? [],
                'requirements' => $course->requirements ?? [],
                'category_id' => $course->category_id,
                'pricing_model' => $course->price > 0 ? 'paid' : 'free',
                'price' => $course->price,
            ];
            $isEdit = true;
        } else {
            $draft = [
                'title' => null,
                'description' => null,
                'objectives' => [],
                'requirements' => [],
                'category_id' => null,
                'pricing_model' => 'free',
                'price' => 0,
            ];
            $isEdit = false;
        }

        return compact('categories', 'draft', 'isEdit', 'course');
    }

    public function saveBuilder(array $validated, ?Course $course, int $userId): array
    {
        $payload = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'objectives' => $this->normalizeList($validated['objectives'] ?? []),
            'requirements' => $this->normalizeList($validated['requirements'] ?? []),
            'category_id' => $validated['category_id'],
            'price' => $validated['pricing_model'] === 'paid' ? (float) ($validated['price'] ?? 0) : 0,
            'thumbnail_url' => $validated['thumbnail_url'] ?? ($course ? $course->thumbnail_url : null),
            'video_preview_url' => $validated['video_preview_url'] ?? ($course ? $course->video_preview_url : null),
        ];

        if ($course) {
            abort_unless($userId === $course->instructor_id, 403);
            $this->courseRepository->updateCourse($course, $payload);
            $course = $this->courseRepository->loadCourseForBuilderEdit($course);

            return [
                'course' => $course,
                'is_edit' => true,
                'next_step' => 'curriculum',
            ];
        }

        return [
            'draft' => $payload,
            'is_edit' => false,
            'next_step' => 'curriculum',
        ];
    }

    public function getCurriculumData(?Course $course, int $userId): array
    {
        if ($course) {
            abort_unless($userId === $course->instructor_id, 403);
            $course = $this->courseRepository->loadCourseForCurriculumEdit($course);

            return [
                'is_edit' => true,
                'course' => $course,
            ];
        }

        return [
            'is_edit' => false,
            'course' => null,
            'curriculum_template' => [],
        ];
    }

    public function storeCourseWithCurriculum(array $builderPayload, array $curriculum, ?Course $editCourse, int $userId): Course
    {
        if ($editCourse) {
            abort_unless($userId === $editCourse->instructor_id, 403);
            $course = $editCourse;
            $this->courseRepository->updateCourse($course, $this->normalizeBuilderPayload($builderPayload));
            $this->courseRepository->deleteCourseSections($course);
        } else {
            $payload = $this->normalizeBuilderPayload($builderPayload);
            $payload['instructor_id'] = $userId;
            $payload['status'] = 'pending';

            $course = $this->courseRepository->createCourse($payload);
        }

        $this->persistCurriculumSections($course, $curriculum);

        return $course->fresh(['instructor', 'sections.lessons', 'reviews']);
    }

    protected function persistCurriculumSections(Course $course, array $curriculum): void
    {
        $sectionOrder = 1;

        foreach ($curriculum as $sectionData) {
            $section = $this->courseRepository->createSection($course, [
                'title' => $sectionData['title'] ?? 'Section ' . $sectionOrder,
                'order_index' => $sectionOrder,
            ]);

            $sectionOrder++;

            $lectures = $sectionData['lectures'] ?? [];
            $lessonOrder = 1;

            foreach ($lectures as $lessonData) {
                $type = $lessonData['type'] ?? 'article';

                $lessonRecord = [
                    'title' => $lessonData['title'] ?? 'Untitled lesson',
                    'content_type' => $type,
                    'order_index' => $lessonOrder,
                    'article_content' => $type === 'article' ? ($lessonData['article_content'] ?? null) : null,
                    'video_url' => $type === 'video' ? ($lessonData['video_url'] ?? null) : null,
                ];

                $this->courseRepository->createLesson($section, $lessonRecord);
                $lessonOrder++;
            }
        }
    }

    public function getLearnDataByIds(int $courseId, ?int $lessonId, int $userId): array
    {
        $course = $this->courseRepository->findCourseForLearnById($courseId);

        abort_unless($this->canUserViewCourseContent($course, $userId), 403);

        if (!$lessonId) {
            $lesson = $this->courseRepository->getFirstLesson($course);
        } else {
            $lesson = $this->courseRepository->findLessonInCourse($lessonId, $course->id);
        }

        $completedLessonIds = $this->attachCompletionState($course, $lesson, $userId);

        return compact('course', 'lesson', 'completedLessonIds');
    }

    protected function attachCompletionState(Course $course, ?object $currentLesson, int $userId): array
    {
        $completedLessonIds = DB::table('lesson_completions as lp')
            ->join('lessons as l', 'l.id', '=', 'lp.lesson_id')
            ->join('sections as s', 's.id', '=', 'l.section_id')
            ->where('lp.user_id', $userId)
            ->where('s.course_id', $course->id)
            ->pluck('lp.lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $completedLookup = array_flip($completedLessonIds);

        if ($course->relationLoaded('sections')) {
            foreach ($course->sections as $section) {
                if (!$section->relationLoaded('lessons')) {
                    continue;
                }

                foreach ($section->lessons as $lesson) {
                    $lesson->setAttribute('is_completed', isset($completedLookup[(int) $lesson->id]));
                }
            }
        }

        if ($currentLesson) {
            $currentLesson->setAttribute('is_completed', isset($completedLookup[(int) $currentLesson->id]));
        }

        return $completedLessonIds;
    }

    protected function canUserViewCourseContent(Course $course, int $userId): bool
    {
        $user = User::query()->select(['id', 'role'])->find($userId);
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && (int) $course->instructor_id === (int) $user->id) {
            return true;
        }

        return $this->courseRepository->isUserEnrolledInCourse($course->id, $userId);
    }

    public function destroyCourse(Course $course, int $userId): bool
    {
        abort_unless($userId === $course->instructor_id, 403);

        if ($this->courseRepository->courseHasEnrollments($course)) {
            return false;
        }

        $this->courseRepository->deleteCourse($course);

        return true;
    }

    public function toggleCourseVisibility(Course $course, int $userId): string
    {
        abort_unless($userId === $course->instructor_id, 403);

        $course->status = $course->status === 'draft' ? 'published' : 'draft';
        $this->courseRepository->saveCourse($course);

        return $course->status;
    }

    public function updateCourseInfo(int $courseId, array $payload, int $userId): Course
    {
        $course = $this->courseRepository->findCourseById($courseId);

        abort_unless($userId === $course->instructor_id, 403);

        if (($payload['status'] ?? null) === 'published') {
            $lessonsCount = $this->courseRepository->countCourseLessons($course);

            abort_if($lessonsCount === 0, 422, 'Không thể xuất bản khóa học không có bài học nào!');
        }

        $this->courseRepository->updateCourse($course, $payload);

        return $course->fresh(['instructor']);
    }

    public function storeReview(Course $course, array $validated, int $userId): void
    {
        $this->courseRepository->upsertReview($userId, $course->id, [
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? '',
        ]);
    }

    public function getUserReview(Course $course, int $userId): ?Review
    {
        return $this->courseRepository->findUserReviewInCourse($userId, $course->id);
    }

    public function deleteUserReview(Course $course, int $userId): bool
    {
        return $this->courseRepository->deleteUserReviewInCourse($userId, $course->id);
    }

    protected function normalizeBuilderPayload(array $payload): array
    {
        return [
            'title' => $payload['title'],
            'description' => $payload['description'] ?? '',
            'objectives' => $this->normalizeList($payload['objectives'] ?? []),
            'requirements' => $this->normalizeList($payload['requirements'] ?? []),
            'category_id' => (int) $payload['category_id'],
            'price' => ($payload['pricing_model'] ?? 'free') === 'paid' ? (float) ($payload['price'] ?? 0) : 0,
            'thumbnail_url' => $payload['thumbnail_url'] ?? null,
            'video_preview_url' => $payload['video_preview_url'] ?? null,
        ];
    }

    protected function normalizeList(array|string|null $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => is_string($item) && trim($item) !== ''));
        }

        if (is_string($value) && trim($value) !== '') {
            return array_values(array_filter(array_map('trim', explode(';;', $value))));
        }

        return [];
    }
}
