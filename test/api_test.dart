import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hadirmetrik/api.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  const token = 'abc123abc123abc1';

  setUp(() {
    Api.token = 'session-token';
  });

  tearDown(() {
    Api.token = null;
    Api.client = http.Client();
  });

  test('accept invitation posts the token with the session bearer', () async {
    late http.Request seen;
    Api.client = MockClient((request) async {
      seen = request;
      return http.Response(
        jsonEncode({
          'success': true,
          'data': {'next_step': 'face_enrollment'},
        }),
        200,
      );
    });

    final data = await Api.acceptInvitation(token);

    expect(seen.method, 'POST');
    expect(seen.url.path, '/api/v1/invitations/$token/accept');
    expect(seen.headers['Authorization'], 'Bearer session-token');
    expect(data['next_step'], 'face_enrollment');
  });

  test(
    'change password sends the current and confirmed new password',
    () async {
      late http.Request seen;
      Api.client = MockClient((request) async {
        seen = request;
        return http.Response(
          jsonEncode({
            'success': true,
            'data': {'must_change_password': false},
          }),
          200,
        );
      });

      await Api.changePassword(current: 'Temp12345', next: 'Rahasia123');

      expect(seen.method, 'POST');
      expect(seen.url.path, '/api/v1/auth/change-password');
      final body = jsonDecode(seen.body) as Map<String, dynamic>;
      expect(body['current_password'], 'Temp12345');
      expect(body['new_password'], 'Rahasia123');
      expect(body['new_password_confirmation'], 'Rahasia123');
    },
  );
}
