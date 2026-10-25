<?php

namespace App\Repositories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StatisticRepository
{
    public function countUsers(): int
    {
        return User::count();
    }

    public function countUsersByStatus(string $status): int
    {
        return User::where('status', $status)->count();
    }

    public function countCourses(): int
    {
        return Course::count();
    }

    public function countCoursesByStatus(string $status): int
    {
        return Course::where('status', $status)->count();
    }

    public function countEnrollments(): int
    {
        return Enrollment::count();
    }

    public function sumEnrollmentRevenueByCourseIds(Collection $courseIds): float
    {
        return (float) Enrollment::whereIn('course_id', $courseIds)->sum('purchase_price');
    }

    public function sumEnrollmentRevenue(): float
    {
        return (float) Enrollment::sum('purchase_price');
    }

    public function instructorCourseIds(int $instructorId): Collection
    {
        return Course::query()
            ->where('instructor_id', $instructorId)
            ->pluck('id');
    }

    public function countInstructorCourses(int $instructorId): int
    {
        return Course::where('instructor_id', $instructorId)->count();
    }

    public function countInstructorCoursesByStatus(int $instructorId, string $status): int
    {
        return Course::where('instructor_id', $instructorId)
            ->where('status', $status)
            ->count();
    }

    public function countDistinctEnrolledUsersByCourseIds(Collection $courseIds): int
    {
        return Enrollment::whereIn('course_id', $courseIds)
            ->distinct('user_id')
            ->count('user_id');
    }

    public function getMonthlyRevenue(int $months = 12): Collection
    {
        return Enrollment::query()
            ->selectRaw("DATE_FORMAT(purchased_at, '%Y-%m') as month")
            ->selectRaw('SUM(purchase_price) as revenue')
            ->whereNotNull('purchased_at')
            ->where('purchased_at', '>=', now()->subMonths(max(1, $months - 1))->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getMonthlyRevenueByCourseIds(Collection $courseIds, int $months = 12): Collection
    {
        return Enrollment::query()
            ->selectRaw("DATE_FORMAT(purchased_at, '%Y-%m') as month")
            ->selectRaw('SUM(purchase_price) as revenue')
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('purchased_at')
            ->where('purchased_at', '>=', now()->subMonths(max(1, $months - 1))->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getTopCourses(int $limit = 5): Collection
    {
        return Enrollment::query()
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->select('enrollments.course_id', 'courses.title')
            ->selectRaw('COUNT(enrollments.id) as enrollments_count')
            ->selectRaw('COUNT(DISTINCT enrollments.user_id) as students_count')
            ->selectRaw('SUM(enrollments.purchase_price) as revenue')
            ->groupBy('enrollments.course_id', 'courses.title')
            ->orderByDesc('revenue')
            ->orderByDesc('enrollments_count')
            ->limit(max(1, $limit))
            ->get();
    }

    public function getTopCoursesByCourseIds(Collection $courseIds, int $limit = 5): Collection
    {
        return Enrollment::query()
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->whereIn('enrollments.course_id', $courseIds)
            ->select('enrollments.course_id', 'courses.title')
            ->selectRaw('COUNT(enrollments.id) as enrollments_count')
            ->selectRaw('COUNT(DISTINCT enrollments.user_id) as students_count')
            ->selectRaw('SUM(enrollments.purchase_price) as revenue')
            ->groupBy('enrollments.course_id', 'courses.title')
            ->orderByDesc('revenue')
            ->orderByDesc('enrollments_count')
            ->limit(max(1, $limit))
            ->get();
    }

    public function paginateTransactions(int $perPage = 20): LengthAwarePaginator
    {
        return Enrollment::query()
            ->with([
                'user:id,fullname,email',
                'course:id,title,instructor_id',
                'course.instructor:id,fullname,email',
            ])
            ->orderByDesc('purchased_at')
            ->paginate(max(1, $perPage))
            ->withQueryString();
    }

    public function paginateTransactionsByCourseIds(Collection $courseIds, int $perPage = 20): LengthAwarePaginator
    {
        return Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->with([
                'user:id,fullname,email',
                'course:id,title,instructor_id',
                'course.instructor:id,fullname,email',
            ])
            ->orderByDesc('purchased_at')
            ->paginate(max(1, $perPage))
            ->withQueryString();
    }
}
