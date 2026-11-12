<?php

namespace App\Http\Controllers\Admin;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends MasterController
{
    protected function modelClass(): string
    {
        return ServiceCategory::class;
    }

    protected function routeBase(): string
    {
        return 'admin.service-categories';
    }

    protected function title(): string
    {
        return 'Kategori Jasa';
    }

    protected function query(): Builder
    {
        return ServiceCategory::withCount('services');
    }

    protected function headers(): array
    {
        return ['Nama kategori', 'Deskripsi', 'Jumlah jasa'];
    }

    protected function cells(Model $row): array
    {
        return [$row->category_name, Str::limit((string) $row->description, 60), $row->services_count];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'category_name', 'label' => 'Nama kategori', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
        ];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'category_name' => ['required', 'string', 'max:100', Rule::unique('service_categories', 'category_name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}