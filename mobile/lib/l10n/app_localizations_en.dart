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
  String get createAccountButton => 'Create a new account';

  @override
  String get alreadyHaveAccount => 'I already have an account';

  @override
  String get createAccountTitle => 'Create a new account';

  @override
  String get createAccountSubtitle =>
      'Enter your name, phone number and a password — we\'ll send a one-time code to confirm your number';

  @override
  String get loginTitle => 'Log in';

  @override
  String get loginSubtitle => 'Enter your phone number and password';

  @override
  String get phoneLabel => 'Phone number';

  @override
  String get phoneHint => '7xxxxxxxxx';

  @override
  String get sendCode => 'Send verification code';

  @override
  String get nameLabel => 'Name';

  @override
  String get nameHint => 'Your full name';

  @override
  String get passwordLabel => 'Password';

  @override
  String get passwordHint => 'At least 8 characters';

  @override
  String get createAccountSubmit => 'Create account';

  @override
  String get loginSubmit => 'Log in';

  @override
  String get passwordTooShort => 'Password must be at least 8 characters';

  @override
  String get forgotPasswordLink => 'Forgot your password?';

  @override
  String get forgotPasswordTitle => 'Reset password';

  @override
  String get forgotPasswordSubtitle =>
      'Enter your phone number and we\'ll send a code to set a new password';

  @override
  String get forgotPasswordSendOtp => 'Send verification code';

  @override
  String get forgotPasswordCodeLabel => 'Verification code';

  @override
  String get forgotPasswordNewPasswordLabel => 'New password';

  @override
  String get forgotPasswordSubmit => 'Set password and log in';

  @override
  String get changePasswordLabel => 'Change password';

  @override
  String get setPasswordLabel => 'Set a password';

  @override
  String get currentPasswordLabel => 'Current password';

  @override
  String get newPasswordLabel => 'New password';

  @override
  String get repeatPasswordLabel => 'Repeat new password';

  @override
  String get passwordsDontMatch => 'The two passwords do not match';

  @override
  String get passwordChanged => 'Password changed';

  @override
  String get biometricSectionTitle => 'Security';

  @override
  String get biometricToggleLabel => 'Unlock with fingerprint or face';

  @override
  String get biometricToggleHint =>
      'Open the app without typing your password every time';

  @override
  String get biometricEnablePrompt =>
      'Enable fingerprint/face unlock for this account';

  @override
  String get lockScreenTitle => 'App locked';

  @override
  String get lockScreenSubtitle =>
      'Use your fingerprint or face to unlock Qreach SmartTag';

  @override
  String get lockScreenUnlockButton => 'Unlock';

  @override
  String get lockScreenFailed => 'Couldn\'t unlock. Try again.';

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
  String get vehicleProtected => 'Linked';

  @override
  String get vehicleNotProtectedYet => 'Not linked';

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
  String get profile => 'My Account';

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
  String get featurePublicInstagram =>
      'Add social accounts (Instagram, Facebook, Snapchat...)';

  @override
  String get featurePublicExtra => 'Control what visitors see per account';

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
  String get voucherEntryTitle => 'Have an activation voucher?';

  @override
  String get voucherEntryBody =>
      'Enter the 12-character serial or scan the QR code to activate your plan.';

  @override
  String get voucherTitle => 'Activate with a voucher';

  @override
  String get voucherIntro =>
      'Bought an activation voucher from a reseller? Enter its 12-character serial (letters and digits) or scan the QR printed on it and your plan switches on right away.';

  @override
  String get voucherFieldLabel => 'Voucher number (12 characters)';

  @override
  String get voucherActivate => 'Activate';

  @override
  String get voucherScan => 'Scan QR code';

  @override
  String get voucherScanHint =>
      'Point the camera at the QR code printed on the voucher';

  @override
  String get voucherNote =>
      'Each voucher can be used once. If it is already used, expired or cancelled, the app will tell you.';

  @override
  String voucherSuccessTitle(String plan) {
    return '$plan is now active';
  }

  @override
  String voucherSuccessUntil(String date) {
    return 'Your plan is active until $date.';
  }

  @override
  String voucherSuccessDays(int days) {
    return 'Your plan was activated for $days days.';
  }

  @override
  String get voucherErrInvalid =>
      'This voucher number does not exist. Check it and try again.';

  @override
  String get voucherErrFormat =>
      'The number is incomplete or wrong. Make sure it has 12 letters and digits.';

  @override
  String get voucherErrUsed => 'This voucher has already been used.';

  @override
  String get voucherErrExpired => 'This voucher has expired.';

  @override
  String get voucherErrRevoked =>
      'This voucher was cancelled and cannot be used.';

  @override
  String get voucherErrThrottled =>
      'Too many attempts in a short time. Wait a minute and try again.';

  @override
  String get voucherErrLocked => 'Too many attempts. Try again in an hour.';

  @override
  String get onboard1Title => 'Link your code to your vehicle';

  @override
  String get onboard1Body =>
      'Add your vehicle, then scan the QR code or tap your phone on the NFC chip to link it. Stick it on the vehicle and protection starts right away.';

  @override
  String get onboard2Title => 'A report, not a nuisance';

  @override
  String get onboard2Body =>
      'When someone scans the code they pick a reason and send it. You get a notification and follow the reply from Conversations — without anyone seeing your number.';

  @override
  String get onboard3Title => 'You control what shows';

  @override
  String get onboard3Body =>
      'Under What visitors see, choose what a visitor can see: your name, phone or an emergency contact. Under Subscription you renew your plan and add more contact methods.';

  @override
  String get onboardSkip => 'Skip';

  @override
  String get onboardNext => 'Next';

  @override
  String get onboardStart => 'Get started';

  @override
  String get subStatusGrace => 'Grace period';

  @override
  String planGraceBanner(String date) {
    return 'Your subscription ended. You have until $date to renew without losing anything; after that you return to Basic and your data stays saved.';
  }

  @override
  String renewalCanceledNote(String date) {
    return 'Renewal is turned off. Your plan stays active until $date.';
  }

  @override
  String trialCardTitle(String days) {
    return 'Try Plus free for $days days';
  }

  @override
  String get trialCardBody =>
      'No payment, and only once per account. It ends by itself and does not renew.';

  @override
  String get trialStartButton => 'Start the free trial';

  @override
  String get trialConfirmTitle => 'Start the free trial?';

  @override
  String trialConfirmBody(String days) {
    return 'Plus will work for $days days, then you return to Basic. The trial cannot be repeated once used.';
  }

  @override
  String get trialStarted => 'Your free Plus trial has started.';

  @override
  String trialRunningBanner(String date) {
    return 'Your free Plus trial ends on $date. Subscribe before then to keep the features.';
  }

  @override
  String get trialEndedBanner =>
      'Your free trial has ended. Subscribe to Plus to keep using its features.';

  @override
  String get referralTitle => 'Invite a friend';

  @override
  String referralHeadline(String days) {
    return 'Invite a friend and get $days free days';
  }

  @override
  String referralBody(String days) {
    return 'Share your code. When your friend subscribes for the first time, $days days of Plus are added to your plan.';
  }

  @override
  String get referralTileBody => 'Invite a friend and earn free days of Plus';

  @override
  String get referralYourCode => 'Your invite code';

  @override
  String get referralCopy => 'Copy code';

  @override
  String get referralCopied => 'Code copied';

  @override
  String get referralShareButton => 'Share invitation';

  @override
  String referralShareText(String code, String link) {
    return 'Try Qreach SmartTag: a smart tag on your vehicle that delivers messages without revealing your number. After installing the app, enter my invite code $code\n$link';
  }

  @override
  String get referralStatInvited => 'Invited';

  @override
  String get referralStatSubscribed => 'Subscribed';

  @override
  String get referralStatDays => 'Free days';

  @override
  String get referralHaveCode => 'I have an invite code from a friend';

  @override
  String get referralCodeField => 'Invite code';

  @override
  String get referralApply => 'Confirm code';

  @override
  String get referralApplied =>
      'Invite code saved. When you subscribe, your friend gets their reward.';

  @override
  String get referralDisabledNote => 'Invitations are paused right now.';

  @override
  String get referralErrInvalid =>
      'The invite code is not correct. Check it and try again.';

  @override
  String get referralErrSelf => 'You cannot use your own invite code.';

  @override
  String get referralErrAlready => 'You already entered an invite code.';

  @override
  String get referralErrNotNew =>
      'Invite codes are for people who have not subscribed yet.';

  @override
  String get referralErrUnavailable =>
      'Invitations are not available right now.';

  @override
  String waitMinutes(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n minutes',
      one: 'a minute',
    );
    return '$_temp0';
  }

  @override
  String waitHours(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n hours',
      one: 'an hour',
    );
    return '$_temp0';
  }

  @override
  String waitDays(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n days',
      one: 'a day',
    );
    return '$_temp0';
  }

  @override
  String authLocked(String duration) {
    return 'Sign-in is paused after 3 wrong attempts. Try again in $duration.';
  }

  @override
  String otpLimit(String duration) {
    return 'You reached the limit of verification codes. Try again in $duration.';
  }

  @override
  String otpCooldown(int seconds) {
    return 'Wait $seconds seconds before asking for a new code.';
  }

  @override
  String get rateLimited => 'Too many requests. Try again in a moment.';

  @override
  String attemptsLeft(int left) {
    String _temp0 = intl.Intl.pluralLogic(
      left,
      locale: localeName,
      other: '$left attempts',
      one: '1 attempt',
    );
    return '$_temp0 left before a temporary lock.';
  }

  @override
  String get cancelAnnualPlan => 'Cancel annual subscription';

  @override
  String get cancelAnnualTitle => 'Cancel the annual subscription?';

  @override
  String cancelAnnualBody(String date) {
    return 'Your subscription will not renew. Your plan keeps working until $date and you keep every remaining day.';
  }

  @override
  String get cancelAnnualConfirm => 'Cancel renewal';

  @override
  String get keepSubscription => 'Keep subscription';

  @override
  String get resumeRenewal => 'Turn renewal back on';

  @override
  String get renewalCanceledDone =>
      'Renewal turned off. Your plan works until the end of the period.';

  @override
  String get renewalResumedDone => 'Renewal turned back on.';

  @override
  String get renewNow => 'Renew now';

  @override
  String get subStatusExpired => 'Expired';

  @override
  String get subStatusCanceled => 'Canceled';

  @override
  String get subStatusPastDue => 'Past due';

  @override
  String get subStatusTrialEnded => 'Trial ended';

  @override
  String get subStatusTrialing => 'Trial';

  @override
  String get publicContactTitle => 'Public contact details';

  @override
  String get publicContactSubtitle =>
      'Shown to anyone who scans your vehicle tag. Choose what to share.';

  @override
  String get publicContactLockedTitle => 'Available on the Plus plan';

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
  String get languageName => 'English';

  @override
  String get loadErrorTitle => 'Couldn\'t load data';

  @override
  String get homeUpgradeTitle => 'Add your social accounts with Plus';

  @override
  String get homeUpgradeBody =>
      'Plus lets you add Instagram, Facebook, Snapchat and more, and show them to visitors.';

  @override
  String tagsLinkedCount(int count) {
    return '$count tags linked';
  }

  @override
  String get activationFreeYears =>
      'The basic service of this tag is free for 10 years from the activation date.';

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
  String get tabProfile => 'My Account';

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
  String get proBadge => 'Plus';

  @override
  String get visibilityNoneShown =>
      'Nothing is shown to visitors right now — they can only message you.';

  @override
  String visibilitySharing(String items) {
    return 'Visitors see: $items';
  }

  @override
  String get visibilityLockedHint =>
      'Adding more contact accounts is available on the Plus plan.';

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

  @override
  String get sendMessage => 'Send';

  @override
  String get replyHint => 'Write your reply...';

  @override
  String threadExpires(String date) {
    return 'This conversation is deleted automatically on $date';
  }

  @override
  String get threadEmpty => 'No messages yet.';

  @override
  String get scanModeQr => 'QR';

  @override
  String get scanModeNfc => 'NFC';

  @override
  String get nfcHoldPhone => 'Hold the back of your phone against the NFC tag';

  @override
  String get nfcUnavailable => 'This device does not support NFC';

  @override
  String get nfcDisabled =>
      'Turn NFC on in your phone settings, then come back';

  @override
  String get nfcNoLink => 'No Qreach link was found on this tag';

  @override
  String get showQr => 'Show QR code';

  @override
  String get codeLinkLabel => 'Link stored in the code';

  @override
  String get changePhoto => 'Change photo';

  @override
  String get takePhoto => 'Take a photo';

  @override
  String get chooseFromGallery => 'Choose from gallery';

  @override
  String get removePhoto => 'Remove photo';

  @override
  String get photoUpdated => 'Photo updated';

  @override
  String get chooseLanguage => 'Choose language';

  @override
  String get languageArabic => 'العربية';

  @override
  String get languageEnglish => 'English';

  @override
  String get languageKurdish => 'کوردی';

  @override
  String get plateTypeTitle => 'Plate type';

  @override
  String get plateLetter => 'Letter';

  @override
  String get plateGovernorate => 'Governorate';

  @override
  String get plateTypePrivate => 'Private';

  @override
  String get plateTypeGovernment => 'Government';

  @override
  String get plateTypeTruck => 'Truck';

  @override
  String get plateTypeTaxi => 'Taxi';

  @override
  String get plateTypeAgricultural => 'Agricultural';

  @override
  String get homeLinked => 'Linked';

  @override
  String get homeNotLinked => 'Not linked';

  @override
  String get homeOwnerLabel => 'Owner';

  @override
  String get homeConversations => 'Latest conversations';

  @override
  String get homeConversationsEmpty => 'No conversations yet.';

  @override
  String get homeActivities => 'Activities';

  @override
  String get actLatest => 'Latest activity';

  @override
  String get actCommunication => 'Latest messages';

  @override
  String get actAccount => 'My account';

  @override
  String get actReports => 'Reports';

  @override
  String get actNone => 'Nothing yet';

  @override
  String get youPrefix => 'You: ';

  @override
  String get visShowName => 'Show my name to visitors';

  @override
  String get visNameHint =>
      'Hidden by default. Visitors only see your name if you turn this on.';

  @override
  String get visShowAvatar => 'Show my photo to visitors';

  @override
  String get visAvatarHint =>
      'Hidden by default. Add a photo in your profile first.';

  @override
  String get visAvatarLabel => 'My photo';

  @override
  String get visShowCover => 'Show my cover photo to visitors';

  @override
  String get visCoverHint =>
      'Hidden by default. Add a cover photo in your profile first.';

  @override
  String get visCoverLabel => 'Cover photo';

  @override
  String get emergencySection => 'Emergency contact';

  @override
  String get emergencyHint =>
      'Such as a parent — shown to visitors only if you turn it on.';

  @override
  String get emergencyName => 'Name';

  @override
  String get emergencyRelation => 'Relationship (father, mother...)';

  @override
  String get emergencyPhone => 'Phone number';

  @override
  String get pickFromContacts => 'Choose from contacts';

  @override
  String get methodsSection => 'More contact methods';

  @override
  String get addMethod => 'Add a contact method';

  @override
  String get methodValue => 'Account or number';

  @override
  String get methodLabel => 'Display name';

  @override
  String get methodsLockedTitle => 'Available on Plus';

  @override
  String get methodsLockedBody =>
      'Add Instagram, Facebook, Snapchat, TikTok, WhatsApp and more, and choose what visitors see.';

  @override
  String get upgradeToPlus => 'Upgrade to Plus';

  @override
  String get removeMethod => 'Remove';

  @override
  String get methodsEmpty => 'You have not added any contact method yet.';

  @override
  String get pickup => 'Pickup';

  @override
  String get bus => 'Bus';

  @override
  String get serverSetup => 'Server setup';

  @override
  String get serverAddress => 'Server address (IP:port)';

  @override
  String get serverSetupHelp =>
      'Enter the server the app connects to, such as 192.168.1.10:8000 or a domain name. It is saved on this phone only.';

  @override
  String get serverTest => 'Test connection';

  @override
  String get serverOk => 'Connected to the server';

  @override
  String get serverFail =>
      'Could not reach the server. Check the address and port, and that the phone is on the same network.';

  @override
  String get serverInvalid => 'Invalid address';

  @override
  String get serverHttpsOnly =>
      'The release build only accepts https addresses';

  @override
  String get serverSaved => 'Server address saved';

  @override
  String get serverReset => 'Reset to default';

  @override
  String get serverResetDone => 'Address reset to default';

  @override
  String get appearance => 'Appearance';

  @override
  String get themeSystem => 'System';

  @override
  String get themeLight => 'Light';

  @override
  String get themeDark => 'Dark';

  @override
  String get sessionReplacedNotice =>
      'You were signed out because your account was opened on another device.';

  @override
  String get disableVehicle => 'Disable vehicle';

  @override
  String get enableVehicle => 'Reactivate vehicle';

  @override
  String get disableVehicleTitle => 'Disable this vehicle?';

  @override
  String get disableVehicleBody =>
      'While it is disabled, scanning its QR code or NFC tag shows an unavailable page: visitors cannot contact you and no alerts reach you. Your code, details and past conversations are kept, and you can reactivate the vehicle at any time.';

  @override
  String get disableConfirm => 'Disable';

  @override
  String get enableVehicleTitle => 'Reactivate this vehicle?';

  @override
  String get enableVehicleBody =>
      'Visitors who scan its code will be able to contact you again.';

  @override
  String get enableConfirm => 'Reactivate';

  @override
  String get vehicleDisabledBanner =>
      'This vehicle is disabled. Its code shows an unavailable page to visitors.';

  @override
  String get vehicleDisabledDone => 'Vehicle disabled';

  @override
  String get vehicleEnabledDone => 'Vehicle reactivated';

  @override
  String get deleteVehicleTitle => 'Delete this vehicle?';

  @override
  String get deleteVehicleBody =>
      'The vehicle is removed from your account and its code stops working. This cannot be undone. If you only want to pause it, disable it instead.';

  @override
  String get attachPhoto => 'Attach a photo';

  @override
  String get removeAttachment => 'Remove photo';

  @override
  String get imageTooLarge => 'That photo is too large (8 MB maximum).';

  @override
  String get imageTypeUnsupported =>
      'Unsupported file. Use a JPG, PNG or WebP photo.';

  @override
  String get cameraPermissionDenied =>
      'Allow camera access in your phone settings to take a photo.';

  @override
  String get photosPermissionDenied =>
      'Allow photo access in your phone settings to choose a photo.';

  @override
  String get imageSendFailed => 'The photo could not be sent. Try again.';

  @override
  String get payCheckoutTitle => 'Subscribe';

  @override
  String get payReviewTitle => 'Order summary';

  @override
  String get payPriceLabel => 'Price';

  @override
  String get payBenefitsTitle => 'What you get';

  @override
  String get payMethodTitle => 'Payment method';

  @override
  String get payNoMethods =>
      'No payment method is available right now. Please try again later.';

  @override
  String get payTestBadge => 'Test';

  @override
  String get payNow => 'Pay now';

  @override
  String get payNote =>
      'You will be taken to a secure payment page. Your plan is activated only after the payment is confirmed.';

  @override
  String get payStatusTitle => 'Payment';

  @override
  String get payChecking => 'Confirming your payment…';

  @override
  String get payCheckingHint =>
      'This usually takes a few seconds. Your plan is activated as soon as the payment is confirmed.';

  @override
  String get paySuccessTitle => 'Payment successful';

  @override
  String get payFailedTitle => 'The payment did not go through';

  @override
  String get payFailedHint => 'You were not charged. You can try again.';

  @override
  String get payFailedExpired =>
      'The payment window expired before it was completed.';

  @override
  String get payTryAgain => 'Try again';

  @override
  String get payDone => 'Done';

  @override
  String get payReceiptTitle => 'Receipt';

  @override
  String get payReceiptNumber => 'Receipt no.';

  @override
  String get payReceiptPlan => 'Plan';

  @override
  String get payReceiptAmount => 'Amount';

  @override
  String get payReceiptMethod => 'Payment method';

  @override
  String get payReceiptDate => 'Date';

  @override
  String get payReceiptValidUntil => 'Valid until';

  @override
  String get payOpenFailed => 'Could not open the payment page.';

  @override
  String get payTestNotice =>
      'This was a test payment. Nothing real was charged.';

  @override
  String get paySubscribeButton => 'Subscribe to this plan';

  @override
  String get tagTypeCode => 'QR + NFC tag';

  @override
  String get pickerSearch => 'Search';

  @override
  String get pickerOther => 'Other';

  @override
  String get pickerChoose => 'Choose';

  @override
  String get pickerNoResults => 'No matches';

  @override
  String get modelHint => 'Type it, e.g. G class';

  @override
  String get editVehicle => 'Edit vehicle';

  @override
  String get editVehicleAction => 'Edit';

  @override
  String get vinLabel => 'VIN (optional)';

  @override
  String get vinInvalid =>
      'Invalid VIN (Latin letters and digits, no I, O or Q).';

  @override
  String get copyLink => 'Copy link';

  @override
  String get linkCopied => 'Link copied';
}
