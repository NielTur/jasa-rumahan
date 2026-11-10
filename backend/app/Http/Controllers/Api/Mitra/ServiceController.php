<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncMitraServicesRequest;
use App\Http\Resources\MitraServiceResource;
use App\Services\MitraSetupService;
use App\Support\ApiResponse;
use App\Support\CurrentMitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(private MitraSetupService $setup)
    {
    }

    // GET /mitra/services: semua jasa katalog aktif + flag selected
    public function index(Request $request): JsonResponse
    {
        $items = $this->setup->catalogWithSelection(CurrentMitra::profile($request));

        return ApiResponse::success($this->toArray($items));
    }

    // PUT /mitra/services: { "service_ids": [1, 2] }
    public function update(SyncMitraServicesRequest $request): JsonResponse
    {
        $items = $this->setup->syncServices(
            CurrentMitra::profile($request),
            $request->validated()['service_ids']
        );

        return ApiResponse::success($this->toArray($items), 'Jasa yang ditawarkan berhasil diperbarui.');
    }

    private function toArray($items): array
    {
        return $items->map(fn($s) => (new MitraServiceResource($s))->resolve())->values()->all();
    }
}