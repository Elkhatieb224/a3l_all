class TimeHandler {
  static String timeAgo(DateTime dateTime) {
    final now = DateTime.now();
    final diff = now.difference(dateTime);

    if (diff.inSeconds < 60) {
      return 'الآن';
    } else if (diff.inMinutes < 60) {
      return ' منذ ${diff.inMinutes} دقيقه';
    } else if (diff.inHours < 24) {
      return ' منذ ${diff.inHours} ساعه';
    } else if (diff.inDays < 7) {
      return ' منذ ${diff.inDays} يوم';
    } else {
      return 'منذ أكثر من أسبوع';
    }
  }
}
