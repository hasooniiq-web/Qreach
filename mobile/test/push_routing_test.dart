import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/notifications/push_notification_service.dart';

void main() {
  test('a contact-request push opens that conversation', () {
    expect(
        routeForPush({'type': 'contact_request', 'contact_request_id': 'abc'}),
        '/contacts/abc');
  });

  test('a contact-request push without an id opens the inbox', () {
    expect(routeForPush({'type': 'contact_request'}), '/contacts');
  });

  test('a tag-action push opens notifications; unknown pushes go nowhere', () {
    expect(routeForPush({'type': 'tag_action'}), '/notifications');
    expect(routeForPush({'type': 'something_else'}), isNull);
    expect(routeForPush({}), isNull);
  });
}
