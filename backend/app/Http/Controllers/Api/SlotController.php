<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SlotRequest;
use App\Models\Service;
use App\Services\SchedulingService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

// GET /api/slots?service_id=&date=YYYY-MM-DD (publik)
class SlotController extends Controller
{
    public function __construct(private SchedulingService $scheduling)
    {
    }

    public function index(SlotRequest $request): JsonResponse
    {
        $service = Service::bookable()->where('id', $request->integer('service_id'))->first();
        if (!$service) {
            throw new ApiException('Jasa tidak ditemukan atau sedang tidak aktif.', 404);
        }

        $date = Carbon::createFromFormat('Y-m-d', $request->input('date'), config('app.timezone'))->startOfDay();

        return ApiResponse::success($this->scheduling->slotsFor($service, $date));
    }
}