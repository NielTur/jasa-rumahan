import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../service/auth_service.dart';
import '../theme/app_colors.dart';
import 'login_page.dart';
import 'register_page.dart';

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  final AuthService _authService = AuthService();

  AuthUser? _user;
  bool _isChecking = true;
  bool _showRegister = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    final token = await UserInfo.getToken();

    if (!mounted) return;

    if (token == null || token.isEmpty) {
      setState(() => _isChecking = false);
      return;
    }

    try {
      final user = await _authService.getMe();

      if (!mounted) return;

      setState(() {
        _user = user;
        _isChecking = false;
        _errorMessage = null;
      });
    } catch (_) {
      final savedToken = await UserInfo.getToken();

      if (!mounted) return;

      setState(() {
        _isChecking = false;
        _errorMessage = savedToken == null || savedToken.isEmpty
            ? null
            : 'Tidak dapat memeriksa sesi. Periksa koneksi server.';
      });
    }
  }

  void _handleLoginSuccess(AuthUser user) {
    setState(() {
      _user = user;
      _showRegister = false;
      _errorMessage = null;
    });
  }

  void _handleRegisterSuccess(AuthUser user) {
    _handleLoginSuccess(user);
  }

  Future<void> _logout() async {
    var hasError = false;

    try {
      await _authService.logout();
    } catch (_) {
      hasError = true;
    }

    if (!mounted) return;

    setState(() {
      _user = null;
      _showRegister = false;
      _errorMessage = null;
    });

    if (hasError) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Token lokal dihapus, tetapi server gagal memproses logout.',
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isChecking) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(color: AppColors.primary),
        ),
      );
    }

    if (_errorMessage != null) {
      return Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.wifi_off_rounded,
                  size: 56,
                  color: AppColors.error,
                ),
                const SizedBox(height: 16),
                Text(_errorMessage!, textAlign: TextAlign.center),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () {
                    setState(() {
                      _isChecking = true;
                      _errorMessage = null;
                    });
                    _restoreSession();
                  },
                  child: const Text('Coba Lagi'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (_user != null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Mitra Jasa Rumahan'),
          actions: [
            IconButton(
              tooltip: 'Keluar',
              onPressed: _logout,
              icon: const Icon(Icons.logout),
            ),
          ],
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.account_circle_outlined,
                  size: 72,
                  color: AppColors.primary,
                ),
                const SizedBox(height: 16),
                Text(
                  'Halo, ${_user!.fullName}',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Autentikasi Mitra berhasil. '
                  'Dashboard akan dibuat pada Stage 4.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: AppColors.textSecondary),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (_showRegister) {
      return RegisterPage(
        onRegisterSuccess: _handleRegisterSuccess,
        onBackToLogin: () {
          setState(() => _showRegister = false);
        },
      );
    }

    return LoginPage(
      onLoginSuccess: _handleLoginSuccess,
      onRegisterTap: () {
        setState(() => _showRegister = true);
      },
    );
  }
}
