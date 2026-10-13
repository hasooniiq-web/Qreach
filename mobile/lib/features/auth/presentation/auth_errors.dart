import '../../../core/network/api_exception.dart';
import '../../../l10n/app_localizations.dart';

/// "an hour", "10 hours", "a day" — a wait said the way a person says it, in the app language.
String waitText(AppLocalizations l10n, int seconds) {
  final minutes = (seconds / 60).ceil().clamp(1, 1 << 30);
  if (minutes < 60) return l10n.waitMinutes(minutes);

  final hours = (minutes / 60).ceil();
  if (hours < 24) return l10n.waitHours(hours);

  return l10n.waitDays((hours / 24).ceil());
}

/// What to tell the customer about a failed sign-in / code step. The server decides
/// the rules (3 wrong tries lock the account for 1 h, then 10 h, ...); this only says it
/// in the app language and shows how many tries are left.
String authErrorMessage(AppLocalizations l10n, ApiException e) {
  switch (e.code) {
    case 'AUTH_LOCKED':
      return e.retryAfter != null
          ? l10n.authLocked(waitText(l10n, e.retryAfter!))
          : e.message;
    case 'OTP_LIMIT':
      return e.retryAfter != null
          ? l10n.otpLimit(waitText(l10n, e.retryAfter!))
          : e.message;
    case 'OTP_COOLDOWN':
      return e.retryAfter != null
          ? l10n.otpCooldown(e.retryAfter!)
          : e.message;
    case 'RATE_LIMITED':
      return l10n.rateLimited;
    case 'NETWORK_ERROR':
      return l10n.networkError;
    case 'INVALID_CREDENTIALS':
      final left = e.attemptsLeft;
      return left != null && left > 0
          ? '${e.message}\n${l10n.attemptsLeft(left)}'
          : e.message;
    default:
      return e.message;
  }
}
