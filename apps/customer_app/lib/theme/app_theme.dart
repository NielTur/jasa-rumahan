
import 'package:flutter/material.dart';

class AppTheme {
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: const Color(0xFF0F766E),
      ),
      scaffoldBackgroundColor: const Color(0xFFF6F8F7),
      appBarTheme: const AppBarTheme(
        backgroundColor: Color(0xFFF6F8F7),
        foregroundColor: Color(0xFF172A29),
        elevation: 0,
        centerTitle: false,
      ),
    );
  }
}
