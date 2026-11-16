class CustomerProfile {
  final int userId;
  final String fullName;
  final String phone;
  final String? email;
  final String role;
  final String? accountStatus;

  const CustomerProfile({
    required this.userId,
    required this.fullName,
    required this.phone,
    required this.role,
    this.email,
    this.accountStatus,
  });

  factory CustomerProfile.fromJson(Map<String, dynamic> json) {
    return CustomerProfile(
      userId: json['user_id'] as int,
      fullName: json['full_name'] as String,
      phone: json['phone'] as String,
      email: json['email'] as String?,
      role: json['role'] as String,
      accountStatus: json['account_status'] as String?,
    );
  }
}
