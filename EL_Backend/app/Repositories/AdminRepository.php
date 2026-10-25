<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminRepository
{
    public function makeUsersQuery(): Builder
    {
        return User::query();
    }

    public function makeCoursesQuery(): Builder
    {
        return Course::with('instructor');
    }

    public function makeCategoriesQuery(): Builder
    {
        return Category::query();
    }

    public function paginate(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function updateUserStatus(User $user, string $status): bool
    {
        return $user->update(['status' => $status]);
    }

    public function updateCourseStatus(Course $course, string $status): bool
    {
        return $course->update(['status' => $status]);
    }

    public function updateCategoryStatus(Category $category, string $status): bool
    {
        return $category->update(['status' => $status]);
    }
}
