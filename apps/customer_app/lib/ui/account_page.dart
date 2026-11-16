import 'package:flutter/material.dart';
import 'package:core/core.dart';

import '../service/auth_service.dart';
import '../theme/app_colors.dart';
import 'auth/login_page.dart';
import 'auth/register_page.dart';

class AccountPage extends StatefulWidget {
  const AccountPage({super.key});

  @override
  State<AccountPage> createState() => _AccountPageState();
}

class _AccountPageState extends State<AccountPage> {
  final _authService = AuthService();

  CustomerProfile? _profile;

  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final token = await UserInfo.getToken();

      if (token == null || token.isEmpty) {
        if (!mounted) return;

        setState(() {
          _profile = null;
          _isLoading = false;
        });

        return;
      }

      final profile = await _authService.getProfile();

      if (!mounted) return;

      setState(() {
        _profile = profile;
        _isLoading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;

      if (e.statusCode == 401 || e.statusCode == 403) {
        await UserInfo.clearToken();

        setState(() {
          _profile = null;
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = e.message;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _errorMessage = 'Tidak dapat mengambil data profil.';
        _isLoading = false;
      });
    }
  }

  Future<void> _openLogin() async {
    final result = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const LoginPage()),
    );

    if (result == true && mounted) {
      await _loadProfile();
    }
  }

  Future<void> _openRegister() async {
    final result = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const RegisterPage()),
    );

    if (result == true && mounted) {
      await _loadProfile();
    }
  }

  Future<void> _logout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Logout'),
          content: const Text('Apakah kamu yakin ingin keluar dari akun?'),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(dialogContext, false);
              },
              child: const Text('Batal'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pop(dialogContext, true);
              },
              child: const Text('Logout'),
            ),
          ],
        );
      },
    );

    if (confirmed != true) return;

    try {
      await _authService.logout();
    } catch (e) {
      // Token lokal tetap dihapus oleh AuthService.logout().
    }

    if (!mounted) return;

    setState(() {
      _profile = null;
    });

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(const SnackBar(content: Text('Kamu berhasil logout.')));
  }

  Widget _buildGuestView() {
    return Column(
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                const CircleAvatar(
                  radius: 35,
                  backgroundColor: AppColors.mintTint,
                  child: Icon(
                    Icons.person_outline,
                    size: 38,
                    color: AppColors.forestDeep,
                  ),
                ),

                const SizedBox(height: 16),

                Text(
                  'Pengguna Tamu',
                  style: Theme.of(
                    context,
                  ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                ),

                const SizedBox(height: 8),

                const Text(
                  'Masuk atau buat akun untuk menyimpan informasi profil '
                  'dan melihat riwayat pesanan.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: AppColors.mutedStone),
                ),

                const SizedBox(height: 22),

                ElevatedButton(
                  onPressed: _openLogin,
                  child: const Text('Login'),
                ),

                const SizedBox(height: 10),

                OutlinedButton(
                  onPressed: _openRegister,
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(48),
                    foregroundColor: AppColors.forestDeep,
                  ),
                  child: const Text('Buat Akun Baru'),
                ),
              ],
            ),
          ),
        ),

        const SizedBox(height: 24),

        _buildMenu(icon: Icons.help_outline, title: 'Pusat Bantuan'),

        _buildMenu(icon: Icons.info_outline, title: 'Tentang Aplikasi'),
      ],
    );
  }

  Widget _buildProfileView() {
    final profile = _profile!;

    return Column(
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                const CircleAvatar(
                  radius: 35,
                  backgroundColor: AppColors.mintTint,
                  child: Icon(
                    Icons.person,
                    size: 38,
                    color: AppColors.forestDeep,
                  ),
                ),

                const SizedBox(height: 16),

                Text(
                  profile.fullName,
                  style: Theme.of(
                    context,
                  ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                ),

                const SizedBox(height: 5),

                Text(
                  profile.phone,
                  style: const TextStyle(color: AppColors.mutedStone),
                ),

                if (profile.email != null && profile.email!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    profile.email!,
                    style: const TextStyle(color: AppColors.mutedStone),
                  ),
                ],

                const SizedBox(height: 16),

                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 7,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.mintTint,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'Customer',
                    style: TextStyle(
                      color: AppColors.forestDeep,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),

        const SizedBox(height: 24),

        _buildMenu(icon: Icons.person_outline, title: 'Informasi Profil'),

        _buildMenu(icon: Icons.location_on_outlined, title: 'Alamat Tersimpan'),

        _buildMenu(icon: Icons.receipt_long_outlined, title: 'Riwayat Pesanan'),

        _buildMenu(icon: Icons.help_outline, title: 'Pusat Bantuan'),

        const SizedBox(height: 20),

        OutlinedButton.icon(
          onPressed: _logout,
          icon: const Icon(Icons.logout),
          label: const Text('Logout'),
          style: OutlinedButton.styleFrom(
            foregroundColor: AppColors.coralDanger,
            minimumSize: const Size.fromHeight(48),
          ),
        ),
      ],
    );
  }

  Widget _buildMenu({required IconData icon, required String title}) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, color: AppColors.emeraldActive),
      title: Text(title),
      trailing: const Icon(Icons.chevron_right_rounded),
      onTap: () {},
    );
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: RefreshIndicator(
        onRefresh: _loadProfile,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(
              'Akun',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                color: AppColors.forestDeep,
                fontWeight: FontWeight.w800,
              ),
            ),

            const SizedBox(height: 24),

            if (_isLoading)
              const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (_errorMessage != null)
              Column(
                children: [
                  const Icon(
                    Icons.cloud_off_outlined,
                    size: 45,
                    color: AppColors.mutedStone,
                  ),
                  const SizedBox(height: 12),
                  Text(_errorMessage!, textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: _loadProfile,
                    child: const Text('Coba Lagi'),
                  ),
                ],
              )
            else if (_profile == null)
              _buildGuestView()
            else
              _buildProfileView(),
          ],
        ),
      ),
    );
  }
}
