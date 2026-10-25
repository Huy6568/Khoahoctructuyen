<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
// use Illuminate\Auth\Access\Response;

class CoursePolicy
{

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    private function isInstructorOwner(User $user, Course $course): bool
    {
        return $user->role === 'instructor' && $user->id === $course->instructor_id;
    }

    public function viewContent(User $user, Course $course)
    {
        // 1. Nếu là Admin -> Luôn được xem (để kiểm duyệt)
        if ($this->isAdmin($user)) {
            return true;
        }

        // 2. Nếu là Giảng viên tạo ra khóa học này -> Được xem
        if ($this->isInstructorOwner($user, $course)) {
            return true;
        }

        // 3. Nếu là Học viên -> Kiểm tra xem đã có trong enrollments chưa
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Chỉ admin được phép truy cập endpoint "view any" để tránh lộ toàn bộ khóa học.
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        // Admin có thể xem bất kỳ khóa học nào
        if ($this->isAdmin($user)) {
            return true;
        }

        // Instructor có thể xem khóa học họ tạo
        return $this->isInstructorOwner($user, $course);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin và Instructor có thể tạo khóa học
        return $this->isAdmin($user) || $user->role === 'instructor';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        // Admin có thể cập nhật bất kỳ khóa học nào
        if ($this->isAdmin($user)) {
            return true;
        }

        // Instructor có thể cập nhật khóa học họ tạo
        return $this->isInstructorOwner($user, $course);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        // Admin có thể xóa bất kỳ khóa học nào
        if ($this->isAdmin($user)) {
            return true;
        }

        // Instructor có thể xóa khóa học họ tạo
        return $this->isInstructorOwner($user, $course);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        // Admin có thể khôi phục bất kỳ khóa học nào
        if ($this->isAdmin($user)) {
            return true;
        }

        // Instructor có thể khôi phục khóa học họ tạo
        return $this->isInstructorOwner($user, $course);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        // Admin có thể xóa vĩnh viễn bất kỳ khóa học nào
        if ($this->isAdmin($user)) {
            return true;
        }

        // Instructor có thể xóa vĩnh viễn khóa học họ tạo
        return $this->isInstructorOwner($user, $course);
    }
}
