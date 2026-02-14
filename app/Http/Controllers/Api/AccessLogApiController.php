<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccessLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessLogApiController extends Controller
{
    protected AccessLogService $accessLogService;

    public function __construct(AccessLogService $accessLogService)
    {
        $this->accessLogService = $accessLogService;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'progress' => 'required|string',
            'category' => 'nullable|string',
            'email' => 'nullable|email',
            'number' => 'nullable|string',
            'company_id' => 'nullable|string',
            'method' => 'nullable|string',
            'endpoint' => 'nullable|string',
            'status_code' => 'nullable|integer',
            'request_data' => 'nullable|array',
            'action' => 'nullable|string',
            'activity_type' => 'nullable|string',
        ]);

        $log = $this->accessLogService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Access log created',
            'data' => $log,
        ], 200);
    }
}
