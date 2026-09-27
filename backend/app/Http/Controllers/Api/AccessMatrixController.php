<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccessMatrixRequest;
use App\Models\AccessMatrix;
use App\Models\Staff;
use App\Services\AccessMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessMatrixController extends Controller
{
    public function __construct(
        protected AccessMatrixService $accessMatrixService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $records = $this->accessMatrixService->getMatrixRules(
            $request->filled('role_id') ? $request->integer('role_id') : null,
            $request->filled('module_id') ? $request->integer('module_id') : null
        );

        return response()->json([
            'success' => true,
            'count' => $records->count(),
            'data' => $records,
        ]);
    }

    public function getStaffModules(Request $request, ?Staff $staff = null): JsonResponse
    {
        $data = $this->accessMatrixService->getStaffModules($request->all(), $staff);

        return response()->json(array_merge(['success' => true], $data));
    }

    public function update(UpdateAccessMatrixRequest $request, AccessMatrix $accessMatrix): JsonResponse
    {
        $updatedMatrix = $this->accessMatrixService->updateRule($accessMatrix, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Module access rule updated successfully.',
            'data' => $updatedMatrix,
        ]);
    }
}
