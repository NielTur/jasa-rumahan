<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyTypeResource;
use App\Http\Resources\ServiceCategoryResource;
use App\Http\Resources\ServiceResource;
use App\Models\PropertyType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Katalog publik (tanpa login). Hanya data aktif yang tampil.
class CatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        $data = ServiceCategory::where('is_active', true)
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            $data->map(fn($c) => (new ServiceCategoryResource($c))->resolve())->all()
        );
    }

    public function services(Request $request): JsonResponse
    {
        $data = Service::bookable()
            ->when(
                $request->filled('category_id'),
                fn($q) => $q->where('category_id', $request->integer('category_id'))
            )
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            $data->map(fn($service) => (new ServiceResource($service))->resolve())->all()
        );
    }

    public function showService(int $id): JsonResponse
    {
        $service = Service::bookable()
            ->with([
                'addons' => fn($q) => $q->where('is_active', true)->orderBy('id'),
                'prices' => fn($q) => $q->where('is_active', true)
                    ->whereHas('propertySize', fn($s) => $s->where('is_active', true)
                        ->whereHas('propertyType', fn($t) => $t->where('is_active', true)))
                    ->orderBy('property_size_id'),
                'prices.propertySize.propertyType',
            ])
            ->find($id);

        if (!$service) {
            throw new ApiException('Jasa tidak ditemukan.', 404);
        }

        return ApiResponse::success((new ServiceResource($service))->resolve());
    }

    public function propertyTypes(): JsonResponse
    {
        $data = PropertyType::where('is_active', true)
            ->with(['sizes' => fn($q) => $q->where('is_active', true)->orderBy('id')])
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            $data->map(fn($t) => (new PropertyTypeResource($t))->resolve())->all()
        );
    }
}
