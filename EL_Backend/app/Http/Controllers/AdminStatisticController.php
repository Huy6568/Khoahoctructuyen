<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use App\Services\StatisticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStatisticController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
        protected StatisticService $statisticService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->adminService->ensureAdmin(auth()->user());

        $months = max(1, min(24, (int) $request->integer('months', 12)));
        $topLimit = max(1, min(20, (int) $request->integer('top_limit', 5)));
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        return response()->json([
            'data' => $this->statisticService->getAdminStatistics($months, $topLimit, $perPage),
        ]);
    }
}
