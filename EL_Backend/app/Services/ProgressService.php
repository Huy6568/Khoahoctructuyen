<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lessons;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    public function markLessonAsCompleted(int $userId, int $lessonId, int $courseId)
    {
        // 1. Phải có đoạn này để lưu ID bài học xuống DB
        \App\Models\LessonCompletion::updateOrCreate([
            'user_id' => $userId,
            'lesson_id' => $lessonId
        ]);

        // 2. Logic tính toán phần trăm ...
        $totalLessons = \App\Models\Lessons::whereHas('section', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->count();

        // Lấy lại số bài đã học (để tính toán chính xác)
        $completedCount = \App\Models\LessonCompletion::where('user_id', $userId)
            ->whereIn('lesson_id', function ($query) use ($courseId) {
                $query->select('l.id')
                    ->from('lessons as l')
                    ->join('sections as s', 's.id', '=', 'l.section_id')
                    ->where('s.course_id', $courseId);
            })->count();

        $percent = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

        // 3. Cập nhật phần trăm
        \App\Models\Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->update(['progress' => $percent]);

        return $percent;
    }

    public function markLessonAsIncomplete(int $userId, int $lessonId, int $courseId): int
    {
        $this->ensureLessonBelongsToCourse($lessonId, $courseId);

        DB::table('lesson_progresses')
            ->where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->delete();

        return $this->recalculateCourseProgress($userId, $courseId);
    }

    protected function recalculateCourseProgress(int $userId, int $courseId): int
    {
        $totalLessons = Lessons::whereHas('section', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })->count();

        $completedLessons = DB::table('lesson_progresses as lp')
            ->join('lessons as l', 'l.id', '=', 'lp.lesson_id')
            ->join('sections as s', 's.id', '=', 'l.section_id')
            ->where('lp.user_id', $userId)
            ->where('s.course_id', $courseId)
            ->distinct('lp.lesson_id')
            ->count('lp.lesson_id');

        $progressPercent = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;

        Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->update(['progress' => $progressPercent]);

        return $progressPercent;
    }

    protected function ensureLessonBelongsToCourse(int $lessonId, int $courseId): void
    {
        $isValidLesson = Lessons::where('id', $lessonId)
            ->whereHas('section', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->exists();

        abort_unless($isValidLesson, 404, 'Bài học không thuộc khóa học này.');
    }

    public function getCourseProgress(int $userId, int $courseId): array
    {
        // 1. Lấy danh sách ID bài học đã hoàn thành
        $completedLessonIds = DB::table('lesson_completions as lp')
            ->join('lessons as l', 'l.id', '=', 'lp.lesson_id')
            ->join('sections as s', 's.id', '=', 'l.section_id')
            ->where('lp.user_id', $userId)
            ->where('s.course_id', $courseId)
            ->pluck('lp.lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 2. Lấy phần trăm tiến độ từ bảng enrollments (đã tính ở bước trước)
        $progressPercent = DB::table('enrollments')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->value('progress') ?? 0;

        return [
            'progress' => (int) $progressPercent,
            'completed_lesson_ids' => $completedLessonIds
        ];
    }
}
