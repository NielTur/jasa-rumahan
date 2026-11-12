<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\AdminAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private AdminAccountService $accounts)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'role' => ['nullable', 'in:customer,mitra,admin'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.users.index', [
            'users' => $this->accounts->users($filters, 20),
            'filters' => $filters,
        ]);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(
            ['status' => ['required', 'in:active,suspended']],
            ['required' => 'Status wajib diisi.', 'in' => 'Status tidak valid.']
        );

        try {
            $user = $this->accounts->setAccountStatus($id, $data['status']);
        } catch (ApiException $e) {
            return back(302, [], route('admin.users.index'))->with('error', $e->getMessage());
        }

        $kata = $user->account_status === 'active' ? 'diaktifkan' : 'disuspend';

        return back(302, [], route('admin.users.index'))
            ->with('success', "Akun {$user->full_name} berhasil {$kata}.");
    }
}