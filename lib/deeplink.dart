/// Deep link carried by the invitation email:
/// `hadirmetrik://invitations/<token>`.
class InvitationLink {
  static const scheme = 'hadirmetrik';
  static const host = 'invitations';

  /// Backend token route constraint: `[A-Za-z0-9]{16,64}`.
  static final _token = RegExp(r'^[A-Za-z0-9]{16,64}$');

  static String? tokenFrom(Uri uri) {
    if (uri.scheme != scheme) return null;

    final segments = uri.pathSegments.where((s) => s.isNotEmpty).toList();
    final String? candidate;

    if (uri.host == host && segments.length == 1) {
      candidate = segments.single;
    } else if (uri.host.isEmpty &&
        segments.length == 2 &&
        segments.first == host) {
      candidate = segments.last;
    } else {
      return null;
    }

    return _token.hasMatch(candidate) ? candidate : null;
  }
}

/// The token waits here while the employee logs in or replaces a temporary
/// password; the accept call happens only after a session exists.
class DeepLink {
  DeepLink._();

  static String? pendingToken;

  static bool get hasPending => pendingToken != null;

  static void remember(String token) => pendingToken = token;

  static String? take() {
    final token = pendingToken;
    pendingToken = null;
    return token;
  }
}
