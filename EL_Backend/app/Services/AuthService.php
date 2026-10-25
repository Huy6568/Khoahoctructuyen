<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        protected AuthRepository $authRepository
    ) {
    }

    public function register(array $validated): array
    {
        $user = $this->authRepository->createUser([
            'fullname' => $validated['fullname'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
            'status' => 'active',
        ]);

        $token = $this->authRepository->createAccessToken($user);

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->authRepository->findUserByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        $token = $this->authRepository->createAccessToken($user);

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $this->authRepository->deleteCurrentAccessToken($user);
    }

    public function registerWebUser(array $validated): User
    {
        return $this->authRepository->createUser([
            'fullname' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
            'status' => 'active',
        ]);
    }

    public function resetPassword(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function (User $user) use ($credentials) {
                $this->authRepository->updatePassword($user, $credentials['password']);
                event(new PasswordReset($user));
            }
        );
    }
}
