

import 'dart:convert';
import 'dart:developer' as developer;
import 'package:http/http.dart' as http;
import '../../utils/token_manager.dart';

class ApiClient {
  static Future<Map<String, String>> _getHeaders() async {
    final token = await TokenManager.getToken();
    if (token != null && token.isNotEmpty) {
      developer.log('ApiClient: Token found, sending Authorization header');
      return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      };
    }
    developer.log('ApiClient: WARNING - No token found! Request will be sent without Authorization header.');
    return {
      'Content-Type': 'application/json',
    };
  }

  static Future<http.Response> getRequest(String url) async {
    final headers = await _getHeaders();
    developer.log('ApiClient GET: $url');
    final response = await http.get(Uri.parse(url), headers: headers);
    _logResponse(response);
    return response;
  }

  static Future<http.Response> postRequest(String url, Map<String, dynamic> body) async {
    final headers = await _getHeaders();
    developer.log('ApiClient POST: $url');
    final response = await http.post(
      Uri.parse(url),
      headers: headers,
      body: jsonEncode(body),
    );
    _logResponse(response);
    return response;
  }

  static void _logResponse(http.Response response) {
    if (response.statusCode == 401) {
      developer.log('ApiClient: 401 Unauthorized! Response: ${response.body}');
    } else if (response.statusCode >= 400) {
      developer.log('ApiClient: Error ${response.statusCode} - ${response.body}');
    }
  }
}
