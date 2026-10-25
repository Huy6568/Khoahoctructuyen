<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Http\Resources\CategoryResource;
use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Notifications\SystemNotification;

class AdminController extends Controller
{
    public function __construct(
        protected AdminService $adminService
    ) {
    }

    public function index(): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());

        return response()->json([
            'data' => [
                'message' => 'Admin API is ready.',
            ],
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $users = $this->adminService->getUsers($request->only(['search', 'role']));

        return response()->json([
            'data' => $users->getCollection()->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => $user->avatar,
                    'bio' => $user->bio,
                    'created_at' => optional($user->created_at)?->toISOString(),
                ];
            })->values(),
            'meta' => $this->paginationMeta($users),
        ]);
    }

    public function banUser(User $user): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->banUser($user);

        return response()->json([
            'message' => 'Người dùng đã bị cấm.',
        ]);
    }

    public function unbanUser(User $user): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->unbanUser($user);

        return response()->json([
            'message' => 'Người dùng đã được kích hoạt lại.',
        ]);
    }

    public function course(Request $request): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $courses = $this->adminService->getCourses($request->only(['search', 'status']));

        return response()->json([
            'data' => CourseResource::collection($courses->getCollection()),
            'meta' => $this->paginationMeta($courses),
        ]);
    }

    public function category(Request $request): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $categories = $this->adminService->getCategories($request->only(['search', 'status']));

        return response()->json([
            'data' => CategoryResource::collection($categories->getCollection()),
            'meta' => $this->paginationMeta($categories),
        ]);
    }

    public function approveCourse(Course $course): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->approveCourse($course);

        $course->instructor->notify(new SystemNotification(
            'Khóa học được duyệt!',
            'Khóa học "'.$course->title.'" của bạn đã được Admin phê duyệt.',
            '/instructor/courses'
        ));

        return response()->json([
            'message' => 'Khóa học đã được duyệt.',
        ]);
    }

    public function rejectCourse(Course $course): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->rejectCourse($course);

        $course->instructor->notify(new SystemNotification(
            'Khóa học bị từ chối!',
            'Khóa học "'.$course->title.'" của bạn đã bị Admin từ chối.',
            '/instructor/courses'
        ));

        return response()->json([
            'message' => 'Khóa học đã bị từ chối.',
        ]);
    }

    public function undoCourse(Course $course): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->undoCourse($course);

        return response()->json([
            'message' => 'Đã hoàn tác trạng thái khóa học.',
        ]);
    }

    public function approveCategory(Category $category): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->approveCategory($category);

        return response()->json([
            'message' => 'Danh mục đã được duyệt.',
        ]);
    }

    public function rejectCategory(Category $category): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->rejectCategory($category);

        return response()->json([
            'message' => 'Danh mục đã bị từ chối.',
        ]);
    }

    public function undoCategory(Category $category): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());
        $this->adminService->undoCategory($category);

        return response()->json([
            'message' => 'Đã hoàn tác trạng thái danh mục.',
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
