<?php

namespace App\Repositories;

use App\Models\Lessons;
use Illuminate\Support\Facades\DB;

class ProgressRepository
{
	public function getCourseLessonProgress(int $courseId, int $userId): array
	{
		$totalLessons = Lessons::whereHas('section', function ($query) use ($courseId) {
			$query->where('course_id', $courseId);
		})->count();

		$completedLessons = DB::table('lesson_progresses as lp')
			->join('lessons as l', 'l.id', '=', 'lp.lesson_id')
			->join('sections as s', 's.id', '=', 'l.section_id')
			->where('s.course_id', $courseId)
			->where('lp.user_id', $userId)
			->distinct('lp.lesson_id')
			->count('lp.lesson_id');

		return [
			'total_lessons' => $totalLessons,
			'completed_lessons' => $completedLessons,
		];
	}
}


