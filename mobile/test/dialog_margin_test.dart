import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/theme/app_theme.dart';

void main() {
  for (final size in const [Size(390, 844), Size(430, 932), Size(320, 640)]) {
    testWidgets('a pop-up keeps the same 16 side margin as the page cards at ${size.width.toInt()} wide',
        (tester) async {
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);

      await tester.pumpWidget(MaterialApp(
        theme: AppTheme.build(dark: false),
        home: Builder(
          builder: (context) => Scaffold(
            body: TextButton(
              onPressed: () => showDialog<void>(
                context: context,
                builder: (_) => AlertDialog(
                  title: const Text('الاسم'),
                  // Short content on purpose: it used to shrink the dialog around it.
                  content: const TextField(),
                  actions: [TextButton(onPressed: () {}, child: const Text('حفظ'))],
                ),
              ),
              child: const Text('open'),
            ),
          ),
        ),
      ));

      await tester.tap(find.text('open'));
      await tester.pumpAndSettle();

      final surface = find.descendant(of: find.byType(Dialog), matching: find.byType(Material)).first;
      final rect = tester.getRect(surface);
      expect(rect.left, 16);
      expect(rect.right, size.width - 16);
    });
  }
}
