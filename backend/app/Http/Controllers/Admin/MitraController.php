<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MitraController extends Controller
{
    public function __construct(private AdminAccountService $accounts)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.mitra.index', [
            'mitras' => $this->accounts->mitra($filters['status'] ?? null, $filters['q'] ?? null, 20),
            'filters' => $filters,
        ]);
    }

    public function updateVerification(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(
            ['verification_status' => ['required', 'in:approved,rejected']],
            ['required' => 'Status verifikasi wajib diisi.', 'in' => 'Status verifikasi tidak valid.']
        );

        $mitra = $this->accounts->setVerification($id, $data['verification_status']);

        $kata = $mitra->verification_status === 'approved' ? 'disetujui' : 'ditolak';

        return back(302, [], route('admin.mitra.index'))
            ->with('success', "Mitra {$mitra->user->full_name} berhasil {$kata}.");
    }
}