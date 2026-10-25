<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }

    public function getUsersForIndex(array $filters): LengthAwarePaginator
    {
        $query = $this->userRepository->makeUsersQuery();

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $this->userRepository->paginateUsers($query);
    }

    public function getMyLearningEnrollments(int $userId): LengthAwarePaginator
    {
        return $this->userRepository->getMyLearningEnrollments($userId);
    }
}
