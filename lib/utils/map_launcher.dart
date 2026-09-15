import 'package:url_launcher/url_launcher.dart';

class MapLauncher {
  static Future<void> openMap(double lat, double lng) async {
    final url = 'https://www.google.com/maps/search/?api=1&query=$lat,$lng';
    final uri = Uri.parse(url);

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      throw 'Tidak dapat membuka peta di koordinat $lat, $lng';
    }
  }
}
