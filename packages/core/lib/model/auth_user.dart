class AuthUser {
  final int userId;
  final String fullName;
  final String phone;
  final String? email;
  final String role;
  final String accountStatus;
  final int? mitraId;
  final String? verificationStatus;

  const AuthUser({
    required this.userId,
    required this.fullName,
    required this.phone,
    required this.role,
    required this.accountStatus,
    this.email,
    this.mitraId,
    this.verificationStatus,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      userId: json['user_id'] as int,
      fullName: json['full_name'] as String,
      phone: json['phone'] as String,
      email: json['email'] as String?,
      role: json['role'] as String,
      accountStatus: json['account_status'] as String,
      mitraId: json['mitra_id'] as int?,
      verificationStatus: json['verification_status'] as String?,
    );
  }
}
