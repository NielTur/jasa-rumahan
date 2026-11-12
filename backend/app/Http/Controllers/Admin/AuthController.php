<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Login admin lewat session (guard web). Admin ditolak login lewat API (CR-05), jadi pintunya di sini.
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'phone' => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            ['required' => ':attribute wajib diisi.'],
            ['phone' => 'Nomor telepon', 'password' => 'Password']
        );

        // Hanya akun ber-role admin yang aktif yang boleh masuk. Tanpa remember-me (tidak ada kolomnya).
        $berhasil = Auth::attempt([
            'phone' => PhoneNormalizer::normalize($data['phone']),
            'password' => $data['password'],
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        if (!$berhasil) {
            // Pesan sengaja sama untuk semua sebab gagal (tidak membocorkan nomor terdaftar atau tidak)
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Nomor telepon atau password salah.']);
        }

        $request->session()->regenerate(); // cegah session fixation

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}