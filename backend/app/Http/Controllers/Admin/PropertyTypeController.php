<?php

namespace App\Http\Controllers\Admin;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyTypeController extends MasterController
{
    protected function modelClass(): string
    {
        return PropertyType::class;
    }

    protected function routeBase(): string
    {
        return 'admin.property-types';
    }

    protected function title(): string
    {
        return 'Jenis Properti';
    }

    protected function query(): Builder
    {
        return PropertyType::withCount('sizes');
    }

    protected function headers(): array
    {
        return ['Jenis properti', 'Jumlah ukuran'];
    }

    protected function cells(Model $row): array
    {
        return [$row->type_name, $row->sizes_count];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'type_name', 'label' => 'Jenis properti', 'type' => 'text', 'required' => true],
        ];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'type_name' => ['required', 'string', 'max:100', Rule::unique('property_types', 'type_name')->ignore($id)],
        ];
    }
}