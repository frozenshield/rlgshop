<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->to("{$frontendUrl}/login?error=".urlencode('Google sign-in failed. Please try again.'));
        }

        $token = $this->authService->handleSocialLogin($googleUser);

        return redirect()->to("{$frontendUrl}/auth/callback?token={$token}");
    }
}
