<?php

namespace App\Services;

use App\Models\MitraProfile;
use App\Models\Service;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// Slot waktu (D-02) dan pencarian mitra yang eligible untuk satu slot.
// Dipakai /slots, POST /orders, dan nanti matching + accept (P2b/P3).
class SchedulingService
{
    /** @return array<int, array{start:string, end:string, available:bool}> */
    public function slotsFor(Service $service, Carbon $date): array
    {
        $result = [];

        foreach (config('marketplace.slots') as $slot) {
            [$start, $end] = $this->slotWindow($date, $slot);

            // available = cukup jauh dari sekarang DAN ada minimal 1 mitra eligible
            $available = $this->isFarEnough($start)
                && $this->eligibleMitraIds($service, $start, $end)->isNotEmpty();

            $result[] = [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'available' => $available,
            ];
        }

        return $result;
    }

    // Cocokkan waktu dari customer dengan salah satu slot di config. null = bukan slot valid.
    /** @return array{0: Carbon, 1: Carbon}|null */
    public function findSlot(Carbon $scheduledAt): ?array
    {
        $local = $scheduledAt->copy()->setTimezone(config('app.timezone'));
        $day = $local->copy()->startOfDay();

        foreach (config('marketplace.slots') as $slot) {
            [$start, $end] = $this->slotWindow($day, $slot);

            if ($start->equalTo($local)) {
                return [$start, $end];
            }
        }

        return null;
    }

    // D-03: slot harus mulai minimal MIN_LEAD_MINUTES dari sekarang
    public function isFarEnough(Carbon $start): bool
    {
        $minimum = Carbon::now(config('app.timezone'))->addMinutes(config('marketplace.min_lead_minutes'));

        return $start->gte($minimum);
    }

    /**
     * ID mitra yang boleh ditawari order ini (docs desain 9.3):
     * approved, tersedia, akun aktif, menyediakan jasa itu (aktif), jadwal harian mencakup slot,
     * dan tidak bentrok dengan order aktif lain.
     */
    public function eligibleMitraIds(Service $service, Carbon $start, Carbon $end): Collection
    {
        $dayOfWeek = $start->dayOfWeek; // Carbon: 0 = Minggu ... 6 = Sabtu (D-09)
        $jobEnd = $start->copy()->addMinutes($service->duration_minutes ?? 120);

        return MitraProfile::query()
            ->where('verification_status', 'approved')
            ->where('is_available', true)
            ->whereHas('user', fn($q) => $q->where('account_status', 'active'))
            ->whereHas('services', fn($q) => $q
                ->where('services.id', $service->id)
                ->where('mitra_services.status', 'active'))
            ->whereHas('availabilities', fn($q) => $q
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('start_time', '<=', $start->format('H:i:s'))
                ->where('end_time', '>=', $end->format('H:i:s')))
            // Bentrok = jendela order lain beririsan dengan jendela order ini
            ->whereDoesntHave('orders', fn($q) => $q
                ->whereIn('order_status', OrderStatus::SCHEDULE_BLOCKING)
                ->where('scheduled_at', '<', $jobEnd->format('Y-m-d H:i:s'))
                ->whereRaw(
                    'DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 120) MINUTE) > ?',
                    [$start->format('Y-m-d H:i:s')]
                ))
            ->pluck('id');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function slotWindow(Carbon $day, array $slot): array
    {
        return [
            $day->copy()->setTimeFromTimeString($slot['start']),
            $day->copy()->setTimeFromTimeString($slot['end']),
        ];
    }
}