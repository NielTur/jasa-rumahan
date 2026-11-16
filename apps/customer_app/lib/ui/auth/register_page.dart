import 'package:flutter/material.dart';
import 'package:core/core.dart';

import '../../service/auth_service.dart';
import '../../theme/app_colors.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();

  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  final _addressLabelController = TextEditingController(text: 'Rumah');
  final _addressController = TextEditingController();
  final _cityController = TextEditingController();
  final _provinceController = TextEditingController();
  final _postalCodeController = TextEditingController();

  final _authService = AuthService();

  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _addressLabelController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _provinceController.dispose();
    _postalCodeController.dispose();

    super.dispose();
  }

  String? _requiredValidator(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Bagian ini wajib diisi.';
    }

    return null;
  }

  Widget _buildField({
    required String label,
    required TextEditingController controller,
    required String hint,
    IconData? icon,
    TextInputType keyboardType = TextInputType.text,
    String? Function(String?)? validator,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          maxLines: maxLines,
          validator: validator,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: icon != null ? Icon(icon) : null,
            alignLabelWithHint: maxLines > 1,
          ),
        ),
      ],
    );
  }

  Future<void> _register() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      await _authService.register(
        fullName: _nameController.text.trim(),
        phone: _phoneController.text.trim(),
        password: _passwordController.text,
        passwordConfirmation: _confirmPasswordController.text,
        addressLabel: _addressLabelController.text.trim(),
        fullAddress: _addressController.text.trim(),
        city: _cityController.text.trim(),
        province: _provinceController.text.trim(),
        postalCode: _postalCodeController.text.trim(),
      );

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Registrasi berhasil. Selamat datang!')),
      );

      Navigator.pop(context, true);
    } on ApiException catch (e) {
      if (!mounted) return;

      final validationMessage = e.errors.values
          .expand((messages) => messages)
          .join('\n');

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            validationMessage.isNotEmpty ? validationMessage : e.message,
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Terjadi kesalahan. Silakan coba lagi.')),
      );
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Widget _buildPasswordField({
    required String label,
    required TextEditingController controller,
    required bool obscureText,
    required VoidCallback onToggle,
    required String hint,
    required String? Function(String?) validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          obscureText: obscureText,
          validator: validator,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: const Icon(Icons.lock_outline),
            suffixIcon: IconButton(
              onPressed: onToggle,
              icon: Icon(
                obscureText
                    ? Icons.visibility_outlined
                    : Icons.visibility_off_outlined,
              ),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Registrasi Customer')),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(24),
            children: [
              Text(
                'Buat Akun Baru',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: AppColors.forestDeep,
                ),
              ),

              const SizedBox(height: 8),

              const Text(
                'Lengkapi data berikut untuk membuat akun JasaRumahan.',
                style: TextStyle(color: AppColors.mutedStone),
              ),

              const SizedBox(height: 28),

              _buildField(
                label: 'Nama Lengkap',
                controller: _nameController,
                hint: 'Masukkan nama lengkap',
                icon: Icons.person_outline,
                validator: _requiredValidator,
              ),

              const SizedBox(height: 18),

              _buildField(
                label: 'Nomor HP',
                controller: _phoneController,
                hint: 'Contoh: 081234567890',
                icon: Icons.phone_outlined,
                keyboardType: TextInputType.phone,
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Nomor HP wajib diisi.';
                  }

                  if (!RegExp(r'^08[0-9]{8,12}$').hasMatch(value.trim())) {
                    return 'Format nomor HP tidak valid.';
                  }

                  return null;
                },
              ),

              const SizedBox(height: 18),

              _buildPasswordField(
                label: 'Password',
                controller: _passwordController,
                obscureText: _obscurePassword,
                onToggle: () {
                  setState(() {
                    _obscurePassword = !_obscurePassword;
                  });
                },
                hint: 'Minimal 8 karakter',
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Password wajib diisi.';
                  }

                  if (value.length < 8) {
                    return 'Password minimal 8 karakter.';
                  }

                  return null;
                },
              ),

              const SizedBox(height: 18),

              _buildPasswordField(
                label: 'Konfirmasi Password',
                controller: _confirmPasswordController,
                obscureText: _obscureConfirmPassword,
                onToggle: () {
                  setState(() {
                    _obscureConfirmPassword = !_obscureConfirmPassword;
                  });
                },
                hint: 'Ulangi password',
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Konfirmasi password wajib diisi.';
                  }

                  if (value != _passwordController.text) {
                    return 'Password tidak sama.';
                  }

                  return null;
                },
              ),

              const SizedBox(height: 28),

              const Divider(),

              const SizedBox(height: 16),

              Text(
                'Alamat Customer',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: AppColors.forestDeep,
                ),
              ),

              const SizedBox(height: 18),

              _buildField(
                label: 'Label Alamat',
                controller: _addressLabelController,
                hint: 'Contoh: Rumah, Kantor',
                icon: Icons.label_outline,
                validator: _requiredValidator,
              ),

              const SizedBox(height: 18),

              _buildField(
                label: 'Alamat Lengkap',
                controller: _addressController,
                hint: 'Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan',
                icon: Icons.location_on_outlined,
                maxLines: 3,
                validator: _requiredValidator,
              ),

              const SizedBox(height: 18),

              _buildField(
                label: 'Kota / Kabupaten (Opsional)',
                controller: _cityController,
                hint: 'Masukkan kota atau kabupaten',
                icon: Icons.location_city_outlined,
              ),

              const SizedBox(height: 18),

              _buildField(
                label: 'Provinsi (Opsional)',
                controller: _provinceController,
                hint: 'Masukkan provinsi',
                icon: Icons.map_outlined,
              ),

              const SizedBox(height: 18),

              _buildField(
                label: 'Kode Pos (Opsional)',
                controller: _postalCodeController,
                hint: 'Contoh: 17212',
                icon: Icons.markunread_mailbox_outlined,
                keyboardType: TextInputType.number,
              ),

              const SizedBox(height: 30),

              ElevatedButton(
                onPressed: _isLoading ? null : _register,
                child: _isLoading
                    ? const SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Text('Daftar Sekarang'),
              ),

              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}
