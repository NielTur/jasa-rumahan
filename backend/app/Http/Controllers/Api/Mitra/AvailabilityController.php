<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncMitraAvailabilitiesRequest;
use App\Http\Resources\MitraAvailabilityResource;
use App\Services\MitraSetupService;
use App\Support\ApiResponse;
use App\Support\CurrentMitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private MitraSetupService $setup)
    {
    }

    // GET /mitra/availabilities
    public function index(Request $request): JsonResponse
    {
        $items = $this->setup->availabilities(CurrentMitra::profile($request));

        return ApiResponse::success($this->toArray($items));
    }

    // PUT /mitra/availabilities: replace-all
    public function update(SyncMitraAvailabilitiesRequest $request): JsonResponse
    {
        $items = $this->setup->replaceAvailabilities(
            CurrentMitra::profile($request),
            $request->validated()['availabilities']
        );

        return ApiResponse::success($this->toArray($items), 'Jadwal berhasil diperbarui.');
    }

    private function toArray($items): array
    {
        return $items->map(fn($a) => (new MitraAvailabilityResource($a))->resolve())->values()->all();
    }
}