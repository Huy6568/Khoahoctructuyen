<?php

namespace App\Http\Controllers;

use App\Services\StatisticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorStatisticController extends Controller
{
    public function __construct(
        protected StatisticService $statisticService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->check(), 401);

        $months = max(1, min(24, (int) $request->integer('months', 12)));
        $topLimit = max(1, min(20, (int) $request->integer('top_limit', 5)));
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        return response()->json([
            'data' => $this->statisticService->getInstructorStatistics((int) auth()->id(), $months, $topLimit, $perPage),
        ]);
    }
}
