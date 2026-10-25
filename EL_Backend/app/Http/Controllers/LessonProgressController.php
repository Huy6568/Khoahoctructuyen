<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProgressService;
use App\Models\Course;
use App\Models\Lessons;


class LessonProgressController extends Controller
{
    public function __construct(private ProgressService $service)
    {
    }

    public function show(Course $course, Request $request)
    {
        $this->authorize('viewContent', $course); // Đảm bảo user đã mua khóa học

        $progress = $this->service->getCourseProgress($request->user()->id, $course->id);

        return response()->json([
            'data' => $progress
        ]);
    }

    public function complete(Course $course, Lessons $lesson, Request $request)
    {
        // Kiểm tra quyền: Nếu chưa mua khóa học (không có trong enrollments) -> Đá văng!
        $this->authorize('viewContent', $course);

        // Cập nhật tiến độ
        $newProgress = $this->service->markLessonAsCompleted($request->user()->id, $lesson->id, $course->id);

        return response()->json([
            'message' => 'Đã đánh dấu hoàn thành',
            'progress' => $newProgress
        ]);
    }

    public function destroy(Course $course, Lessons $lesson, Request $request)
    {
        // Kiểm tra quyền: Nếu chưa mua khóa học (không có trong enrollments) -> Đá văng!
        $this->authorize('viewContent', $course);

        $newProgress = $this->service->markLessonAsIncomplete($request->user()->id, $lesson->id, $course->id);

        return response()->json([
            'message' => 'Đã hủy đánh dấu hoàn thành',
            'progress' => $newProgress,
        ]);
    }
}
