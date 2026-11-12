<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends MasterController
{
    protected function modelClass(): string
    {
        return Service::class;
    }

    protected function routeBase(): string
    {
        return 'admin.services';
    }

    protected function title(): string
    {
        return 'Jasa';
    }

    protected function query(): Builder
    {
        return Service::with('category');
    }

    protected function headers(): array
    {
        return ['Nama jasa', 'Kategori', 'Durasi (menit)'];
    }

    protected function cells(Model $row): array
    {
        return [$row->service_name, $row->category->category_name, $row->duration_minutes ?? '-'];
    }

    protected function fields(): array
    {
        $kategori = ServiceCategory::orderBy('id')->get()
            ->mapWithKeys(fn($c) => [$c->id => $c->category_name . ($c->is_active ? '' : ' (nonaktif)')])
            ->all();

        return [
            ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'required' => true, 'options' => $kategori],
            ['name' => 'service_name', 'label' => 'Nama jasa', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            [
                'name' => 'duration_minutes',
                'label' => 'Durasi (menit)',
                'type' => 'number',
                'step' => '1',
                'help' => 'Boleh dikosongkan (dianggap 120 menit). Isi 15 sampai 480.',
            ],
        ];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'service_name' => ['required', 'string', 'max:100', Rule::unique('services', 'service_name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['nullable', 'integer', 'between:15,480'],
        ];
    }
}