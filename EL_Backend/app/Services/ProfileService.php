<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\ProfileRepository;

class ProfileService
{
    public function __construct(
        protected ProfileRepository $profileRepository
    ) {
    }

    public function updateProfile(User $user, array $validated): User
    {
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $this->profileRepository->save($user);

        return $user;
    }

    public function deleteAccount(User $user): void
    {
        $this->profileRepository->deleteAllTokens($user);
        $this->profileRepository->deleteUser($user);
    }
}
