<?php

namespace App\Services;

use App\Repositories\StatisticRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StatisticService
{
    public function __construct(
        protected StatisticRepository $statisticRepository
    ) {
    }

    public function getAdminStatistics(int $months = 12, int $topLimit = 5, int $perPage = 20): array
    {
        $transactions = $this->statisticRepository->paginateTransactions($perPage);

        return [
            'revenue' => [
                'total' => $this->statisticRepository->sumEnrollmentRevenue(),
                'monthly' => $this->mapMonthlyRevenue($this->statisticRepository->getMonthlyRevenue($months)),
            ],
            'students' => [
                'total' => $this->statisticRepository->countUsers(),
            ],
            'users' => [
                'total' => $this->statisticRepository->countUsers(),
                'active' => $this->statisticRepository->countUsersByStatus('active'),
                'banned' => $this->statisticRepository->countUsersByStatus('banned'),
            ],
            'courses' => [
                'total' => $this->statisticRepository->countCourses(),
                'published' => $this->statisticRepository->countCoursesByStatus('published'),
                'pending' => $this->statisticRepository->countCoursesByStatus('pending'),
                'rejected' => $this->statisticRepository->countCoursesByStatus('rejected'),
            ],
            'enrollments' => [
                'total' => $this->statisticRepository->countEnrollments(),
            ],
            'top_courses' => $this->mapTopCourses($this->statisticRepository->getTopCourses($topLimit)),
            'transactions' => [
                'data' => $this->mapTransactions($transactions),
                'meta' => $this->paginationMeta($transactions),
            ],
        ];
    }

    public function getInstructorStatistics(int $instructorId, int $months = 12, int $topLimit = 5, int $perPage = 20): array
    {
        $courseIds = $this->statisticRepository->instructorCourseIds($instructorId);
        $transactions = $this->statisticRepository->paginateTransactionsByCourseIds($courseIds, $perPage);

        return [
            'revenue' => [
                'total' => $this->statisticRepository->sumEnrollmentRevenueByCourseIds($courseIds),
                'monthly' => $this->mapMonthlyRevenue($this->statisticRepository->getMonthlyRevenueByCourseIds($courseIds, $months)),
            ],
            'courses' => [
                'total' => $this->statisticRepository->countInstructorCourses($instructorId),
                'published' => $this->statisticRepository->countInstructorCoursesByStatus($instructorId, 'published'),
                'draft' => $this->statisticRepository->countInstructorCoursesByStatus($instructorId, 'draft'),
                'pending' => $this->statisticRepository->countInstructorCoursesByStatus($instructorId, 'pending'),
            ],
            'students' => [
                'total' => $this->statisticRepository->countDistinctEnrolledUsersByCourseIds($courseIds),
            ],
            'top_courses' => $this->mapTopCourses($this->statisticRepository->getTopCoursesByCourseIds($courseIds, $topLimit)),
            'transactions' => [
                'data' => $this->mapTransactions($transactions),
                'meta' => $this->paginationMeta($transactions),
            ],
        ];
    }

    protected function mapMonthlyRevenue(Collection $rows): array
    {
        return $rows->map(function ($row) {
            return [
                'month' => $row->month,
                'revenue' => (float) $row->revenue,
            ];
        })->values()->all();
    }

    protected function mapTopCourses(Collection $rows): array
    {
        return $rows->map(function ($row) {
            return [
                'course_id' => (int) $row->course_id,
                'title' => (string) $row->title,
                'enrollments_count' => (int) $row->enrollments_count,
                'students_count' => (int) $row->students_count,
                'revenue' => (float) $row->revenue,
            ];
        })->values()->all();
    }

    protected function mapTransactions(LengthAwarePaginator $transactions): array
    {
        return $transactions->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'course' => [
                    'id' => $item->course?->id,
                    'title' => $item->course?->title,
                ],
                'student' => [
                    'id' => $item->user?->id,
                    'fullname' => $item->user?->fullname,
                    'email' => $item->user?->email,
                ],
                'instructor' => [
                    'id' => $item->course?->instructor?->id,
                    'fullname' => $item->course?->instructor?->fullname,
                    'email' => $item->course?->instructor?->email,
                ],
                'purchase_price' => (float) $item->purchase_price,
                'purchased_at' => optional($item->purchased_at)?->toISOString(),
            ];
        })->values()->all();
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
