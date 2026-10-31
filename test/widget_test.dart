import 'package:flutter_test/flutter_test.dart';

import 'package:hadirmetrik/main.dart';

void main() {
  testWidgets('app starts on the splash screen', (tester) async {
    await tester.pumpWidget(const HadirMetrik());
    expect(find.text('HadirMetrik'), findsWidgets);
    await tester.pump(const Duration(seconds: 5));
  });
}
