import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hadirmetrik/api.dart';
import 'package:hadirmetrik/screens/hr_people.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  tearDown(() {
    Api.token = null;
    Api.client = http.Client();
  });

  testWidgets('add employee sends the form and hides the temporary password when an invitation is sent', (tester) async {
    Api.token = 'hr-session';
    Map<String, dynamic>? sent;

    Api.client = MockClient((request) async {
      if (request.method == 'GET' && request.url.path.endsWith('/hr/employees/create-options')) {
        return _optionsResponse();
      }
      if (request.method == 'POST' && request.url.path.endsWith('/hr/employees')) {
        sent = jsonDecode(request.body) as Map<String, dynamic>;
        return http.Response(
          jsonEncode({
            'success': true,
            'data': {
              'employee': {'id': 10, 'employee_number': 'HM-0316', 'full_name': 'Budi Santoso'},
              'must_change_password': true,
              'invitation': {
                'id': 3,
                'status': 'pending',
                'deliveries': [
                  {'channel': 'email', 'status': 'queued'},
                ],
              },
            },
          }),
          201,
        );
      }
      return http.Response(jsonEncode({'success': false, 'message': 'unexpected'}), 500);
    });

    await _openForm(tester);
    await _fillForm(tester);

    await tester.tap(find.text('Simpan & Undang'));
    await tester.pumpAndSettle();

    expect(sent, isNotNull);
    expect(sent!['full_name'], 'Budi Santoso');
    expect(sent!['email'], 'budi@hadirmetrik.local');
    expect(sent!['division_id'], 1);
    expect(sent!['shift_id'], 1);
    expect(sent!['work_location_id'], 1);
    expect(sent!['manager_id'], 9);
    expect(sent!['send_invitation'], true);
    expect(sent!['invitation_channels'], ['email']);
    expect(sent!['compensation'], {
      'base_salary': 5200000,
      'transport_allowance': 575000,
      'meal_allowance': 690000,
    });
    expect(sent!['employment_start'], '2026-10-19');

    // The employee sets their own password from the invitation link, so the
    // dialog must not offer the temporary one.
    expect(find.textContaining('Karyawan membuat kata sandinya sendiri'), findsOneWidget);
    expect(find.text('Kata sandi sementara'), findsNothing);
    expect(find.byTooltip('Salin kata sandi'), findsNothing);
  });

  testWidgets('add employee without an invitation still shows the temporary password and copies it', (tester) async {
    Api.token = 'hr-session';
    Map<String, dynamic>? sent;
    String? copied;

    tester.binding.defaultBinaryMessenger.setMockMethodCallHandler(SystemChannels.platform, (call) async {
      if (call.method == 'Clipboard.setData') copied = (call.arguments as Map)['text'] as String?;
      return null;
    });
    addTearDown(() => tester.binding.defaultBinaryMessenger.setMockMethodCallHandler(SystemChannels.platform, null));

    Api.client = MockClient((request) async {
      if (request.method == 'GET' && request.url.path.endsWith('/hr/employees/create-options')) {
        return _optionsResponse();
      }
      if (request.method == 'POST' && request.url.path.endsWith('/hr/employees')) {
        sent = jsonDecode(request.body) as Map<String, dynamic>;
        return http.Response(
          jsonEncode({
            'success': true,
            'data': {
              'employee': {'id': 11, 'employee_number': 'HM-0317', 'full_name': 'Budi Santoso'},
              'initial_password': 'Abc12345Xyz67890',
              'must_change_password': true,
            },
          }),
          201,
        );
      }
      return http.Response(jsonEncode({'success': false, 'message': 'unexpected'}), 500);
    });

    await _openForm(tester);
    await _fillForm(tester);

    final scrollable = find.descendant(of: find.byType(ListView), matching: find.byType(Scrollable)).first;
    await tester.scrollUntilVisible(find.text('Kirim undangan registrasi wajah'), 200, scrollable: scrollable);
    await tester.tap(find.text('Kirim undangan registrasi wajah'));
    await tester.pump();

    await tester.tap(find.text('Simpan & Undang'));
    await tester.pumpAndSettle();

    expect(sent!['send_invitation'], false);
    expect(find.text('Abc12345Xyz67890'), findsOneWidget);

    await tester.tap(find.byTooltip('Salin kata sandi'));
    await tester.pump();
    expect(copied, 'Abc12345Xyz67890');
  });
}

http.Response _optionsResponse() => http.Response(
      jsonEncode({
        'success': true,
        'data': {
          'divisions': [
            {'id': 1, 'name': 'Operasional', 'code': 'OPS'},
          ],
          'shifts': [
            {'id': 1, 'name': 'Pagi', 'start_time': '08:00', 'end_time': '17:00', 'timezone': 'Asia/Jakarta', 'is_overnight': false},
          ],
          'work_locations': [
            {'id': 1, 'name': 'Kantor Pusat', 'address': 'Yogyakarta', 'latitude': -7.7, 'longitude': 110.3, 'geofence_radius_m': 100, 'timezone': 'Asia/Jakarta'},
          ],
          'managers': [
            {'id': 9, 'employee_number': 'HM-0001', 'full_name': 'Sari Wulandari', 'job_title': 'Manager'},
          ],
        },
      }),
      200,
    );

Future<void> _openForm(WidgetTester tester) async {
  tester.view.physicalSize = const Size(800, 1600);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);

  await tester.pumpWidget(const MaterialApp(home: AddEmployee()));
  await tester.pumpAndSettle();
}

Future<void> _fillForm(WidgetTester tester) async {
  final scrollable = find.descendant(of: find.byType(ListView), matching: find.byType(Scrollable)).first;

  Future<void> fill(String hint, String value) async {
    await tester.scrollUntilVisible(find.text(hint), 160, scrollable: scrollable);
    await tester.enterText(find.ancestor(of: find.text(hint), matching: find.byType(TextFormField)), value);
    await tester.pump();
  }

  await fill('Sesuai KTP', 'Budi Santoso');
  await fill('nama@sinarmetrik.co.id', 'budi@hadirmetrik.local');
  await fill('0812 3456 7890', '081234567890');
  await fill('Contoh: Staf Gudang', 'Staf Gudang');
  await tester.pump();
}
