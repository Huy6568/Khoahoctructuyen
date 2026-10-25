<?php

namespace App\Repositories;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function makeUsersQuery(): Builder
    {
        return User::query();
    }

    public function paginateUsers(Builder $query, int $perPage = 12): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function getMyLearningEnrollments(int $userId, int $perPage = 16): LengthAwarePaginator
    {
        return Enrollment::with(['course.instructor'])
            ->where('user_id', $userId)
            ->orderBy('purchased_at', 'desc')
            ->paginate($perPage);
    }
}
