import 'package:flutter/material.dart';
import 'package:core/core.dart';

import '../service/booking_service.dart';
import '../theme/app_colors.dart';

class BookingPage extends StatefulWidget {
  final ServiceDetail detail;

  const BookingPage({super.key, required this.detail});

  @override
  State<BookingPage> createState() => _BookingPageState();
}

class _BookingPageState extends State<BookingPage> {
  final _formKey = GlobalKey<FormState>();
  final _bookingService = BookingService();

  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _addressNotesController = TextEditingController();
  final _customerNoteController = TextEditingController();

  CustomerProfile? _profile;
  OrderQuote? _quote;

  int? _selectedGroupId;
  ServicePriceSize? _selectedSize;
  final Map<int, int> _addonQuantities = {};

  DateTime _selectedDate = DateUtils.dateOnly(DateTime.now());
  List<BookingSlot> _slots = [];
  BookingSlot? _selectedSlot;

  bool _loadingProfile = true;
  bool _loadingSlots = false;
  bool _loadingQuote = false;
  bool _submitting = false;

  String? _slotError;
  String? _profileError;
  int _quoteRequestId = 0;

  List<ServicePriceGroup> get _priceGroups => widget.detail.prices;

  ServicePriceGroup? get _selectedGroup {
    for (final group in _priceGroups) {
      if (group.propertyTypeId == _selectedGroupId) {
        return group;
      }
    }
    return null;
  }

  @override
  void initState() {
    super.initState();

    if (_priceGroups.isNotEmpty) {
      final firstGroup = _priceGroups.first;
      _selectedGroupId = firstGroup.propertyTypeId;

      if (firstGroup.sizes.isNotEmpty) {
        _selectedSize = firstGroup.sizes.first;
      }
    }

    _loadProfile();
    _loadSlots();

    if (_selectedSize != null) {
      _refreshQuote();
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _addressNotesController.dispose();
    _customerNoteController.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    try {
      final profile = await _bookingService.getCustomerProfile();

      if (!mounted) return;

      setState(() {
        _profile = profile;
        _loadingProfile = false;

        if (profile != null) {
          _nameController.text = profile.fullName;
          _phoneController.text = profile.phone;
        }
      });
    } on ApiException catch (error) {
      if (!mounted) return;

      setState(() {
        _loadingProfile = false;
        _profileError = error.message;
      });
    } catch (_) {
      if (!mounted) return;

      setState(() {
        _loadingProfile = false;
        _profileError = 'Profil customer gagal dimuat.';
      });
    }
  }

  Future<void> _loadSlots() async {
    setState(() {
      _loadingSlots = true;
      _slotError = null;
      _selectedSlot = null;
      _slots = [];
    });

    try {
      final slots = await _bookingService.getSlots(
        serviceId: widget.detail.id,
        date: _selectedDate,
      );

      if (!mounted) return;

      setState(() {
        _slots = slots.where((slot) => slot.available).toList();
        _loadingSlots = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;

      setState(() {
        _slotError = error.message;
        _loadingSlots = false;
      });
    } catch (_) {
      if (!mounted) return;

      setState(() {
        _slotError = 'Gagal memuat jadwal. Silakan coba lagi.';
        _loadingSlots = false;
      });
    }
  }

  Future<void> _refreshQuote() async {
    final size = _selectedSize;
    final requestId = ++_quoteRequestId;

    if (size == null) {
      setState(() {
        _quote = null;
        _loadingQuote = false;
      });
      return;
    }

    setState(() {
      _loadingQuote = true;
    });

    try {
      final quote = await _bookingService.getQuote(
        serviceId: widget.detail.id,
        propertySizeId: size.propertySizeId,
        addonQuantities: Map<int, int>.from(_addonQuantities),
      );

      if (!mounted || requestId != _quoteRequestId) return;

      setState(() {
        _quote = quote;
        _loadingQuote = false;
      });
    } on ApiException catch (error) {
      if (!mounted || requestId != _quoteRequestId) return;

      setState(() {
        _quote = null;
        _loadingQuote = false;
      });

      _showMessage(error.message);
    } catch (_) {
      if (!mounted || requestId != _quoteRequestId) return;

      setState(() {
        _quote = null;
        _loadingQuote = false;
      });

      _showMessage('Gagal menghitung harga pesanan.');
    }
  }

  void _onGroupChanged(int? groupId) {
    if (groupId == null) return;

    final group = _priceGroups.firstWhere(
      (item) => item.propertyTypeId == groupId,
    );

    setState(() {
      _selectedGroupId = groupId;
      _selectedSize = group.sizes.isEmpty ? null : group.sizes.first;
    });

    _refreshQuote();
  }

  void _onSizeChanged(int? sizeId) {
    if (sizeId == null) return;

    final group = _selectedGroup;
    if (group == null) return;

    final sizes = group.sizes.where((item) => item.propertySizeId == sizeId);

    if (sizes.isEmpty) return;

    setState(() {
      _selectedSize = sizes.first;
    });

    _refreshQuote();
  }

  void _changeAddonQuantity(ServiceAddon addon, int change) {
    final current = _addonQuantities[addon.id] ?? 0;
    final updated = (current + change).clamp(0, 99);

    setState(() {
      if (updated == 0) {
        _addonQuantities.remove(addon.id);
      } else {
        _addonQuantities[addon.id] = updated;
      }
    });

    _refreshQuote();
  }

  Future<void> _pickDate() async {
    final today = DateUtils.dateOnly(DateTime.now());

    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate.isBefore(today) ? today : _selectedDate,
      firstDate: today,
      lastDate: today.add(const Duration(days: 90)),
    );

    if (picked == null || !mounted) return;

    setState(() {
      _selectedDate = DateUtils.dateOnly(picked);
    });

    _loadSlots();
  }

  void _showMessage(String message) {
    if (!mounted) return;

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _submitOrder() async {
    if (!_formKey.currentState!.validate()) return;

    if (_selectedSize == null) {
      _showMessage('Pilih ukuran properti terlebih dahulu.');
      return;
    }

    if (_selectedSlot == null) {
      _showMessage('Pilih jadwal pengerjaan terlebih dahulu.');
      return;
    }

    setState(() {
      _submitting = true;
    });

    try {
      final result = await _bookingService.createOrder(
        serviceId: widget.detail.id,
        propertySizeId: _selectedSize!.propertySizeId,
        addonQuantities: Map<int, int>.from(_addonQuantities),
        scheduledAt: _selectedSlot!.start,
        customerName: _nameController.text,
        customerPhone: _phoneController.text,
        serviceAddress: _addressController.text,
        addressNotes: _addressNotesController.text,
        customerNote: _customerNoteController.text,
      );

      if (!mounted) return;

      setState(() {
        _submitting = false;
      });

      await showDialog<void>(
        context: context,
        barrierDismissible: false,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Pesanan berhasil dibuat'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Pesanan kamu berhasil dikirim dan sedang mencari mitra.',
              ),
              const SizedBox(height: 16),
              const Text('Kode pesanan'),
              SelectableText(
                result.orderCode,
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 17,
                ),
              ),
              const SizedBox(height: 8),
              Text('Status: ${result.orderStatus}'),
            ],
          ),
          actions: [
            FilledButton(
              onPressed: () {
                Navigator.of(dialogContext).pop();
              },
              child: const Text('Lihat Pesanan'),
            ),
          ],
        ),
      );

      if (!mounted) return;

      Navigator.of(context).pop(result);
    } on ApiException catch (error) {
      if (!mounted) return;

      setState(() {
        _submitting = false;
      });

      _showMessage(error.message);
    } catch (_) {
      if (!mounted) return;

      setState(() {
        _submitting = false;
      });

      _showMessage('Terjadi kesalahan saat membuat pesanan.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAF8),
      appBar: AppBar(
        title: const Text('Pemesanan Jasa'),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.forestDeep,
        elevation: 0,
      ),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(18, 18, 18, 28),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildServiceHeader(),
              const SizedBox(height: 22),
              _buildPropertySection(),
              const SizedBox(height: 22),
              _buildAddonSection(),
              const SizedBox(height: 22),
              _buildScheduleSection(),
              const SizedBox(height: 22),
              _buildContactSection(),
              const SizedBox(height: 22),
              _buildAddressSection(),
              const SizedBox(height: 22),
              _buildPriceSection(),
            ],
          ),
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Container(
          padding: const EdgeInsets.all(16),
          color: Colors.white,
          child: SizedBox(
            height: 52,
            child: FilledButton(
              onPressed: _submitting ? null : _submitOrder,
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.forestDeep,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              child: _submitting
                  ? const SizedBox(
                      height: 22,
                      width: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Text(
                      'Konfirmasi Pesanan',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 15,
                      ),
                    ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildServiceHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.forestDeep,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.home_repair_service_outlined,
            color: AppColors.sageFresh,
            size: 30,
          ),
          const SizedBox(height: 12),
          Text(
            widget.detail.serviceName,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Estimasi pengerjaan ${widget.detail.durationMinutes} menit',
            style: const TextStyle(color: Colors.white70),
          ),
          const SizedBox(height: 8),
          const Text(
            'Durasi merupakan estimasi dan dapat menyesuaikan kondisi di lokasi.',
            style: TextStyle(color: Colors.white70, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _buildPropertySection() {
    final group = _selectedGroup;

    return _sectionCard(
      title: '1. Detail properti',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _fieldLabel('Jenis properti'),
          DropdownButtonFormField<int>(
            initialValue: _selectedGroupId,
            isExpanded: true,
            decoration: _inputDecoration('Pilih jenis properti'),
            items: _priceGroups.map((group) {
              return DropdownMenuItem<int>(
                value: group.propertyTypeId,
                child: Text(group.typeName),
              );
            }).toList(),
            onChanged: _onGroupChanged,
          ),
          const SizedBox(height: 16),
          _fieldLabel('Ukuran properti'),
          if (group == null || group.sizes.isEmpty)
            const Text(
              'Ukuran properti belum tersedia.',
              style: TextStyle(color: AppColors.mutedStone),
            )
          else
            DropdownButtonFormField<int>(
              initialValue: _selectedSize?.propertySizeId,
              isExpanded: true,
              decoration: _inputDecoration('Pilih ukuran properti'),
              items: group.sizes.map((size) {
                return DropdownMenuItem<int>(
                  value: size.propertySizeId,
                  child: Text(size.sizeName),
                );
              }).toList(),
              onChanged: _onSizeChanged,
            ),
        ],
      ),
    );
  }

  Widget _buildAddonSection() {
    return _sectionCard(
      title: '2. Layanan tambahan',
      subtitle: 'Opsional, pilih sesuai kebutuhan.',
      child: widget.detail.addons.isEmpty
          ? const Text(
              'Tidak ada layanan tambahan untuk jasa ini.',
              style: TextStyle(color: AppColors.mutedStone),
            )
          : Column(
              children: widget.detail.addons.map((addon) {
                final quantity = _addonQuantities[addon.id] ?? 0;

                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 9),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              addon.addonName,
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            Text(
                              _formatRupiah(addon.price),
                              style: const TextStyle(
                                color: AppColors.mutedStone,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        onPressed: quantity == 0
                            ? null
                            : () => _changeAddonQuantity(addon, -1),
                        icon: const Icon(Icons.remove_circle_outline),
                      ),
                      Text(
                        '$quantity',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                      IconButton(
                        onPressed: () => _changeAddonQuantity(addon, 1),
                        icon: const Icon(Icons.add_circle_outline),
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
    );
  }

  Widget _buildScheduleSection() {
    return _sectionCard(
      title: '3. Jadwal pengerjaan',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          OutlinedButton.icon(
            onPressed: _pickDate,
            icon: const Icon(Icons.calendar_month_outlined),
            label: Text(_formatDate(_selectedDate)),
          ),
          const SizedBox(height: 14),
          if (_loadingSlots)
            const Center(child: CircularProgressIndicator())
          else if (_slotError != null)
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_slotError!, style: const TextStyle(color: Colors.red)),
                TextButton(
                  onPressed: _loadSlots,
                  child: const Text('Muat ulang jadwal'),
                ),
              ],
            )
          else if (_slots.isEmpty)
            const Text(
              'Tidak ada jadwal tersedia pada tanggal ini.',
              style: TextStyle(color: AppColors.mutedStone),
            )
          else
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _slots.map((slot) {
                final selected = _selectedSlot == slot;

                return ChoiceChip(
                  label: Text(slot.label),
                  selected: selected,
                  onSelected: (_) {
                    setState(() {
                      _selectedSlot = slot;
                    });
                  },
                  selectedColor: AppColors.mintTint,
                );
              }).toList(),
            ),
        ],
      ),
    );
  }

  Widget _buildContactSection() {
    return _sectionCard(
      title: '4. Informasi kontak',
      subtitle: _profile != null
          ? 'Data akun terisi otomatis dan tetap bisa diubah.'
          : 'Isi informasi kontak yang dapat dihubungi.',
      child: Column(
        children: [
          if (_loadingProfile)
            const LinearProgressIndicator()
          else if (_profileError != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Text(
                _profileError!,
                style: const TextStyle(
                  color: AppColors.mutedStone,
                  fontSize: 12,
                ),
              ),
            ),
          _textField(
            controller: _nameController,
            label: 'Nama lengkap',
            icon: Icons.person_outline,
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return 'Nama lengkap wajib diisi.';
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          _textField(
            controller: _phoneController,
            label: 'Nomor HP',
            icon: Icons.phone_outlined,
            keyboardType: TextInputType.phone,
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return 'Nomor HP wajib diisi.';
              }
              return null;
            },
          ),
        ],
      ),
    );
  }

  Widget _buildAddressSection() {
    return _sectionCard(
      title: '5. Alamat pengerjaan',
      subtitle: 'Alamat harus diisi untuk setiap pemesanan.',
      child: Column(
        children: [
          _textField(
            controller: _addressController,
            label: 'Alamat lengkap',
            icon: Icons.location_on_outlined,
            maxLines: 3,
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return 'Alamat lengkap wajib diisi.';
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          _textField(
            controller: _addressNotesController,
            label: 'Patokan / catatan alamat (opsional)',
            icon: Icons.signpost_outlined,
            maxLines: 2,
          ),
          const SizedBox(height: 12),
          _textField(
            controller: _customerNoteController,
            label: 'Catatan untuk mitra (opsional)',
            icon: Icons.notes_outlined,
            maxLines: 2,
          ),
        ],
      ),
    );
  }

  Widget _buildPriceSection() {
    return _sectionCard(
      title: '6. Rincian harga',
      child: _loadingQuote
          ? const Center(child: CircularProgressIndicator())
          : _quote == null
          ? const Text(
              'Pilih ukuran properti untuk melihat rincian harga.',
              style: TextStyle(color: AppColors.mutedStone),
            )
          : Column(
              children: [
                _priceRow('Harga jasa', _quote!.pricing.servicePrice),
                _priceRow(
                  'Total layanan tambahan',
                  _quote!.pricing.addonsTotal,
                ),
                const Divider(height: 24),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Total pembayaran',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                    Text(
                      _formatRupiah(_quote!.pricing.totalPrice),
                      style: const TextStyle(
                        color: AppColors.forestDeep,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                const Text(
                  'Harga dihitung oleh server berdasarkan pilihan jasa, ukuran properti, dan layanan tambahan.',
                  style: TextStyle(color: AppColors.mutedStone, fontSize: 11),
                ),
              ],
            ),
    );
  }

  Widget _sectionCard({
    required String title,
    String? subtitle,
    required Widget child,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(17),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE7ECE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: AppColors.forestDeep,
              fontSize: 16,
              fontWeight: FontWeight.w800,
            ),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 5),
            Text(
              subtitle,
              style: const TextStyle(color: AppColors.mutedStone, fontSize: 12),
            ),
          ],
          const SizedBox(height: 16),
          child,
        ],
      ),
    );
  }

  Widget _fieldLabel(String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
    );
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 13, vertical: 14),
    );
  }

  Widget _textField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    TextInputType? keyboardType,
    int maxLines = 1,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      validator: validator,
      decoration: _inputDecoration(
        label,
      ).copyWith(prefixIcon: Icon(icon), alignLabelWithHint: maxLines > 1),
    );
  }

  Widget _priceRow(String label, int amount) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [Text(label), Text(_formatRupiah(amount))],
      ),
    );
  }

  String _formatRupiah(int amount) {
    final digits = amount.toString();
    final result = StringBuffer();

    for (var index = 0; index < digits.length; index++) {
      if (index > 0 && (digits.length - index) % 3 == 0) {
        result.write('.');
      }
      result.write(digits[index]);
    }

    return 'Rp $result';
  }

  String _formatDate(DateTime date) {
    const days = [
      'Senin',
      'Selasa',
      'Rabu',
      'Kamis',
      'Jumat',
      'Sabtu',
      'Minggu',
    ];
    const months = [
      'Januari',
      'Februari',
      'Maret',
      'April',
      'Mei',
      'Juni',
      'Juli',
      'Agustus',
      'September',
      'Oktober',
      'November',
      'Desember',
    ];

    return '${days[date.weekday - 1]}, ${date.day} '
        '${months[date.month - 1]} ${date.year}';
  }
}
