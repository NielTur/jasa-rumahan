import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../service/mitra_profile_service.dart';
import '../theme/app_colors.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  final _service = MitraProfileService();
  final _bioController = TextEditingController();
  final _areaController = TextEditingController();

  MitraProfile? _profile;
  final List<_AvailabilityDraft> _rows = [];

  bool _isLoading = true;
  bool _isSavingProfile = false;
  bool _isSavingSchedule = false;
  bool _isAvailable = false;
  String? _error;

  static const _days = [
    'Minggu',
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
  ];

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void dispose() {
    _bioController.dispose();
    _areaController.dispose();
    for (final row in _rows) {
      row.dispose();
    }
    super.dispose();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final results = await Future.wait([
        _service.getProfile(),
        _service.getAvailabilities(),
      ]);

      if (!mounted) return;

      final profile = results[0] as MitraProfile;
      final schedules = results[1] as List<MitraAvailability>;

      setState(() {
        _profile = profile;
        _bioController.text = profile.bio ?? '';
        _areaController.text = profile.serviceArea ?? '';
        _isAvailable = profile.isAvailable;

        for (final row in _rows) {
          row.dispose();
        }
        _rows.clear();

        if (schedules.isEmpty) {
          for (var day = 0; day < 7; day++) {
            _rows.add(_AvailabilityDraft(day));
          }
        } else {
          _rows.addAll(schedules.map(_AvailabilityDraft.fromModel));
        }
      });
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Gagal mengambil profil Mitra.');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveProfile() async {
    setState(() => _isSavingProfile = true);

    try {
      final result = await _service.updateProfile(
        bio: _bioController.text.trim(),
        serviceArea: _areaController.text.trim(),
        isAvailable: _isAvailable,
      );

      if (!mounted) return;
      setState(() => _profile = result);
      _message('Profil berhasil diperbarui.');
    } on ApiException catch (error) {
      if (mounted) _message(error.message);
    } catch (_) {
      if (mounted) _message('Gagal memperbarui profil.');
    } finally {
      if (mounted) setState(() => _isSavingProfile = false);
    }
  }

  Future<void> _saveSchedule() async {
    final validation = _validateSchedule();
    if (validation != null) {
      _message(validation);
      return;
    }

    setState(() => _isSavingSchedule = true);

    try {
      final items = _rows.map((row) {
        return MitraAvailability(
          dayOfWeek: row.day,
          startTime: row.start.text,
          endTime: row.end.text,
          isActive: row.isActive,
        );
      }).toList();

      final saved = await _service.updateAvailabilities(items);
      if (!mounted) return;

      setState(() {
        for (final row in _rows) {
          row.dispose();
        }
        _rows
          ..clear()
          ..addAll(saved.map(_AvailabilityDraft.fromModel));
      });

      _message('Jadwal operasional berhasil disimpan.');
    } on ApiException catch (error) {
      if (mounted) _message(error.message);
    } catch (_) {
      if (mounted) _message('Gagal menyimpan jadwal operasional.');
    } finally {
      if (mounted) setState(() => _isSavingSchedule = false);
    }
  }

  String? _validateSchedule() {
    final activeRows = _rows.where((row) => row.isActive).toList();

    for (final row in activeRows) {
      final start = _minutes(row.start.text);
      final end = _minutes(row.end.text);

      if (start == null || end == null) {
        return 'Format jam harus HH:MM.';
      }
      if (start < 480 || end > 1200 || end <= start) {
        return 'Jadwal harus berada dalam rentang 08:00–20:00.';
      }
    }

    for (var i = 0; i < activeRows.length; i++) {
      for (var j = i + 1; j < activeRows.length; j++) {
        final a = activeRows[i];
        final b = activeRows[j];

        if (a.day != b.day) continue;

        final aStart = _minutes(a.start.text)!;
        final aEnd = _minutes(a.end.text)!;
        final bStart = _minutes(b.start.text)!;
        final bEnd = _minutes(b.end.text)!;

        if (aStart < bEnd && bStart < aEnd) {
          return 'Jadwal ${_days[a.day]} saling bertumpuk.';
        }
      }
    }

    return null;
  }

  int? _minutes(String value) {
    final match = RegExp(
      r'^([0-1]\d|2[0-3]):([0-5]\d)$',
    ).firstMatch(value.trim());
    if (match == null) return null;
    return int.parse(match[1]!) * 60 + int.parse(match[2]!);
  }

  void _addSchedule() {
    setState(() => _rows.add(_AvailabilityDraft(1)));
  }

  void _removeSchedule(int index) {
    setState(() {
      _rows.removeAt(index).dispose();
    });
  }

  void _message(String value) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(value)));
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: _loadProfile,
              child: const Text('Coba Lagi'),
            ),
          ],
        ),
      );
    }

    if (_profile == null) {
      return const Center(child: Text('Profil tidak tersedia.'));
    }

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(20),
      children: [
        _accountCard(),
        const SizedBox(height: 20),
        _profileForm(),
        const SizedBox(height: 24),
        _scheduleSection(),
      ],
    );
  }

  Widget _accountCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _profile!.fullName,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 6),
            Text(_profile!.phone),
            const SizedBox(height: 6),
            Text(
              'Status verifikasi: ${_profile!.verificationStatus ?? '-'}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }

  Widget _profileForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Informasi Mitra', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 12),
        TextField(
          controller: _bioController,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'Bio',
            hintText: 'Ceritakan layanan yang kamu sediakan',
          ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _areaController,
          decoration: const InputDecoration(
            labelText: 'Area layanan',
            hintText: 'Contoh: Bogor dan sekitarnya',
          ),
        ),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('Tersedia menerima pesanan'),
          value: _isAvailable,
          activeTrackColor: AppColors.primary,
          onChanged: (value) => setState(() => _isAvailable = value),
        ),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _isSavingProfile ? null : _saveProfile,
            child: _isSavingProfile
                ? const CircularProgressIndicator()
                : const Text('Simpan Profil'),
          ),
        ),
      ],
    );
  }

  Widget _scheduleSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Jadwal Operasional',
          style: Theme.of(context).textTheme.titleMedium,
        ),
        const SizedBox(height: 6),
        const Text('Jam operasional sistem: 08:00–20:00 WIB.'),
        const SizedBox(height: 12),
        ..._rows.asMap().entries.map(
          (entry) => _scheduleCard(entry.key, entry.value),
        ),
        OutlinedButton.icon(
          onPressed: _isSavingSchedule ? null : _addSchedule,
          icon: const Icon(Icons.add),
          label: const Text('Tambah Jadwal'),
        ),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _isSavingSchedule ? null : _saveSchedule,
            child: _isSavingSchedule
                ? const CircularProgressIndicator()
                : const Text('Simpan Jadwal'),
          ),
        ),
      ],
    );
  }

  Widget _scheduleCard(int index, _AvailabilityDraft row) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<int>(
                    value: row.day,
                    decoration: const InputDecoration(labelText: 'Hari'),
                    items: List.generate(
                      7,
                      (day) =>
                          DropdownMenuItem(value: day, child: Text(_days[day])),
                    ),
                    onChanged: (value) {
                      if (value != null) {
                        setState(() => row.day = value);
                      }
                    },
                  ),
                ),
                IconButton(
                  onPressed: _rows.length == 1
                      ? null
                      : () => _removeSchedule(index),
                  icon: const Icon(Icons.delete_outline),
                ),
              ],
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Aktif'),
              value: row.isActive,
              onChanged: (value) {
                setState(() => row.isActive = value);
              },
            ),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: row.start,
                    keyboardType: TextInputType.datetime,
                    decoration: const InputDecoration(
                      labelText: 'Mulai',
                      hintText: '08:00',
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextField(
                    controller: row.end,
                    keyboardType: TextInputType.datetime,
                    decoration: const InputDecoration(
                      labelText: 'Selesai',
                      hintText: '16:00',
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _AvailabilityDraft {
  int day;
  bool isActive;
  final TextEditingController start;
  final TextEditingController end;

  _AvailabilityDraft(this.day)
    : isActive = false,
      start = TextEditingController(text: '08:00'),
      end = TextEditingController(text: '20:00');

  _AvailabilityDraft.fromModel(MitraAvailability item)
    : day = item.dayOfWeek,
      isActive = item.isActive,
      start = TextEditingController(text: item.startTime),
      end = TextEditingController(text: item.endTime);

  void dispose() {
    start.dispose();
    end.dispose();
  }
}
