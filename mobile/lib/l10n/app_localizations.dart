import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
      : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
    delegate,
    GlobalMaterialLocalizations.delegate,
    GlobalCupertinoLocalizations.delegate,
    GlobalWidgetsLocalizations.delegate,
  ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en')
  ];

  /// No description provided for @appName.
  ///
  /// In ar, this message translates to:
  /// **'Qreach SmartTag'**
  String get appName;

  /// No description provided for @welcomeTitle.
  ///
  /// In ar, this message translates to:
  /// **'مرحبًا بك في Qreach'**
  String get welcomeTitle;

  /// No description provided for @welcomeSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أضف رمز SmartTag إلى مركبتك للتواصل بأمان'**
  String get welcomeSubtitle;

  /// No description provided for @phoneLabel.
  ///
  /// In ar, this message translates to:
  /// **'رقم الهاتف'**
  String get phoneLabel;

  /// No description provided for @phoneHint.
  ///
  /// In ar, this message translates to:
  /// **'7xxxxxxxxx'**
  String get phoneHint;

  /// No description provided for @sendCode.
  ///
  /// In ar, this message translates to:
  /// **'إرسال رمز التحقق'**
  String get sendCode;

  /// No description provided for @otpTitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رمز التحقق'**
  String get otpTitle;

  /// No description provided for @otpSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أرسلنا رمزًا مكونًا من 6 أرقام إلى {phone}'**
  String otpSubtitle(String phone);

  /// No description provided for @verify.
  ///
  /// In ar, this message translates to:
  /// **'تحقق'**
  String get verify;

  /// No description provided for @resendCode.
  ///
  /// In ar, this message translates to:
  /// **'إعادة إرسال الرمز'**
  String get resendCode;

  /// No description provided for @resendIn.
  ///
  /// In ar, this message translates to:
  /// **'إعادة الإرسال بعد {seconds} ثانية'**
  String resendIn(int seconds);

  /// No description provided for @invalidOtp.
  ///
  /// In ar, this message translates to:
  /// **'رمز التحقق غير صحيح'**
  String get invalidOtp;

  /// No description provided for @myVehicles.
  ///
  /// In ar, this message translates to:
  /// **'مركباتي'**
  String get myVehicles;

  /// No description provided for @addVehicle.
  ///
  /// In ar, this message translates to:
  /// **'إضافة مركبة'**
  String get addVehicle;

  /// No description provided for @noVehiclesTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد مركبات بعد'**
  String get noVehiclesTitle;

  /// No description provided for @noVehiclesSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أضف مركبتك الأولى للبدء في استخدام SmartTag'**
  String get noVehiclesSubtitle;

  /// No description provided for @plateNumber.
  ///
  /// In ar, this message translates to:
  /// **'رقم اللوحة'**
  String get plateNumber;

  /// No description provided for @nickname.
  ///
  /// In ar, this message translates to:
  /// **'الاسم (اختياري)'**
  String get nickname;

  /// No description provided for @make.
  ///
  /// In ar, this message translates to:
  /// **'الشركة المصنعة'**
  String get make;

  /// No description provided for @model.
  ///
  /// In ar, this message translates to:
  /// **'الطراز'**
  String get model;

  /// No description provided for @year.
  ///
  /// In ar, this message translates to:
  /// **'سنة الصنع'**
  String get year;

  /// No description provided for @color.
  ///
  /// In ar, this message translates to:
  /// **'اللون'**
  String get color;

  /// No description provided for @vehicleType.
  ///
  /// In ar, this message translates to:
  /// **'نوع المركبة'**
  String get vehicleType;

  /// No description provided for @save.
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get save;

  /// No description provided for @cancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء'**
  String get cancel;

  /// No description provided for @linkedTags.
  ///
  /// In ar, this message translates to:
  /// **'الرموز المرتبطة'**
  String get linkedTags;

  /// No description provided for @noTagsYet.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رموز مرتبطة بهذه المركبة'**
  String get noTagsYet;

  /// No description provided for @linkNewTag.
  ///
  /// In ar, this message translates to:
  /// **'ربط رمز جديد'**
  String get linkNewTag;

  /// No description provided for @scanTagInstructions.
  ///
  /// In ar, this message translates to:
  /// **'وجّه الكاميرا نحو رمز QR الموجود على الملصق'**
  String get scanTagInstructions;

  /// No description provided for @activating.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ الربط...'**
  String get activating;

  /// No description provided for @activationSuccess.
  ///
  /// In ar, this message translates to:
  /// **'تم ربط الرمز بنجاح'**
  String get activationSuccess;

  /// No description provided for @tagAlreadyActivated.
  ///
  /// In ar, this message translates to:
  /// **'هذا الرمز مُفعّل بالفعل'**
  String get tagAlreadyActivated;

  /// No description provided for @tagRevoked.
  ///
  /// In ar, this message translates to:
  /// **'تم إلغاء هذا الرمز'**
  String get tagRevoked;

  /// No description provided for @tagNotFound.
  ///
  /// In ar, this message translates to:
  /// **'الرمز غير صالح'**
  String get tagNotFound;

  /// No description provided for @profile.
  ///
  /// In ar, this message translates to:
  /// **'الملف الشخصي'**
  String get profile;

  /// No description provided for @logout.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج'**
  String get logout;

  /// No description provided for @logoutAllDevices.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج من جميع الأجهزة'**
  String get logoutAllDevices;

  /// No description provided for @logoutConfirm.
  ///
  /// In ar, this message translates to:
  /// **'هل أنت متأكد من تسجيل الخروج؟'**
  String get logoutConfirm;

  /// No description provided for @settings.
  ///
  /// In ar, this message translates to:
  /// **'الإعدادات'**
  String get settings;

  /// No description provided for @language.
  ///
  /// In ar, this message translates to:
  /// **'اللغة'**
  String get language;

  /// No description provided for @sedan.
  ///
  /// In ar, this message translates to:
  /// **'سيدان'**
  String get sedan;

  /// No description provided for @suv.
  ///
  /// In ar, this message translates to:
  /// **'دفع رباعي'**
  String get suv;

  /// No description provided for @truck.
  ///
  /// In ar, this message translates to:
  /// **'شاحنة'**
  String get truck;

  /// No description provided for @motorcycle.
  ///
  /// In ar, this message translates to:
  /// **'دراجة نارية'**
  String get motorcycle;

  /// No description provided for @van.
  ///
  /// In ar, this message translates to:
  /// **'فان'**
  String get van;

  /// No description provided for @other.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get other;

  /// No description provided for @statusActive.
  ///
  /// In ar, this message translates to:
  /// **'نشط'**
  String get statusActive;

  /// No description provided for @statusAssigned.
  ///
  /// In ar, this message translates to:
  /// **'مربوط'**
  String get statusAssigned;

  /// No description provided for @statusRevoked.
  ///
  /// In ar, this message translates to:
  /// **'ملغى'**
  String get statusRevoked;

  /// No description provided for @genericError.
  ///
  /// In ar, this message translates to:
  /// **'حدث خطأ ما. حاول مرة أخرى.'**
  String get genericError;

  /// No description provided for @networkError.
  ///
  /// In ar, this message translates to:
  /// **'تعذر الاتصال بالخادم. تحقق من اتصالك بالإنترنت.'**
  String get networkError;

  /// No description provided for @retry.
  ///
  /// In ar, this message translates to:
  /// **'إعادة المحاولة'**
  String get retry;

  /// No description provided for @loading.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ التحميل...'**
  String get loading;

  /// No description provided for @delete.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get delete;

  /// No description provided for @deleteVehicleConfirm.
  ///
  /// In ar, this message translates to:
  /// **'هل أنت متأكد من حذف هذه المركبة؟'**
  String get deleteVehicleConfirm;

  /// No description provided for @requiredField.
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب'**
  String get requiredField;

  /// No description provided for @invalidPhone.
  ///
  /// In ar, this message translates to:
  /// **'رقم هاتف غير صالح'**
  String get invalidPhone;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
      'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
      'an issue with the localizations generation tool. Please file an issue '
      'on GitHub with a reproducible sample app and the gen-l10n configuration '
      'that was used.');
}
