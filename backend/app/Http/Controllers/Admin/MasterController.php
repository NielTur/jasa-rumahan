<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Pola yang sama untuk semua CRUD data master (A-2, A-3): daftar, tambah, ubah, Aktifkan/Nonaktifkan.
// TIDAK ada hapus: master yang pernah dipakai transaksi dinonaktifkan, bukan dihapus (B-12).
// Controller turunan hanya mendeskripsikan kolom tabel, isi form, dan aturan validasinya.
abstract class MasterController extends Controller
{
    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    // Awalan nama rute, contoh 'admin.service-categories'
    abstract protected function routeBase(): string;

    // Judul halaman, contoh 'Kategori Jasa'
    abstract protected function title(): string;

    // Judul kolom tabel (di luar kolom # , Status, Aksi)
    abstract protected function headers(): array;

    // Isi satu baris tabel, urutannya sama dengan headers()
    abstract protected function cells(Model $row): array;

    // Isian form: [name, label, type (text|textarea|number|select), required?, options?, step?, help?]
    abstract protected function fields(): array;

    abstract protected function rules(Request $request, ?int $id): array;

    protected function query(): Builder
    {
        return ($this->modelClass())::query();
    }

    // Pesan khusus per field, digabung dengan pesan umum di bawah
    protected function messages(): array
    {
        return [];
    }

    public function index(): View
    {
        $items = $this->query()->orderBy('id')->paginate(20);

        $rows = $items->getCollection()->map(fn($row) => [
            'id' => $row->id,
            'active' => (bool) $row->is_active,
            'cells' => $this->cells($row),
        ])->all();

        return view('admin.master.index', [
            'title' => $this->title(),
            'routeBase' => $this->routeBase(),
            'headers' => $this->headers(),
            'rows' => $rows,
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return $this->formView(null);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            $this->rules($request, null),
            $this->allMessages(),
            $this->attributeNames()
        );
        $data['is_active'] = true; // data baru langsung aktif

        ($this->modelClass())::create($data);

        return redirect()->route($this->routeBase() . '.index')
            ->with('success', "Data {$this->title()} berhasil ditambahkan.");
    }

    public function edit(int $id): View
    {
        return $this->formView(($this->modelClass())::findOrFail($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $row = ($this->modelClass())::findOrFail($id);

        $data = $request->validate(
            $this->rules($request, $id),
            $this->allMessages(),
            $this->attributeNames()
        );

        $row->update($data);

        return redirect()->route($this->routeBase() . '.index')
            ->with('success', "Data {$this->title()} berhasil diperbarui.");
    }

    // Pengganti hapus: balik status aktif <-> nonaktif
    public function toggle(int $id): RedirectResponse
    {
        $row = ($this->modelClass())::findOrFail($id);
        $row->update(['is_active' => !$row->is_active]);

        $kata = $row->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route($this->routeBase() . '.index')
            ->with('success', "Data {$this->title()} berhasil {$kata}.");
    }

    private function formView(?Model $row): View
    {
        return view('admin.master.form', [
            'title' => $this->title(),
            'routeBase' => $this->routeBase(),
            'fields' => $this->fields(),
            'row' => $row,
        ]);
    }

    // Nama field di pesan error memakai label di form
    private function attributeNames(): array
    {
        $hasil = [];
        foreach ($this->fields() as $f) {
            $hasil[$f['name']] = $f['label'];
        }

        return $hasil;
    }

    private function allMessages(): array
    {
        return array_merge([
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.numeric' => ':attribute maksimal :max.',
            'min.numeric' => ':attribute minimal :min.',
            'between.numeric' => ':attribute harus antara :min dan :max.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'unique' => ':attribute sudah dipakai.',
        ], $this->messages());
    }
}