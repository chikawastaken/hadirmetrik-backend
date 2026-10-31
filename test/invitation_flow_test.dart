import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hadirmetrik/api.dart';
import 'package:hadirmetrik/deeplink.dart';
import 'package:hadirmetrik/screens/auth.dart';
import 'package:hadirmetrik/widgets.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  tearDown(() {
    Api.token = null;
    Api.client = http.Client();
    DeepLink.pendingToken = null;
  });

  testWidgets(
    'change password blocks a new password shorter than 8 characters',
    (tester) async {
      await tester.pumpWidget(const MaterialApp(home: ChangePassword()));

      await tester.tap(find.text('Simpan Kata Sandi'));
      await tester.pump();

      expect(find.text('Kata sandi baru minimal 8 karakter'), findsOneWidget);
    },
  );

  testWidgets(
    'invitation link lets the employee choose a password and opens the invitation',
    (tester) async {
      DeepLink.remember('abc123abc123abc1');
      Map<String, dynamic>? body;

      Api.client = MockClient((request) async {
        if (request.url.path == '/api/v1/invitations/abc123abc123abc1/password') {
          body = jsonDecode(request.body) as Map<String, dynamic>;
          return http.Response(
            jsonEncode({
              'success': true,
              'data': {
                'token': 'session-token',
                'token_type': 'Bearer',
                'must_change_password': false,
                'user': {'id': 2, 'role': 'employee'},
              },
            }),
            200,
          );
        }
        return http.Response(
          jsonEncode({
            'success': true,
            'data': {'next_step': 'face_enrollment'},
          }),
          200,
        );
      });

      await tester.pumpWidget(const MaterialApp(home: InvitationSetPassword()));
      await tester.pumpAndSettle();

      await tester.enterText(find.ancestor(of: find.text('Minimal 8 karakter'), matching: find.byType(TextFormField)), 'KaryawanBaru2026');
      await tester.enterText(find.ancestor(of: find.text('Ketik ulang kata sandi baru'), matching: find.byType(TextFormField)), 'KaryawanBaru2026');
      await tester.tap(find.widgetWithText(Btn, 'Buat Kata Sandi'));
      await tester.pumpAndSettle();

      expect(body, {'new_password': 'KaryawanBaru2026', 'new_password_confirmation': 'KaryawanBaru2026'});
      expect(Api.token, 'session-token');
      expect(find.text('Registrasi Wajah'), findsOneWidget);
    },
  );

  testWidgets(
    'invitation link refuses a password shorter than 8 characters',
    (tester) async {
      DeepLink.remember('abc123abc123abc1');
      Api.client = MockClient((request) async => http.Response('{}', 500));

      await tester.pumpWidget(const MaterialApp(home: InvitationSetPassword()));
      await tester.pumpAndSettle();

      await tester.tap(find.widgetWithText(Btn, 'Buat Kata Sandi'));
      await tester.pump();

      expect(find.text('Kata sandi minimal 8 karakter'), findsOneWidget);
    },
  );

  testWidgets(
    'invitation link offers the employee login when the password was already changed',
    (tester) async {
      DeepLink.remember('abc123abc123abc1');
      Api.client = MockClient((request) async {
        return http.Response(
          jsonEncode({
            'success': false,
            'message': 'Kata sandi akun ini sudah pernah diganti. Silakan masuk dengan kata sandi Anda.',
            'errors': {
              'code': ['INVITATION_PASSWORD_ALREADY_CHANGED'],
            },
          }),
          409,
        );
      });

      await tester.pumpWidget(const MaterialApp(home: InvitationSetPassword()));
      await tester.pumpAndSettle();

      await tester.enterText(find.ancestor(of: find.text('Minimal 8 karakter'), matching: find.byType(TextFormField)), 'KaryawanBaru2026');
      await tester.enterText(find.ancestor(of: find.text('Ketik ulang kata sandi baru'), matching: find.byType(TextFormField)), 'KaryawanBaru2026');
      await tester.tap(find.widgetWithText(Btn, 'Buat Kata Sandi'));
      await tester.pumpAndSettle();

      expect(find.text('Masuk dengan akun karyawan'), findsOneWidget);

      await tester.tap(find.text('Masuk dengan akun karyawan'));
      await tester.pumpAndSettle();

      expect(find.text('Selamat Datang'), findsOneWidget);
      expect(DeepLink.pendingToken, 'abc123abc123abc1');
    },
  );

  testWidgets(
    'invitation accept reserves the token and opens face enrollment',
    (tester) async {
      Api.token = 'session-token';
      DeepLink.remember('abc123abc123abc1');
      Api.client = MockClient((request) async {
        expect(request.url.path, '/api/v1/invitations/abc123abc123abc1/accept');
        return http.Response(
          jsonEncode({
            'success': true,
            'data': {'next_step': 'face_enrollment'},
          }),
          200,
        );
      });

      await tester.pumpWidget(const MaterialApp(home: InvitationAccept()));
      await tester.pumpAndSettle();

      expect(find.text('Registrasi Wajah'), findsOneWidget);
      expect(DeepLink.hasPending, isFalse);
    },
  );

  testWidgets(
    'invitation owned by another account offers a switch to the employee login',
    (tester) async {
      Api.token = 'hr-session';
      DeepLink.remember('abc123abc123abc1');
      Api.client = MockClient((request) async {
        return http.Response(
          jsonEncode({
            'success': false,
            'message': 'Undangan ini bukan milik akun Anda.',
            'errors': {
              'code': ['INVITATION_NOT_OWNED'],
            },
          }),
          403,
        );
      });

      await tester.pumpWidget(const MaterialApp(home: InvitationAccept()));
      await tester.pumpAndSettle();

      expect(find.text('Undangan ini bukan milik akun Anda.'), findsOneWidget);

      await tester.tap(find.text('Masuk dengan akun karyawan'));
      await tester.pumpAndSettle();

      expect(Api.token, isNull);
      expect(DeepLink.pendingToken, 'abc123abc123abc1');
      expect(find.text('Selamat Datang'), findsOneWidget);
    },
  );
}
