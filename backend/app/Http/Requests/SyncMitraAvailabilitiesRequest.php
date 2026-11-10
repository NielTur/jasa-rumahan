<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Body (replace-all, array kosong = hapus semua jadwal):
// { "availabilities": [ { "day_of_week": 1, "start_time": "08:00", "end_time": "16:00", "is_active": true } ] }
class SyncMitraAvailabilitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jam = 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'; // format ketat HH:MM

        return [
            'availabilities' => ['present', 'array', 'max:50'],
            'availabilities.*.day_of_week' => ['required', 'integer', 'between:0,6'], // 0 = Minggu (D-09)
            'availabilities.*.start_time' => ['required', 'string', $jam],
            'availabilities.*.end_time' => ['required', 'string', $jam],
            'availabilities.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'availabilities.present' => 'Daftar jadwal wajib dikirim (boleh kosong).',
            'availabilities.array' => 'Daftar jadwal harus berupa array.',
            'availabilities.max' => 'Jumlah jadwal terlalu banyak.',
            'availabilities.*.day_of_week.required' => 'Hari wajib diisi.',
            'availabilities.*.day_of_week.integer' => 'Hari harus berupa angka 0-6.',
            'availabilities.*.day_of_week.between' => 'Hari harus 0 (Minggu) sampai 6 (Sabtu).',
            'availabilities.*.start_time.required' => 'Jam mulai wajib diisi.',
            'availabilities.*.start_time.regex' => 'Jam mulai harus berformat HH:MM, contoh 08:00.',
            'availabilities.*.end_time.required' => 'Jam selesai wajib diisi.',
            'availabilities.*.end_time.regex' => 'Jam selesai harus berformat HH:MM, contoh 16:00.',
            'availabilities.*.is_active.boolean' => 'Status jadwal harus true atau false.',
        ];
    }

    // Aturan lintas-baris dicek manual (lebih mudah dijelaskan daripada rule bawaan berbintang):
    // jam selesai > jam mulai, di dalam jam operasional (D-02), dan tidak overlap di hari yang sama.
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return; // format dasar saja sudah salah, jangan numpuk error
            }

            $slots = collect(config('marketplace.slots'));
            $buka = $slots->min('start'); // 08:00
            $tutup = $slots->max('end');  // 20:00

            $perHari = [];
            foreach ($this->input('availabilities', []) as $i => $row) {
                if ($row['end_time'] <= $row['start_time']) {
                    $validator->errors()->add("availabilities.$i.end_time", 'Jam selesai harus lebih besar dari jam mulai.');
                    continue;
                }
                if ($row['start_time'] < $buka || $row['end_time'] > $tutup) {
                    $validator->errors()->add("availabilities.$i.start_time", "Jadwal harus di dalam jam operasional {$buka}-{$tutup}.");
                    continue;
                }
                $perHari[(int) $row['day_of_week']][] = [
                    'i' => $i,
                    'start' => $row['start_time'],
                    'end' => $row['end_time'],
                ];
            }

            // Urutkan per hari, lalu pantau jam selesai terjauh. Mulai < selesai terjauh = overlap.
            foreach ($perHari as $list) {
                usort($list, fn($a, $b) => strcmp($a['start'], $b['start']));
                $selesaiTerjauh = '';
                foreach ($list as $item) {
                    if ($item['start'] < $selesaiTerjauh) {
                        $validator->errors()->add("availabilities.{$item['i']}.start_time", 'Jadwal bertabrakan dengan jadwal lain di hari yang sama.');
                    }
                    $selesaiTerjauh = max($selesaiTerjauh, $item['end']);
                }
            }
        });
    }
}