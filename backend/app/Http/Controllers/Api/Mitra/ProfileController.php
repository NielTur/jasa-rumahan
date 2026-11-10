<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMitraProfileRequest;
use App\Http\Resources\MitraProfileResource;
use App\Services\MitraSetupService;
use App\Support\ApiResponse;
use App\Support\CurrentMitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private MitraSetupService $setup)
    {
    }

    // GET /mitra/profile
    public function show(Request $request): JsonResponse
    {
        $mitra = CurrentMitra::profile($request);

        return ApiResponse::success((new MitraProfileResource($mitra))->resolve());
    }

    // PUT /mitra/profile
    public function update(UpdateMitraProfileRequest $request): JsonResponse
    {
        $mitra = $this->setup->updateProfile(CurrentMitra::profile($request), $request->validated());

        return ApiResponse::success((new MitraProfileResource($mitra))->resolve(), 'Profil berhasil diperbarui.');
    }
}