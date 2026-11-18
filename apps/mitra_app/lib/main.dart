import 'package:flutter/material.dart';

import 'theme/app_theme.dart';
import 'ui/auth_gate.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MitraApp());
}

class MitraApp extends StatelessWidget {
  const MitraApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Mitra Jasa Rumahan',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const AuthGate(),
    );
  }
}
