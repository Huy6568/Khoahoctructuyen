<?php

namespace App\Http\Controllers;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function index(Request $request)
    {
        $users = $this->userService->getUsersForIndex($request->only(['search']));

        return view('user.index', compact('users'));
    }

    public function myLearning()
    {
        $enrollments = $this->userService->getMyLearningEnrollments(auth()->id());

        return view('learning.index', compact('enrollments'));
    }
}
