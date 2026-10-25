<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Repositories\AdminRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminService
{
    public function __construct(
        protected AdminRepository $adminRepository
    ) {
    }

    public function ensureAdmin(?User $actor): void
    {
        abort_unless($actor && $actor->role === 'admin', 403);
    }

    public function getUsers(array $filters): LengthAwarePaginator
    {
        $query = $this->adminRepository->makeUsersQuery();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $this->adminRepository->paginate($query);
    }

    public function getCourses(array $filters): LengthAwarePaginator
    {
        $query = $this->adminRepository->makeCoursesQuery();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhereHas('instructor', function ($instructorQuery) use ($search) {
                        $instructorQuery->where('fullname', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $this->adminRepository->paginate($query->latest());
    }

    public function getCategories(array $filters): LengthAwarePaginator
    {
        $query = $this->adminRepository->makeCategoriesQuery();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $this->adminRepository->paginate($query->orderBy('id', 'asc'));
    }

    public function banUser(User $user): void
    {
        $this->adminRepository->updateUserStatus($user, 'banned');
    }

    public function unbanUser(User $user): void
    {
        $this->adminRepository->updateUserStatus($user, 'active');
    }

    public function approveCourse(Course $course): void
    {
        $this->adminRepository->updateCourseStatus($course, 'published');
    }

    public function rejectCourse(Course $course): void
    {
        $this->adminRepository->updateCourseStatus($course, 'rejected');
    }

    public function undoCourse(Course $course): void
    {
        $this->adminRepository->updateCourseStatus($course, 'pending');
    }

    public function approveCategory(Category $category): void
    {
        $this->adminRepository->updateCategoryStatus($category, 'accepted');
    }

    public function rejectCategory(Category $category): void
    {
        $this->adminRepository->updateCategoryStatus($category, 'rejected');
    }

    public function undoCategory(Category $category): void
    {
        $this->adminRepository->updateCategoryStatus($category, 'pending');
    }
}
