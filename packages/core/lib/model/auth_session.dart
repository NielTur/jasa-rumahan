import 'auth_user.dart';

class AuthSession {
  final String token;
  final AuthUser user;

  const AuthSession({required this.token, required this.user});

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    return AuthSession(
      token: json['token'] as String,
      user: AuthUser.fromJson(Map<String, dynamic>.from(json['user'] as Map)),
    );
  }
}
