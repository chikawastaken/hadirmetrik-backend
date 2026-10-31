import 'dart:convert';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;

const baseUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: kIsWeb ? 'http://localhost:8000/api/v1' : 'http://10.0.2.2:8000/api/v1',
);

class ApiError implements Exception {
  ApiError(this.message, [this.errors]);
  final String message;
  final Map<String, dynamic>? errors;

  /// Machine-readable reason from `errors.code`, e.g. `INVITATION_NOT_OWNED`.
  String? get code {
    final value = errors?['code'];
    if (value is List && value.isNotEmpty) return value.first.toString();
    if (value is String) return value;
    return null;
  }

  @override
  String toString() => message;
}

class Api {
  static String? token;

  /// Overridable for tests.
  static http.Client client = http.Client();

  static Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  static Map<String, dynamic> _handle(http.Response r) {
    Map<String, dynamic> body;
    try {
      body = jsonDecode(r.body) as Map<String, dynamic>;
    } catch (_) {
      throw ApiError('Respons server tidak valid (${r.statusCode})');
    }
    if (r.statusCode >= 200 && r.statusCode < 300 && body['success'] == true) {
      return (body['data'] as Map<String, dynamic>?) ?? {};
    }
    throw ApiError(body['message'] ?? 'Terjadi kesalahan', body['errors']);
  }

  static Future<Map<String, dynamic>> post(String path, Map<String, dynamic> data) async =>
      _handle(await client
          .post(Uri.parse('$baseUrl$path'), headers: _headers, body: jsonEncode(data))
          .timeout(const Duration(seconds: 15)));

  static Future<Map<String, dynamic>> get(String path) async =>
      _handle(await client
          .get(Uri.parse('$baseUrl$path'), headers: _headers)
          .timeout(const Duration(seconds: 15)));

  static Future<Map<String, dynamic>> patch(String path, Map<String, dynamic> data) async =>
      _handle(await client
          .patch(Uri.parse('$baseUrl$path'), headers: _headers, body: jsonEncode(data))
          .timeout(const Duration(seconds: 15)));

  static Future<Map<String, dynamic>> getProfile() async => get('/me/profile');
  static Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> data) async => patch('/me/profile', data);

  /// Reserves the invitation for the signed-in employee (architecture §4.8).
  static Future<Map<String, dynamic>> acceptInvitation(String token) => post('/invitations/$token/accept', {});

  /// Chooses the permanent password straight from the invitation link and
  /// receives a session in return (architecture §4.8).
  static Future<Map<String, dynamic>> setPasswordFromInvitation(String invitation, String next) async {
    final data = await post('/invitations/$invitation/password', {
      'new_password': next,
      'new_password_confirmation': next,
    });
    token = data['token'] as String?;
    return data;
  }

  static Future<Map<String, dynamic>> changePassword({required String current, required String next}) =>
      post('/auth/change-password', {
        'current_password': current,
        'new_password': next,
        'new_password_confirmation': next,
      });

  static Future<Map<String, dynamic>> login(String identifier, String password) async {
    final data = await post('/login', {
      'identifier': identifier,
      'password': password,
      'device_name': 'Flutter Android',
    });
    token = data['token'] as String?;
    return data;
  }

  static Future<void> logout() async {
    try {
      await post('/logout', {});
    } finally {
      token = null;
    }
  }

  static Future<Map<String, dynamic>> createEmployee(Map<String, dynamic> body) =>
      post('/hr/employees', body);
}