import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:qreach_smarttag/app.dart';

void main() {
  testWidgets('app boots to the splash screen without crashing',
      (tester) async {
    await tester.pumpWidget(const ProviderScope(child: QreachApp()));

    // Before the stored-session check resolves, the splash screen (with its
    // 'Q' logo mark) should be showing rather than any crash/red screen.
    expect(find.text('Q'), findsOneWidget);
  });
}
