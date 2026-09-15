class ApiConstants {

  static const String baseUrl = 'https://prelude-suave-suspend.ngrok-free.dev/temuin/backend/api';

  static const String login = '$baseUrl/auth/login.php';
  static const String register = '$baseUrl/auth/register.php';
  static const String profile = '$baseUrl/auth/profile.php';

  static const String categories = '$baseUrl/master/categories.php';

  static const String items = '$baseUrl/items/index.php';
  static const String itemDetail = '$baseUrl/items/detail.php';
  static const String itemDelete = '$baseUrl/items/delete.php';
  static const String itemsFeed = '$baseUrl/items/feed.php';
  static const String itemBoost = '$baseUrl/items/boost.php';
  static const String paymentCharge = '$baseUrl/payment/charge.php';

  static String get notifications => '$baseUrl/notifications/index.php';
  static String get readNotification => '$baseUrl/notifications/read.php';

  static const String reportFound = '$baseUrl/reports/found-report.php';
  static const String reportLost = '$baseUrl/reports/lost-report.php';
}
