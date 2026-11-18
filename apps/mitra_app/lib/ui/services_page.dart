import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../service/mitra_services_service.dart';
import '../theme/app_colors.dart';

class ServicesPage extends StatefulWidget {
  const ServicesPage({super.key});

  @override
  State<ServicesPage> createState() => _ServicesPageState();
}

class _ServicesPageState extends State<ServicesPage> {
  final _service = MitraServicesService();

  List<MitraServiceItem> _services = [];
  final Set<int> _selectedIds = {};

  bool _isLoading = true;
  bool _isSaving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadServices();
  }

  Future<void> _loadServices() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final result = await _service.getServices();
      if (!mounted) return;

      setState(() {
        _services = result;
        _selectedIds
          ..clear()
          ..addAll(
            result.where((item) => item.selected).map((item) => item.id),
          );
      });
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Gagal mengambil katalog jasa.');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveServices() async {
    setState(() => _isSaving = true);

    try {
      final result = await _service.updateServices(_selectedIds.toList());

      if (!mounted) return;

      setState(() {
        _services = result;
        _selectedIds
          ..clear()
          ..addAll(
            result.where((item) => item.selected).map((item) => item.id),
          );
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pilihan jasa berhasil disimpan.')),
      );
    } on ApiException catch (error) {
      if (mounted) _showMessage(error.message);
    } catch (_) {
      if (mounted) _showMessage('Gagal menyimpan pilihan jasa.');
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _loadServices,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            'Pilih jasa yang ingin kamu tawarkan kepada pelanggan.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 20),
          if (_isLoading)
            const Center(child: CircularProgressIndicator())
          else if (_error != null)
            _errorView()
          else if (_services.isEmpty)
            const Center(child: Text('Belum ada jasa tersedia.'))
          else ...[
            Text(
              '${_selectedIds.length} jasa dipilih',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 12),
            ..._services.map(_serviceCard),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isSaving ? null : _saveServices,
              child: _isSaving
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Simpan Pilihan Jasa'),
            ),
          ],
        ],
      ),
    );
  }

  Widget _serviceCard(MitraServiceItem item) {
    final selected = _selectedIds.contains(item.id);

    return Card(
      child: CheckboxListTile(
        value: selected,
        onChanged: _isSaving
            ? null
            : (value) {
                setState(() {
                  if (value == true) {
                    _selectedIds.add(item.id);
                  } else {
                    _selectedIds.remove(item.id);
                  }
                });
              },
        title: Text(item.serviceName),
        subtitle: Text(
          '${item.durationMinutes} menit'
          '${item.description == null ? '' : '\n${item.description}'}',
        ),
        activeColor: AppColors.primary,
        controlAffinity: ListTileControlAffinity.leading,
      ),
    );
  }

  Widget _errorView() {
    return Column(
      children: [
        Text(_error!, textAlign: TextAlign.center),
        const SizedBox(height: 12),
        OutlinedButton(
          onPressed: _loadServices,
          child: const Text('Coba Lagi'),
        ),
      ],
    );
  }
}
