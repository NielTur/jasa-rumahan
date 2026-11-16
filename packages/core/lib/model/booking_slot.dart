class BookingSlot {
  final DateTime start;
  final DateTime end;
  final bool available;

  const BookingSlot({
    required this.start,
    required this.end,
    required this.available,
  });

  factory BookingSlot.fromJson(Map<String, dynamic> json) {
    return BookingSlot(
      start: DateTime.parse(json['start'] as String),
      end: DateTime.parse(json['end'] as String),
      available: json['available'] as bool,
    );
  }

  String get label {
    final startTime = _formatTime(start);
    final endTime = _formatTime(end);

    return '$startTime - $endTime';
  }

  String _formatTime(DateTime dateTime) {
    final hour = dateTime.hour.toString().padLeft(2, '0');
    final minute = dateTime.minute.toString().padLeft(2, '0');

    return '$hour:$minute';
  }
}
