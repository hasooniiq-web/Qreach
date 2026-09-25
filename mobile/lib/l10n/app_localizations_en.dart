// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appName => 'Qreach SmartTag';

  @override
  String get welcomeTitle => 'Welcome to Qreach';

  @override
  String get welcomeSubtitle =>
      'Add a SmartTag to your vehicle to communicate safely';

  @override
  String get phoneLabel => 'Phone number';

  @override
  String get phoneHint => '7xxxxxxxxx';

  @override
  String get sendCode => 'Send verification code';

  @override
  String get otpTitle => 'Enter verification code';

  @override
  String otpSubtitle(String phone) {
    return 'We sent a 6-digit code to $phone';
  }

  @override
  String get verify => 'Verify';

  @override
  String get resendCode => 'Resend code';

  @override
  String resendIn(int seconds) {
    return 'Resend in ${seconds}s';
  }

  @override
  String get invalidOtp => 'Invalid verification code';

  @override
  String homeGreetingWithName(String name) {
    return 'Hello, $name';
  }

  @override
  String get homeGreetingNoName => 'Welcome back';

  @override
  String get quickActions => 'Quick actions';

  @override
  String get notifications => 'Notifications';

  @override
  String get notificationsComingSoonTitle => 'Notifications are coming soon';

  @override
  String get notificationsComingSoonSubtitle =>
      'We\'re building a full notification center for scan and activity alerts.';

  @override
  String get vehicleProtected => 'Protected';

  @override
  String get vehicleNotProtectedYet => 'Not protected yet';

  @override
  String get noVehicleYetHomeTitle => 'Add your first vehicle';

  @override
  String get noVehicleYetHomeSubtitle =>
      'Add a vehicle to start using SmartTag.';

  @override
  String get myVehicles => 'My Vehicles';

  @override
  String get addVehicle => 'Add vehicle';

  @override
  String get noVehiclesTitle => 'No vehicles yet';

  @override
  String get noVehiclesSubtitle =>
      'Add your first vehicle to start using SmartTag';

  @override
  String get plateNumber => 'Plate number';

  @override
  String get nickname => 'Nickname (optional)';

  @override
  String get make => 'Make';

  @override
  String get model => 'Model';

  @override
  String get year => 'Year';

  @override
  String get color => 'Color';

  @override
  String get vehicleType => 'Vehicle type';

  @override
  String get save => 'Save';

  @override
  String get cancel => 'Cancel';

  @override
  String get linkedTags => 'Linked tags';

  @override
  String get noTagsYet => 'No tags linked to this vehicle yet';

  @override
  String get linkNewTag => 'Link a new tag';

  @override
  String get scanTagInstructions =>
      'Point the camera at the QR code on the sticker';

  @override
  String get activating => 'Activating...';

  @override
  String get activationSuccess => 'Tag linked successfully';

  @override
  String get tagAlreadyActivated => 'This tag is already activated';

  @override
  String get tagRevoked => 'This tag has been revoked';

  @override
  String get tagNotFound => 'Invalid tag';

  @override
  String get profile => 'Profile';

  @override
  String get logout => 'Log out';

  @override
  String get logoutAllDevices => 'Log out of all devices';

  @override
  String get logoutConfirm => 'Are you sure you want to log out?';

  @override
  String get settings => 'Settings';

  @override
  String get language => 'Language';

  @override
  String get sedan => 'Sedan';

  @override
  String get suv => 'SUV';

  @override
  String get truck => 'Truck';

  @override
  String get motorcycle => 'Motorcycle';

  @override
  String get van => 'Van';

  @override
  String get other => 'Other';

  @override
  String get statusActive => 'Active';

  @override
  String get statusAssigned => 'Assigned';

  @override
  String get statusRevoked => 'Revoked';

  @override
  String get genericError => 'Something went wrong. Please try again.';

  @override
  String get networkError =>
      'Couldn\'t reach the server. Check your connection.';

  @override
  String get retry => 'Retry';

  @override
  String get loading => 'Loading...';

  @override
  String get delete => 'Delete';

  @override
  String get deleteVehicleConfirm =>
      'Are you sure you want to delete this vehicle?';

  @override
  String get requiredField => 'This field is required';

  @override
  String get invalidPhone => 'Invalid phone number';

  @override
  String get subscriptionTitle => 'Subscription';

  @override
  String get currentPlan => 'Your current plan';

  @override
  String get planDefaultFree => 'Default free plan';

  @override
  String planEndsOn(String date) {
    return 'Ends on $date';
  }

  @override
  String planExpiredBanner(String date) {
    return 'Your subscription ended on $date. You are back on Basic.';
  }

  @override
  String get planFeaturesTitle => 'What your plan includes';

  @override
  String get featureMessaging => 'Receive visitor messages';

  @override
  String get featurePublicPhone => 'Show your phone number to visitors';

  @override
  String get featurePublicInstagram => 'Show your Instagram account';

  @override
  String get featurePublicExtra => 'Extra contact info';

  @override
  String get plansTitle => 'Plans';

  @override
  String get planYourPlan => 'Your plan';

  @override
  String get priceFree => 'Free';

  @override
  String priceAmountYear(String amount) {
    return '$amount IQD / year';
  }

  @override
  String priceAmountDays(String amount, int days) {
    return '$amount IQD / $days days';
  }

  @override
  String get requestUpgrade => 'Request upgrade';

  @override
  String get upgradePending => 'Request under review';

  @override
  String upgradeConfirmTitle(String plan) {
    return 'Request an upgrade to $plan?';
  }

  @override
  String get upgradeConfirmBody =>
      'The Qreach team will review your request and contact you to complete the subscription and activate the plan.';

  @override
  String get upgradeSent => 'Upgrade request sent';

  @override
  String get subStatusActive => 'Active';

  @override
  String get subStatusExpired => 'Expired';

  @override
  String get subStatusCanceled => 'Canceled';

  @override
  String get subStatusPastDue => 'Past due';

  @override
  String get subStatusTrialing => 'Trial';

  @override
  String get publicContactTitle => 'Public contact details';

  @override
  String get publicContactSubtitle =>
      'Shown to anyone who scans your vehicle tag. Choose what to share.';

  @override
  String get publicContactLockedTitle => 'Available on the Pro plan';

  @override
  String get publicContactLockedBody =>
      'On Basic, visitors can only message you. Your phone number and accounts are not shown to them.';

  @override
  String get viewPlans => 'View plans';

  @override
  String get phoneShownLabel => 'Phone number shown to visitors';

  @override
  String get instagramLabel => 'Instagram account';

  @override
  String get instagramHint => 'Username only';

  @override
  String get extraLabel => 'Extra information';

  @override
  String get extraHint => 'e.g. Available after 5pm';

  @override
  String get saved => 'Saved';

  @override
  String get invalidInstagram => 'Invalid username';

  @override
  String get settingsAccount => 'Account';

  @override
  String get settingsSecurity => 'Security';

  @override
  String get settingsPreferences => 'Preferences';

  @override
  String get languageName => 'Arabic';

  @override
  String get loadErrorTitle => 'Couldn\'t load data';

  @override
  String get homeUpgradeTitle => 'Show your contact details to visitors';

  @override
  String get homeUpgradeBody =>
      'The Pro plan shows your number and Instagram when your tag is scanned.';

  @override
  String tagsLinkedCount(int count) {
    return '$count tags linked';
  }

  @override
  String get activationSuccessSubtitle =>
      'Anyone can now scan the tag and reach you.';

  @override
  String get done => 'Done';

  @override
  String get protectionStatus => 'Protection status';

  @override
  String get vehicleDetails => 'Vehicle details';

  @override
  String get tagTypeQr => 'QR tag';

  @override
  String get tagTypeNfc => 'NFC tag';

  @override
  String get vehicleFormIdentity => 'Vehicle identity';

  @override
  String get vehicleFormMore => 'More details';

  @override
  String get vehicleFormNicknameHint => 'A name to tell your vehicles apart';

  @override
  String get notificationsEmptyTitle => 'No notifications yet';

  @override
  String get notificationsEmptySubtitle =>
      'Alerts appear here when someone interacts with your vehicle tag.';

  @override
  String get markAllRead => 'Mark all as read';

  @override
  String get groupToday => 'Today';

  @override
  String get groupYesterday => 'Yesterday';

  @override
  String get groupEarlier => 'Earlier';

  @override
  String get recentActivity => 'Recent activity';

  @override
  String get viewAll => 'View all';

  @override
  String get unreadLabel => 'Unread';

  @override
  String get emergencyLabel => 'Emergency';

  @override
  String get noRecentActivity =>
      'No activity yet. When someone scans your vehicle tag, you will see it here.';

  @override
  String get tabHome => 'Home';

  @override
  String get tabVehicles => 'Vehicles';

  @override
  String get tabContacts => 'Contacts';

  @override
  String get tabProfile => 'Profile';

  @override
  String get contactsTitle => 'Contact requests';

  @override
  String get contactsEmptyTitle => 'No messages yet';

  @override
  String get contactsEmptySubtitle =>
      'When someone messages you after scanning your vehicle tag, it appears here.';

  @override
  String get contactStatusNew => 'New';

  @override
  String get contactStatusRead => 'Read';

  @override
  String get contactStatusResponded => 'Replied';

  @override
  String get contactStatusClosed => 'Closed';

  @override
  String get markResponded => 'Mark as replied';

  @override
  String get callVisitor => 'Call visitor';

  @override
  String get visitorAnonymous => 'Visitor';

  @override
  String contactAboutVehicle(String vehicle) {
    return 'About $vehicle';
  }

  @override
  String get vehicleCodeTitle => 'Vehicle code';

  @override
  String get removeCode => 'Remove code';

  @override
  String get removeCodeConfirmTitle => 'Remove the vehicle code?';

  @override
  String get removeCodeConfirmBody =>
      'The current code stops working for good and cannot be linked again. You can then link a new one.';

  @override
  String get codeRemoved => 'Code removed';

  @override
  String get oneCodeNote =>
      'Each vehicle has one code. To change it, remove the current one, then link the new one.';

  @override
  String get noCodeYetTitle => 'No code linked';

  @override
  String get noCodeYetSubtitle =>
      'Scan the sticker code so people can reach you about this vehicle.';

  @override
  String get profileMyInfo => 'My info';

  @override
  String get profileName => 'Name';

  @override
  String get profileNameHint => 'Your name as you want it shown';

  @override
  String get editName => 'Edit name';

  @override
  String get notSet => 'Not set';

  @override
  String get visibilityTitle => 'What visitors see';

  @override
  String get visibilitySubtitle =>
      'By default a visitor sees nothing except a way to message you. Add your details and choose what to show.';

  @override
  String get showToVisitors => 'Show to visitors';

  @override
  String get proBadge => 'Pro';

  @override
  String get visibilityNoneShown =>
      'Nothing is shown to visitors right now — they can only message you.';

  @override
  String visibilitySharing(String items) {
    return 'Visitors see: $items';
  }

  @override
  String get visibilityLockedHint =>
      'Showing details to visitors is available on the Pro plan.';

  @override
  String get scanManualTitle => 'Enter the code manually';

  @override
  String get scanManualHint => 'Paste the code link or the code itself';

  @override
  String get scanManualButton => 'Link';

  @override
  String get scanCameraError => 'Couldn\'t start the camera';

  @override
  String get scanCameraPermission =>
      'Allow camera access in your phone settings and try again, or enter the code manually.';

  @override
  String get scanTorch => 'Flash';

  @override
  String get homeStatVehicles => 'Vehicles';

  @override
  String get homeStatNewMessages => 'New messages';

  @override
  String get homeLinkCode => 'Link a code';

  @override
  String get homeNoCodeCta => 'Link your vehicle code';
}
