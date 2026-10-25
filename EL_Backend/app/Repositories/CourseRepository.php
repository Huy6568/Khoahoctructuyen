<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lessons;
use App\Models\Review;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CourseRepository
{
    public function findCourseById(int $courseId): Course
    {
        return Course::findOrFail($courseId);
    }

	public function getHomeCourses(int $limit = 8): Collection
	{
		return Course::with('instructor')
            ->where('status', 'published')
			->withCount('reviews')
			->withAvg('reviews', 'rating')
			->latest()
			->take($limit)
			->get();
	}

	public function getCategories(): Collection
	{
		return Category::orderBy('name')->get();
	}

	public function getMyLearningEnrollments(int $userId, array $filters = [], int $perPage = 16): LengthAwarePaginator
	{
		$query = Enrollment::with(['course.instructor'])
			->where('user_id', $userId);

		if (!empty($filters['search'])) {
			$search = trim((string) $filters['search']);

			$query->whereHas('course', function ($courseQuery) use ($search) {
				$courseQuery->where('title', 'like', '%' . $search . '%')
					->orWhere('description', 'like', '%' . $search . '%');
			});
		}

		$sort = $filters['sort'] ?? 'latest';
		if ($sort === 'oldest') {
			$query->orderBy('purchased_at', 'asc');
		} else {
			$query->orderBy('purchased_at', 'desc');
		}

		return $query->paginate($perPage)->withQueryString();
	}

	public function makePublicCoursesQuery(): Builder
	{
		return Course::with('instructor')
            ->where('status', 'published')
			->withCount('reviews')
			->withAvg('reviews', 'rating');
	}

	public function makeInstructorCoursesQuery(int $instructorId): Builder
	{
		return Course::query()->where('instructor_id', $instructorId);
	}

	public function paginateCourses(Builder $query, int $perPage = 12): LengthAwarePaginator
	{
		return $query->paginate($perPage)->withQueryString();
	}

	public function loadCourseDetail(Course $course): Course
	{
		$course->load(['instructor', 'sections.lessons', 'lessons', 'reviews.user']);
		$course->loadCount([
			'lessons as video_count' => function ($query) {
				$query->where('content_type', 'video');
			},
			'lessons as article_count' => function ($query) {
				$query->where('content_type', 'article');
			},
			'reviews',
			'enrollments',
		]);
		$course->loadAvg('reviews', 'rating');

		return $course;
	}

	public function isUserEnrolledInCourse(int $courseId, int $userId): bool
	{
		return Enrollment::where('course_id', $courseId)
			->where('user_id', $userId)
			->exists();
	}

	public function loadCourseForLearning(Course $course): Course
	{
		$course->load(['instructor', 'sections.lessons', 'lessons']);

		return $course;
	}

	public function getFirstLesson(Course $course): ?Lessons
	{
		return $course->sections->first()?->lessons->first();
	}

	public function loadCourseForCurriculumEdit(Course $course): Course
	{
		$course->load(['instructor', 'sections.lessons']);
		$course->loadCount('enrollments');

		return $course;
	}

	public function loadCourseForBuilderEdit(Course $course): Course
	{
		$course->load(['instructor']);
		$course->loadCount('enrollments');

		return $course;
	}

	public function findCourseForLearnById(int $courseId): Course
	{
		return Course::with('instructor', 'sections.lessons', 'lessons')->findOrFail($courseId);
	}

	public function findLessonInCourse(int $lessonId, int $courseId): Lessons
	{
		return Lessons::where('id', $lessonId)
			->whereHas('section', function ($query) use ($courseId) {
				$query->where('course_id', $courseId);
			})
			->firstOrFail();
	}

	public function updateCourse(Course $course, array $data): bool
	{
		return $course->update($data);
	}

    public function countCourseLessons(Course $course): int
    {
        return $course->lessons()->count();
    }

	public function createCourse(array $data): Course
	{
		return Course::create($data);
	}

	public function deleteCourseSections(Course $course): void
	{
		$course->sections()->delete();
	}

	public function createSection(Course $course, array $data): Section
	{
		return $course->sections()->create($data);
	}

	public function createLesson(Section $section, array $data): Lessons
	{
		return $section->lessons()->create($data);
	}

	public function courseHasEnrollments(Course $course): bool
	{
		return $course->enrollments()->exists();
	}

	public function deleteCourse(Course $course): ?bool
	{
		return $course->delete();
	}

	public function saveCourse(Course $course): bool
	{
		return $course->save();
	}

	public function upsertReview(int $userId, int $courseId, array $data): Review
	{
		return Review::updateOrCreate(
			[
				'user_id' => $userId,
				'course_id' => $courseId,
			],
			$data
		);
	}

	public function findUserReviewInCourse(int $userId, int $courseId): ?Review
	{
		return Review::query()
			->where('user_id', $userId)
			->where('course_id', $courseId)
			->first();
	}

	public function deleteUserReviewInCourse(int $userId, int $courseId): bool
	{
		return (bool) Review::query()
			->where('user_id', $userId)
			->where('course_id', $courseId)
			->delete();
	}
}
