<?php

namespace App\Observers;

use App\Models\Lesson;

class LessonObserver
{
    /**
     * Handle the Lesson "created" event.
     */
    public function created(Lesson $lesson): void
    {
        //
    }

    /**
     * Handle the Lesson "updated" event.
     */
    public function updated(Lesson $lesson): void
    {
        //
    }

    /**
     * Handle the Lesson "deleted" event.
     */


    public function deleted(Lesson $lesson)
    {
        // Lấy Section chứa bài học này
        $section = $lesson->section;
        if (!$section) return;

        // Lấy Khóa học
        $course = $section->course;

        // Đếm tổng số bài học trong tất cả các chương của khóa học này
        $lessonsCount = $course->sections()->withCount('lessons')->get()->sum('lessons_count');

        // Nếu không còn bài học nào, tự động chuyển về draft
        if ($lessonsCount === 0 && $course->status !== 'draft') {
            $course->update(['status' => 'draft']);
        }
    }


    /**
     * Handle the Lesson "restored" event.
     */
    public function restored(Lesson $lesson): void
    {
        //
    }

    /**
     * Handle the Lesson "force deleted" event.
     */
    public function forceDeleted(Lesson $lesson): void
    {
        //
    }
}
