import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/empty_state.dart';

/// No customer-facing notifications API exists yet (see docs/API.md and
/// docs/UI_UX_DESIGN_SYSTEM_V2.md) — this ships as an honest "coming soon"
/// state rather than a screen that fabricates data or calls an endpoint
/// that doesn't exist, matching the admin dashboard's existing pattern for
/// not-yet-built nav destinations.
class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.notifications)),
      body: EmptyState(
        icon: Icons.notifications_outlined,
        title: l10n.notificationsComingSoonTitle,
        subtitle: l10n.notificationsComingSoonSubtitle,
      ),
    );
  }
}
