<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\RegisterMitraRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth)
    {
    }

    public function registerCustomer(RegisterCustomerRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->registerCustomer($request->validated());

        return ApiResponse::success($this->authPayload($user, $token), 'Registrasi berhasil.', 201);
    }

    public function registerMitra(RegisterMitraRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->registerMitra($request->validated());

        return ApiResponse::success($this->authPayload($user, $token), 'Registrasi mitra berhasil.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->login($request->input('phone'), $request->input('password'));

        return ApiResponse::success($this->authPayload($user, $token), 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        // Hapus hanya token yang dipakai request ini
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('mitraProfile');

        return ApiResponse::success((new UserResource($user))->resolve());
    }

    // Bentuk data login/register: token + user (+ mitra_id jika mitra)
    private function authPayload(User $user, string $token): array
    {
        $user->loadMissing('mitraProfile');

        $payload = [
            'token' => $token,
            'user' => (new UserResource($user))->resolve(),
        ];

        if ($user->role === 'mitra') {
            $payload['mitra_id'] = $user->mitraProfile?->id;
        }

        return $payload;
    }
}