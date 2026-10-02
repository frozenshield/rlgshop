<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $result = $this->authService->registerUser($request->validated());

        return response()->json([
            'success' => true,
            'token' => $result['token'],
            'user' => $result['user'],
        ], 201);
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $result = $this->authService->loginUser($request->validated());

        return response()->json([
            'success' => true,
            'token' => $result['token'],
            'user' => $result['user'],
        ]);
    }
}
