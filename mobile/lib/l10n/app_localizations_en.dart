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
}
