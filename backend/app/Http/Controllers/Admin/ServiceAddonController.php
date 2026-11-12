<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use App\Models\ServiceAddon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ServiceAddonController extends MasterController
{
    protected function modelClass(): string
    {
        return ServiceAddon::class;
    }

    protected function routeBase(): string
    {
        return 'admin.service-addons';
    }

    protected function title(): string
    {
        return 'Add-on';
    }

    protected function query(): Builder
    {
        return ServiceAddon::with('service');
    }

    protected function headers(): array
    {
        return ['Nama add-on', 'Jasa utama', 'Harga'];
    }

    protected function cells(Model $row): array
    {
        return [
            $row->addon_name,
            $row->service->service_name,
            'Rp ' . number_format($row->price, 0, ',', '.'),
        ];
    }

    protected function fields(): array
    {
        $jasa = Service::orderBy('id')->get()
            ->mapWithKeys(fn($s) => [$s->id => $s->service_name . ($s->is_active ? '' : ' (nonaktif)')])
            ->all();

        return [
            ['name' => 'service_id', 'label' => 'Jasa utama', 'type' => 'select', 'required' => true, 'options' => $jasa],
            ['name' => 'addon_name', 'label' => 'Nama add-on', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ['name' => 'price', 'label' => 'Harga (Rp)', 'type' => 'number', 'step' => '1', 'required' => true],
        ];
    }

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'addon_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['bail', 'required', 'integer', 'between:1,100000000'],
        ];
    }
}