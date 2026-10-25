<?php

namespace App\Repositories;

use App\Models\User;

class ProfileRepository
{
    public function save(User $user): bool
    {
        return $user->save();
    }

    public function deleteAllTokens(User $user): int
    {
        return $user->tokens()->delete();
    }

    public function deleteUser(User $user): ?bool
    {
        return $user->delete();
    }
}
