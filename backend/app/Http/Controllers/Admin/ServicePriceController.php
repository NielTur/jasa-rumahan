<?php

namespace App\Http\Controllers\Admin;

use App\Models\PropertySize;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Harga jasa per ukuran properti. Satu-satunya jalan mengubah harga (A-4); tanpa ubah kode.
// Perubahan harga tidak memengaruhi order lama karena harga sudah disalin sebagai snapshot (B-3).
class ServicePriceController extends MasterController
{
    protected function modelClass(): string
    {
        return ServicePrice::class;
    }

    protected function routeBase(): string
    {
        return 'admin.service-prices';
    }

    protected function title(): string
    {
        return 'Harga Jasa';
    }

    protected function query(): Builder
    {
        return ServicePrice::with(['service', 'propertySize.propertyType']);
    }

    protected function headers(): array
    {
        return ['Jasa', 'Ukuran properti', 'Harga'];
    }

    protected function cells(Model $row): array
    {
        return [
            $row->service->service_name,
            $row->propertySize->propertyType->type_name . ' - ' . $row->propertySize->size_name,
            'Rp ' . number_format($row->price, 0, ',', '.'),
        ];
    }

    protected function fields(): array
    {
        $jasa = Service::orderBy('id')->get()
            ->mapWithKeys(fn($s) => [$s->id => $s->service_name . ($s->is_active ? '' : ' (nonaktif)')])
            ->all();

        $ukuran = PropertySize::with('propertyType')->orderBy('id')->get()
            ->mapWithKeys(fn($s) => [
                $s->id => $s->propertyType->type_name . ' - ' . $s->size_name . ($s->is_active ? '' : ' (nonaktif)'),
            ])
            ->all();

        return [
            ['name' => 'service_id', 'label' => 'Jasa', 'type' => 'select', 'required' => true, 'options' => $jasa],
            ['name' => 'property_size_id', 'label' => 'Ukuran properti', 'type' => 'select', 'required' => true, 'options' => $ukuran],
            ['name' => 'price', 'label' => 'Harga (Rp)', 'type' => 'number', 'step' => '1', 'required' => true, 'help' => 'Dalam rupiah, tanpa titik atau koma.'],
        ];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            // Satu kombinasi jasa + ukuran hanya boleh punya satu baris harga
            'property_size_id' => [
                'bail',
                'required',
                'integer',
                'exists:property_sizes,id',
                Rule::unique('service_prices', 'property_size_id')
                    ->where(fn($q) => $q->where('service_id', $request->input('service_id')))
                    ->ignore($id),
            ],
            'price' => ['bail', 'required', 'integer', 'between:1,100000000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'property_size_id.unique' => 'Harga untuk kombinasi jasa dan ukuran ini sudah ada. Ubah harganya lewat tombol Edit.',
        ];
    }
}