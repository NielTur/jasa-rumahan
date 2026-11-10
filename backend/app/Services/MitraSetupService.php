<?php

namespace App\Services;

use App\Models\MitraProfile;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Pengaturan akun mitra: profil, jasa yang ditawarkan, jadwal operasional.
// Controller tipis, semua logika di sini (B-1).
class MitraSetupService
{
    public function updateProfile(MitraProfile $mitra, array $data): MitraProfile
    {
        $mitra->update($data);

        return $mitra->refresh()->load('user');
    }

    // Semua jasa katalog yang aktif + flag `selected` (mitra memilih jasa itu dan statusnya active)
    public function catalogWithSelection(MitraProfile $mitra): Collection
    {
        $aktif = $mitra->services()
            ->wherePivot('status', 'active')
            ->pluck('services.id')
            ->all();

        return Service::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->each(fn($s) => $s->selected = in_array($s->id, $aktif));
    }

    // Sinkronisasi jasa: yang dipilih jadi active, yang dilepas jadi inactive (tidak dihapus,
    // supaya pasangan mitra+jasa tetap unik dan riwayatnya aman).
    public function syncServices(MitraProfile $mitra, array $serviceIds): Collection
    {
        $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));

        DB::transaction(function () use ($mitra, $serviceIds) {
            // Kunci baris mitra supaya dua PUT bersamaan tidak sama-sama insert pasangan yang sama (UNIQUE)
            MitraProfile::whereKey($mitra->id)->lockForUpdate()->first();

            // [service_id => 'active'|'inactive'] kondisi sekarang
            $sekarang = $mitra->services()->get()
                ->mapWithKeys(fn($s) => [$s->id => $s->pivot->status]);

            foreach ($serviceIds as $id) {
                if (!$sekarang->has($id)) {
                    $mitra->services()->attach($id, ['status' => 'active']);
                } elseif ($sekarang[$id] !== 'active') {
                    $mitra->services()->updateExistingPivot($id, ['status' => 'active']);
                }
            }

            foreach ($sekarang as $id => $status) {
                if ($status === 'active' && !in_array($id, $serviceIds)) {
                    $mitra->services()->updateExistingPivot($id, ['status' => 'inactive']);
                }
            }
        });

        return $this->catalogWithSelection($mitra);
    }

    public function availabilities(MitraProfile $mitra): Collection
    {
        return $mitra->availabilities()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    // Replace-all: hapus jadwal lama lalu isi ulang sesuai kiriman. Jadwal bukan master yang
    // dipakai FK transaksi, jadi aman dihapus (B-12 tidak berlaku).
    public function replaceAvailabilities(MitraProfile $mitra, array $rows): Collection
    {
        DB::transaction(function () use ($mitra, $rows) {
            MitraProfile::whereKey($mitra->id)->lockForUpdate()->first();

            $mitra->availabilities()->delete();

            foreach ($rows as $row) {
                $mitra->availabilities()->forceCreate([
                    'day_of_week' => (int) $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'is_active' => $row['is_active'] ?? true,
                ]);
            }
        });

        return $this->availabilities($mitra);
    }
}