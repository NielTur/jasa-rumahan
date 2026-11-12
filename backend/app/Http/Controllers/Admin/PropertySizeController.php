<?php

namespace App\Http\Controllers\Admin;

use App\Models\PropertySize;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PropertySizeController extends MasterController
{
    protected function modelClass(): string
    {
        return PropertySize::class;
    }

    protected function routeBase(): string
    {
        return 'admin.property-sizes';
    }

    protected function title(): string
    {
        return 'Ukuran Properti';
    }

    protected function query(): Builder
    {
        return PropertySize::with('propertyType');
    }

    protected function headers(): array
    {
        return ['Nama ukuran', 'Jenis properti', 'Luas'];
    }

    protected function cells(Model $row): array
    {
        $min = $row->min_area;
        $max = $row->max_area;
        $luas = ($min === null && $max === null) ? '-' : ($min ?? '?') . ' - ' . ($max ?? '?') . ' m²';

        return [$row->size_name, $row->propertyType->type_name, $luas];
    }

    protected function fields(): array
    {
        $jenis = PropertyType::orderBy('id')->get()
            ->mapWithKeys(fn($t) => [$t->id => $t->type_name . ($t->is_active ? '' : ' (nonaktif)')])
            ->all();

        return [
            ['name' => 'property_type_id', 'label' => 'Jenis properti', 'type' => 'select', 'required' => true, 'options' => $jenis],
            ['name' => 'size_name', 'label' => 'Nama ukuran', 'type' => 'text', 'required' => true],
            ['name' => 'min_area', 'label' => 'Luas minimum (m²)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'max_area', 'label' => 'Luas maksimum (m²)', 'type' => 'number', 'step' => '0.01'],
        ];
    }

    protected function rules(Request $request, ?int $id): array
    {
        $luas = ['nullable', 'numeric', 'min:0', 'max:100000'];

        // Luas maksimum dibandingkan dengan minimum hanya kalau minimum diisi
        $luasMaks = $request->filled('min_area') ? array_merge($luas, ['gte:min_area']) : $luas;

        return [
            'property_type_id' => ['required', 'integer', 'exists:property_types,id'],
            'size_name' => ['required', 'string', 'max:100'],
            'min_area' => $luas,
            'max_area' => $luasMaks,
        ];
    }

    protected function messages(): array
    {
        return [
            'max_area.gte' => 'Luas maksimum harus lebih besar atau sama dengan luas minimum.',
        ];
    }
}