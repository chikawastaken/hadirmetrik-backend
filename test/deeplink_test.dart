import 'package:flutter_test/flutter_test.dart';
import 'package:hadirmetrik/deeplink.dart';

void main() {
  const token = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';

  test('reads the token from a hadirmetrik invitation link', () {
    expect(
      InvitationLink.tokenFrom(Uri.parse('hadirmetrik://invitations/$token')),
      token,
    );
  });

  test('reads the token when the link uses an empty host', () {
    expect(
      InvitationLink.tokenFrom(Uri.parse('hadirmetrik:///invitations/$token')),
      token,
    );
  });

  test('ignores links from another scheme', () {
    expect(
      InvitationLink.tokenFrom(
        Uri.parse('https://example.com/invitations/$token'),
      ),
      isNull,
    );
  });

  test('ignores links for another host', () {
    expect(
      InvitationLink.tokenFrom(Uri.parse('hadirmetrik://other/$token')),
      isNull,
    );
  });

  test('ignores tokens that fall outside the backend length range', () {
    expect(
      InvitationLink.tokenFrom(Uri.parse('hadirmetrik://invitations/short')),
      isNull,
    );
  });

  test('ignores links with extra path segments', () {
    expect(
      InvitationLink.tokenFrom(
        Uri.parse('hadirmetrik://invitations/$token/extra'),
      ),
      isNull,
    );
  });
}
