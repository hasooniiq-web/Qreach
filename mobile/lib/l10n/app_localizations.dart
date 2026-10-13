import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_ckb.dart';
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
    Locale('en'),
    Locale('ckb')
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

  /// No description provided for @createAccountButton.
  ///
  /// In ar, this message translates to:
  /// **'إنشاء حساب جديد'**
  String get createAccountButton;

  /// No description provided for @alreadyHaveAccount.
  ///
  /// In ar, this message translates to:
  /// **'لدي حساب بالفعل'**
  String get alreadyHaveAccount;

  /// No description provided for @createAccountTitle.
  ///
  /// In ar, this message translates to:
  /// **'إنشاء حساب جديد'**
  String get createAccountTitle;

  /// No description provided for @createAccountSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل اسمك ورقم هاتفك وكلمة مرور — سنرسل لك رمز تحقق لمرة واحدة لتأكيد رقمك'**
  String get createAccountSubtitle;

  /// No description provided for @loginTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get loginTitle;

  /// No description provided for @loginSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم هاتفك وكلمة المرور'**
  String get loginSubtitle;

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

  /// No description provided for @nameLabel.
  ///
  /// In ar, this message translates to:
  /// **'الاسم'**
  String get nameLabel;

  /// No description provided for @nameHint.
  ///
  /// In ar, this message translates to:
  /// **'اسمك الكامل'**
  String get nameHint;

  /// No description provided for @passwordLabel.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور'**
  String get passwordLabel;

  /// No description provided for @passwordHint.
  ///
  /// In ar, this message translates to:
  /// **'8 أحرف على الأقل'**
  String get passwordHint;

  /// No description provided for @createAccountSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إنشاء الحساب'**
  String get createAccountSubmit;

  /// No description provided for @loginSubmit.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get loginSubmit;

  /// No description provided for @passwordTooShort.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور يجب أن تكون 8 أحرف على الأقل'**
  String get passwordTooShort;

  /// No description provided for @forgotPasswordLink.
  ///
  /// In ar, this message translates to:
  /// **'هل نسيت كلمة المرور؟'**
  String get forgotPasswordLink;

  /// No description provided for @forgotPasswordTitle.
  ///
  /// In ar, this message translates to:
  /// **'استعادة كلمة المرور'**
  String get forgotPasswordTitle;

  /// No description provided for @forgotPasswordSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم هاتفك وسنرسل لك رمز تحقق لتعيين كلمة مرور جديدة'**
  String get forgotPasswordSubtitle;

  /// No description provided for @forgotPasswordSendOtp.
  ///
  /// In ar, this message translates to:
  /// **'إرسال رمز التحقق'**
  String get forgotPasswordSendOtp;

  /// No description provided for @forgotPasswordCodeLabel.
  ///
  /// In ar, this message translates to:
  /// **'رمز التحقق'**
  String get forgotPasswordCodeLabel;

  /// No description provided for @forgotPasswordNewPasswordLabel.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور الجديدة'**
  String get forgotPasswordNewPasswordLabel;

  /// No description provided for @forgotPasswordSubmit.
  ///
  /// In ar, this message translates to:
  /// **'تعيين كلمة المرور وتسجيل الدخول'**
  String get forgotPasswordSubmit;

  /// No description provided for @changePasswordLabel.
  ///
  /// In ar, this message translates to:
  /// **'تغيير كلمة المرور'**
  String get changePasswordLabel;

  /// No description provided for @setPasswordLabel.
  ///
  /// In ar, this message translates to:
  /// **'تعيين كلمة مرور'**
  String get setPasswordLabel;

  /// No description provided for @currentPasswordLabel.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور الحالية'**
  String get currentPasswordLabel;

  /// No description provided for @newPasswordLabel.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور الجديدة'**
  String get newPasswordLabel;

  /// No description provided for @repeatPasswordLabel.
  ///
  /// In ar, this message translates to:
  /// **'تكرار كلمة المرور الجديدة'**
  String get repeatPasswordLabel;

  /// No description provided for @passwordsDontMatch.
  ///
  /// In ar, this message translates to:
  /// **'كلمتا المرور غير متطابقتين'**
  String get passwordsDontMatch;

  /// No description provided for @passwordChanged.
  ///
  /// In ar, this message translates to:
  /// **'تم تغيير كلمة المرور'**
  String get passwordChanged;

  /// No description provided for @biometricSectionTitle.
  ///
  /// In ar, this message translates to:
  /// **'الأمان'**
  String get biometricSectionTitle;

  /// No description provided for @biometricToggleLabel.
  ///
  /// In ar, this message translates to:
  /// **'الدخول بالبصمة أو الوجه'**
  String get biometricToggleLabel;

  /// No description provided for @biometricToggleHint.
  ///
  /// In ar, this message translates to:
  /// **'افتح التطبيق دون إدخال كلمة المرور في كل مرة'**
  String get biometricToggleHint;

  /// No description provided for @biometricEnablePrompt.
  ///
  /// In ar, this message translates to:
  /// **'فعّل الدخول بالبصمة أو الوجه لهذا الحساب'**
  String get biometricEnablePrompt;

  /// No description provided for @lockScreenTitle.
  ///
  /// In ar, this message translates to:
  /// **'التطبيق مُقفل'**
  String get lockScreenTitle;

  /// No description provided for @lockScreenSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'استخدم بصمتك أو وجهك لفتح Qreach SmartTag'**
  String get lockScreenSubtitle;

  /// No description provided for @lockScreenUnlockButton.
  ///
  /// In ar, this message translates to:
  /// **'فتح القفل'**
  String get lockScreenUnlockButton;

  /// No description provided for @lockScreenFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر فتح القفل. حاول مرة أخرى.'**
  String get lockScreenFailed;

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

  /// No description provided for @homeGreetingWithName.
  ///
  /// In ar, this message translates to:
  /// **'مرحبًا، {name}'**
  String homeGreetingWithName(String name);

  /// No description provided for @homeGreetingNoName.
  ///
  /// In ar, this message translates to:
  /// **'مرحبًا بك'**
  String get homeGreetingNoName;

  /// No description provided for @quickActions.
  ///
  /// In ar, this message translates to:
  /// **'إجراءات سريعة'**
  String get quickActions;

  /// No description provided for @notifications.
  ///
  /// In ar, this message translates to:
  /// **'التنبيهات'**
  String get notifications;

  /// No description provided for @notificationsComingSoonTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات قريبًا'**
  String get notificationsComingSoonTitle;

  /// No description provided for @notificationsComingSoonSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'نعمل على إضافة مركز إشعارات كامل لعمليات المسح والنشاط.'**
  String get notificationsComingSoonSubtitle;

  /// No description provided for @vehicleProtected.
  ///
  /// In ar, this message translates to:
  /// **'مربوطة'**
  String get vehicleProtected;

  /// No description provided for @vehicleNotProtectedYet.
  ///
  /// In ar, this message translates to:
  /// **'غير مربوطة'**
  String get vehicleNotProtectedYet;

  /// No description provided for @noVehicleYetHomeTitle.
  ///
  /// In ar, this message translates to:
  /// **'أضف مركبتك الأولى'**
  String get noVehicleYetHomeTitle;

  /// No description provided for @noVehicleYetHomeSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أضف مركبة للبدء في استخدام SmartTag.'**
  String get noVehicleYetHomeSubtitle;

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
  /// **'الموديل'**
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
  /// **'حسابي'**
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
  /// **'مربوطة'**
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

  /// No description provided for @subscriptionTitle.
  ///
  /// In ar, this message translates to:
  /// **'الاشتراك'**
  String get subscriptionTitle;

  /// No description provided for @currentPlan.
  ///
  /// In ar, this message translates to:
  /// **'باقتك الحالية'**
  String get currentPlan;

  /// No description provided for @planDefaultFree.
  ///
  /// In ar, this message translates to:
  /// **'الباقة الافتراضية المجانية'**
  String get planDefaultFree;

  /// No description provided for @planEndsOn.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي في {date}'**
  String planEndsOn(String date);

  /// No description provided for @planExpiredBanner.
  ///
  /// In ar, this message translates to:
  /// **'انتهى اشتراكك في {date}. عدت إلى الباقة الأساسية.'**
  String planExpiredBanner(String date);

  /// No description provided for @planFeaturesTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما تتيحه باقتك'**
  String get planFeaturesTitle;

  /// No description provided for @featureMessaging.
  ///
  /// In ar, this message translates to:
  /// **'استقبال رسائل الزوار'**
  String get featureMessaging;

  /// No description provided for @featurePublicPhone.
  ///
  /// In ar, this message translates to:
  /// **'عرض رقم هاتفك للزوار'**
  String get featurePublicPhone;

  /// No description provided for @featurePublicInstagram.
  ///
  /// In ar, this message translates to:
  /// **'إضافة حسابات التواصل (إنستغرام، فيسبوك، سناب...)'**
  String get featurePublicInstagram;

  /// No description provided for @featurePublicExtra.
  ///
  /// In ar, this message translates to:
  /// **'التحكم بما يراه الزائر من كل حساب'**
  String get featurePublicExtra;

  /// No description provided for @plansTitle.
  ///
  /// In ar, this message translates to:
  /// **'الباقات'**
  String get plansTitle;

  /// No description provided for @planYourPlan.
  ///
  /// In ar, this message translates to:
  /// **'باقتك'**
  String get planYourPlan;

  /// No description provided for @priceFree.
  ///
  /// In ar, this message translates to:
  /// **'مجاني'**
  String get priceFree;

  /// No description provided for @priceAmountYear.
  ///
  /// In ar, this message translates to:
  /// **'{amount} د.ع / سنة'**
  String priceAmountYear(String amount);

  /// No description provided for @priceAmountDays.
  ///
  /// In ar, this message translates to:
  /// **'{amount} د.ع / {days} يومًا'**
  String priceAmountDays(String amount, int days);

  /// No description provided for @requestUpgrade.
  ///
  /// In ar, this message translates to:
  /// **'طلب الترقية'**
  String get requestUpgrade;

  /// No description provided for @upgradePending.
  ///
  /// In ar, this message translates to:
  /// **'طلبك قيد المراجعة'**
  String get upgradePending;

  /// No description provided for @upgradeConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'طلب الترقية إلى {plan}؟'**
  String upgradeConfirmTitle(String plan);

  /// No description provided for @upgradeConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'سيراجع فريق Qreach طلبك ويتواصل معك لإتمام الاشتراك وتفعيل الباقة.'**
  String get upgradeConfirmBody;

  /// No description provided for @upgradeSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلب الترقية'**
  String get upgradeSent;

  /// No description provided for @subStatusActive.
  ///
  /// In ar, this message translates to:
  /// **'فعّال'**
  String get subStatusActive;

  /// No description provided for @voucherEntryTitle.
  ///
  /// In ar, this message translates to:
  /// **'لديك قسيمة تفعيل؟'**
  String get voucherEntryTitle;

  /// No description provided for @voucherEntryBody.
  ///
  /// In ar, this message translates to:
  /// **'أدخل الرقم المكوّن من 16 خانة أو امسح رمز QR لتفعيل الباقة.'**
  String get voucherEntryBody;

  /// No description provided for @voucherTitle.
  ///
  /// In ar, this message translates to:
  /// **'تفعيل بقسيمة'**
  String get voucherTitle;

  /// No description provided for @voucherIntro.
  ///
  /// In ar, this message translates to:
  /// **'اشتريت قسيمة تفعيل من أحد الموزّعين؟ أدخل رقمها المكوّن من 16 خانة أو امسح رمز QR المطبوع عليها، فتُفعَّل باقتك فورًا.'**
  String get voucherIntro;

  /// No description provided for @voucherFieldLabel.
  ///
  /// In ar, this message translates to:
  /// **'رقم القسيمة (16 خانة)'**
  String get voucherFieldLabel;

  /// No description provided for @voucherActivate.
  ///
  /// In ar, this message translates to:
  /// **'تفعيل'**
  String get voucherActivate;

  /// No description provided for @voucherScan.
  ///
  /// In ar, this message translates to:
  /// **'مسح رمز QR'**
  String get voucherScan;

  /// No description provided for @voucherScanHint.
  ///
  /// In ar, this message translates to:
  /// **'وجّه الكاميرا نحو رمز QR المطبوع على القسيمة'**
  String get voucherScanHint;

  /// No description provided for @voucherNote.
  ///
  /// In ar, this message translates to:
  /// **'تُستخدم كل قسيمة مرة واحدة فقط. إن كانت مستخدمة أو منتهية أو ملغاة فسيخبرك التطبيق.'**
  String get voucherNote;

  /// No description provided for @voucherSuccessTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم تفعيل باقة {plan}'**
  String voucherSuccessTitle(String plan);

  /// No description provided for @voucherSuccessUntil.
  ///
  /// In ar, this message translates to:
  /// **'باقتك فعّالة حتى {date}.'**
  String voucherSuccessUntil(String date);

  /// No description provided for @voucherSuccessDays.
  ///
  /// In ar, this message translates to:
  /// **'تم تفعيل الباقة لمدة {days} يومًا.'**
  String voucherSuccessDays(int days);

  /// No description provided for @voucherErrInvalid.
  ///
  /// In ar, this message translates to:
  /// **'رقم القسيمة غير موجود. تأكد منه وأعد المحاولة.'**
  String get voucherErrInvalid;

  /// No description provided for @voucherErrFormat.
  ///
  /// In ar, this message translates to:
  /// **'الرقم غير مكتمل أو غير صحيح. تأكد أنه 16 رقمًا.'**
  String get voucherErrFormat;

  /// No description provided for @voucherErrUsed.
  ///
  /// In ar, this message translates to:
  /// **'هذه القسيمة مستخدمة من قبل.'**
  String get voucherErrUsed;

  /// No description provided for @voucherErrExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية هذه القسيمة.'**
  String get voucherErrExpired;

  /// No description provided for @voucherErrRevoked.
  ///
  /// In ar, this message translates to:
  /// **'هذه القسيمة ملغاة ولا يمكن استخدامها.'**
  String get voucherErrRevoked;

  /// No description provided for @voucherErrThrottled.
  ///
  /// In ar, this message translates to:
  /// **'محاولات كثيرة خلال وقت قصير. انتظر دقيقة ثم حاول مجددًا.'**
  String get voucherErrThrottled;

  /// No description provided for @voucherErrLocked.
  ///
  /// In ar, this message translates to:
  /// **'تجاوزت عدد المحاولات المسموح بها. حاول بعد ساعة.'**
  String get voucherErrLocked;

  /// No description provided for @onboard1Title.
  ///
  /// In ar, this message translates to:
  /// **'اربط رمزك بمركبتك'**
  String get onboard1Title;

  /// No description provided for @onboard1Body.
  ///
  /// In ar, this message translates to:
  /// **'أضف مركبتك، ثم امسح رمز QR أو قرّب هاتفك من شريحة NFC لربطه بها. يلصق الرمز على المركبة وتبدأ الحماية فورًا.'**
  String get onboard1Body;

  /// No description provided for @onboard2Title.
  ///
  /// In ar, this message translates to:
  /// **'يصلك بلاغ لا إزعاج'**
  String get onboard2Title;

  /// No description provided for @onboard2Body.
  ///
  /// In ar, this message translates to:
  /// **'عندما يمسح أحدهم الرمز يختار سبب البلاغ ويرسله، فيصلك إشعار وتتابع الرد من «المحادثات» دون أن يعرف رقمك.'**
  String get onboard2Body;

  /// No description provided for @onboard3Title.
  ///
  /// In ar, this message translates to:
  /// **'أنت تتحكم بما يظهر'**
  String get onboard3Title;

  /// No description provided for @onboard3Body.
  ///
  /// In ar, this message translates to:
  /// **'من «ما يظهر للزائر» اختر ما يراه الزائر: الاسم أو الهاتف أو جهة اتصال للطوارئ. وفي «الاشتراك» تجدّد باقتك وتضيف وسائل تواصل أكثر.'**
  String get onboard3Body;

  /// No description provided for @onboardSkip.
  ///
  /// In ar, this message translates to:
  /// **'تخطي'**
  String get onboardSkip;

  /// No description provided for @onboardNext.
  ///
  /// In ar, this message translates to:
  /// **'التالي'**
  String get onboardNext;

  /// No description provided for @onboardStart.
  ///
  /// In ar, this message translates to:
  /// **'ابدأ الآن'**
  String get onboardStart;

  /// No description provided for @subStatusGrace.
  ///
  /// In ar, this message translates to:
  /// **'فترة سماح'**
  String get subStatusGrace;

  /// No description provided for @planGraceBanner.
  ///
  /// In ar, this message translates to:
  /// **'انتهى اشتراكك. لديك حتى {date} لتجديده دون أن تخسر شيئًا، وبعدها تعود باقتك إلى الأساسية وتبقى بياناتك محفوظة.'**
  String planGraceBanner(String date);

  /// No description provided for @renewalCanceledNote.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف التجديد. تبقى باقتك فعّالة حتى {date}.'**
  String renewalCanceledNote(String date);

  /// No description provided for @trialCardTitle.
  ///
  /// In ar, this message translates to:
  /// **'جرّب باقة بلس مجانًا لمدة {days} أيام'**
  String trialCardTitle(String days);

  /// No description provided for @trialCardBody.
  ///
  /// In ar, this message translates to:
  /// **'بدون أي دفع، وتُمنح مرة واحدة فقط لكل حساب. تنتهي تلقائيًا ولا تتجدد.'**
  String get trialCardBody;

  /// No description provided for @trialStartButton.
  ///
  /// In ar, this message translates to:
  /// **'ابدأ التجربة المجانية'**
  String get trialStartButton;

  /// No description provided for @trialConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'بدء التجربة المجانية؟'**
  String get trialConfirmTitle;

  /// No description provided for @trialConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'ستعمل باقة بلس لمدة {days} أيام ثم تعود إلى الأساسية. لا يمكن إعادة التجربة بعد استخدامها.'**
  String trialConfirmBody(String days);

  /// No description provided for @trialStarted.
  ///
  /// In ar, this message translates to:
  /// **'بدأت تجربتك المجانية لباقة بلس.'**
  String get trialStarted;

  /// No description provided for @trialRunningBanner.
  ///
  /// In ar, this message translates to:
  /// **'تجربتك المجانية لباقة بلس تنتهي في {date}. اشترك قبل ذلك لتبقى الميزات.'**
  String trialRunningBanner(String date);

  /// No description provided for @trialEndedBanner.
  ///
  /// In ar, this message translates to:
  /// **'انتهت تجربتك المجانية. اشترك في بلس لتستمر في استخدام ميزاته.'**
  String get trialEndedBanner;

  /// No description provided for @cancelAnnualPlan.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الاشتراك السنوي'**
  String get cancelAnnualPlan;

  /// No description provided for @cancelAnnualTitle.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الاشتراك السنوي؟'**
  String get cancelAnnualTitle;

  /// No description provided for @cancelAnnualBody.
  ///
  /// In ar, this message translates to:
  /// **'لن يتجدد اشتراكك. تبقى باقتك تعمل حتى {date} ولا تخسر أيًّا من الأيام المتبقية.'**
  String cancelAnnualBody(String date);

  /// No description provided for @cancelAnnualConfirm.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء التجديد'**
  String get cancelAnnualConfirm;

  /// No description provided for @keepSubscription.
  ///
  /// In ar, this message translates to:
  /// **'إبقاء الاشتراك'**
  String get keepSubscription;

  /// No description provided for @resumeRenewal.
  ///
  /// In ar, this message translates to:
  /// **'إعادة تفعيل التجديد'**
  String get resumeRenewal;

  /// No description provided for @renewalCanceledDone.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف التجديد. باقتك تعمل حتى نهاية المدة.'**
  String get renewalCanceledDone;

  /// No description provided for @renewalResumedDone.
  ///
  /// In ar, this message translates to:
  /// **'تمت إعادة تفعيل التجديد.'**
  String get renewalResumedDone;

  /// No description provided for @renewNow.
  ///
  /// In ar, this message translates to:
  /// **'تجديد الآن'**
  String get renewNow;

  /// No description provided for @subStatusExpired.
  ///
  /// In ar, this message translates to:
  /// **'منتهي'**
  String get subStatusExpired;

  /// No description provided for @subStatusCanceled.
  ///
  /// In ar, this message translates to:
  /// **'ملغى'**
  String get subStatusCanceled;

  /// No description provided for @subStatusPastDue.
  ///
  /// In ar, this message translates to:
  /// **'متأخر السداد'**
  String get subStatusPastDue;

  /// No description provided for @subStatusTrialEnded.
  ///
  /// In ar, this message translates to:
  /// **'انتهت التجربة'**
  String get subStatusTrialEnded;

  /// No description provided for @subStatusTrialing.
  ///
  /// In ar, this message translates to:
  /// **'تجريبي'**
  String get subStatusTrialing;

  /// No description provided for @publicContactTitle.
  ///
  /// In ar, this message translates to:
  /// **'بيانات التواصل العامة'**
  String get publicContactTitle;

  /// No description provided for @publicContactSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'تظهر لأي شخص يمسح رمز مركبتك. اختر ما تريد مشاركته.'**
  String get publicContactSubtitle;

  /// No description provided for @publicContactLockedTitle.
  ///
  /// In ar, this message translates to:
  /// **'متاحة في باقة Plus'**
  String get publicContactLockedTitle;

  /// No description provided for @publicContactLockedBody.
  ///
  /// In ar, this message translates to:
  /// **'في الباقة الأساسية يستطيع الزوار مراسلتك فقط. رقم هاتفك وحسابك لا يظهران لهم.'**
  String get publicContactLockedBody;

  /// No description provided for @viewPlans.
  ///
  /// In ar, this message translates to:
  /// **'عرض الباقات'**
  String get viewPlans;

  /// No description provided for @phoneShownLabel.
  ///
  /// In ar, this message translates to:
  /// **'رقم الهاتف الذي يظهر للزوار'**
  String get phoneShownLabel;

  /// No description provided for @instagramLabel.
  ///
  /// In ar, this message translates to:
  /// **'حساب إنستغرام'**
  String get instagramLabel;

  /// No description provided for @instagramHint.
  ///
  /// In ar, this message translates to:
  /// **'اسم المستخدم فقط'**
  String get instagramHint;

  /// No description provided for @extraLabel.
  ///
  /// In ar, this message translates to:
  /// **'معلومات إضافية'**
  String get extraLabel;

  /// No description provided for @extraHint.
  ///
  /// In ar, this message translates to:
  /// **'مثال: متاح بعد الخامسة مساءً'**
  String get extraHint;

  /// No description provided for @saved.
  ///
  /// In ar, this message translates to:
  /// **'تم الحفظ'**
  String get saved;

  /// No description provided for @invalidInstagram.
  ///
  /// In ar, this message translates to:
  /// **'اسم مستخدم غير صالح'**
  String get invalidInstagram;

  /// No description provided for @settingsAccount.
  ///
  /// In ar, this message translates to:
  /// **'الحساب'**
  String get settingsAccount;

  /// No description provided for @settingsSecurity.
  ///
  /// In ar, this message translates to:
  /// **'الأمان'**
  String get settingsSecurity;

  /// No description provided for @settingsPreferences.
  ///
  /// In ar, this message translates to:
  /// **'التفضيلات'**
  String get settingsPreferences;

  /// No description provided for @languageName.
  ///
  /// In ar, this message translates to:
  /// **'العربية'**
  String get languageName;

  /// No description provided for @loadErrorTitle.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر تحميل البيانات'**
  String get loadErrorTitle;

  /// No description provided for @homeUpgradeTitle.
  ///
  /// In ar, this message translates to:
  /// **'أضف حسابات التواصل مع Plus'**
  String get homeUpgradeTitle;

  /// No description provided for @homeUpgradeBody.
  ///
  /// In ar, this message translates to:
  /// **'باقة Plus تتيح إضافة إنستغرام وفيسبوك وسناب وغيرها وعرضها للزائر.'**
  String get homeUpgradeBody;

  /// No description provided for @tagsLinkedCount.
  ///
  /// In ar, this message translates to:
  /// **'{count} رمز مرتبط'**
  String tagsLinkedCount(int count);

  /// No description provided for @activationSuccessSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أصبح بإمكان أي شخص مسح الرمز والتواصل معك.'**
  String get activationSuccessSubtitle;

  /// No description provided for @done.
  ///
  /// In ar, this message translates to:
  /// **'تم'**
  String get done;

  /// No description provided for @protectionStatus.
  ///
  /// In ar, this message translates to:
  /// **'حالة الحماية'**
  String get protectionStatus;

  /// No description provided for @vehicleDetails.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل المركبة'**
  String get vehicleDetails;

  /// No description provided for @tagTypeQr.
  ///
  /// In ar, this message translates to:
  /// **'رمز QR'**
  String get tagTypeQr;

  /// No description provided for @tagTypeNfc.
  ///
  /// In ar, this message translates to:
  /// **'رمز NFC'**
  String get tagTypeNfc;

  /// No description provided for @vehicleFormIdentity.
  ///
  /// In ar, this message translates to:
  /// **'هوية المركبة'**
  String get vehicleFormIdentity;

  /// No description provided for @vehicleFormMore.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل إضافية'**
  String get vehicleFormMore;

  /// No description provided for @vehicleFormNicknameHint.
  ///
  /// In ar, this message translates to:
  /// **'اسم يميّز مركبتك عن غيرها'**
  String get vehicleFormNicknameHint;

  /// No description provided for @notificationsEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد إشعارات بعد'**
  String get notificationsEmptyTitle;

  /// No description provided for @notificationsEmptySubtitle.
  ///
  /// In ar, this message translates to:
  /// **'ستظهر هنا تنبيهات عندما يتفاعل أحدهم مع رمز مركبتك.'**
  String get notificationsEmptySubtitle;

  /// No description provided for @markAllRead.
  ///
  /// In ar, this message translates to:
  /// **'تحديد الكل كمقروء'**
  String get markAllRead;

  /// No description provided for @groupToday.
  ///
  /// In ar, this message translates to:
  /// **'اليوم'**
  String get groupToday;

  /// No description provided for @groupYesterday.
  ///
  /// In ar, this message translates to:
  /// **'أمس'**
  String get groupYesterday;

  /// No description provided for @groupEarlier.
  ///
  /// In ar, this message translates to:
  /// **'سابقًا'**
  String get groupEarlier;

  /// No description provided for @recentActivity.
  ///
  /// In ar, this message translates to:
  /// **'آخر النشاط'**
  String get recentActivity;

  /// No description provided for @viewAll.
  ///
  /// In ar, this message translates to:
  /// **'عرض الكل'**
  String get viewAll;

  /// No description provided for @unreadLabel.
  ///
  /// In ar, this message translates to:
  /// **'غير مقروء'**
  String get unreadLabel;

  /// No description provided for @emergencyLabel.
  ///
  /// In ar, this message translates to:
  /// **'طارئ'**
  String get emergencyLabel;

  /// No description provided for @noRecentActivity.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد نشاط بعد. عندما يمسح أحدهم رمز مركبتك ستراه هنا.'**
  String get noRecentActivity;

  /// No description provided for @tabHome.
  ///
  /// In ar, this message translates to:
  /// **'الرئيسية'**
  String get tabHome;

  /// No description provided for @tabVehicles.
  ///
  /// In ar, this message translates to:
  /// **'مركباتي'**
  String get tabVehicles;

  /// No description provided for @tabContacts.
  ///
  /// In ar, this message translates to:
  /// **'التواصل'**
  String get tabContacts;

  /// No description provided for @tabProfile.
  ///
  /// In ar, this message translates to:
  /// **'حسابي'**
  String get tabProfile;

  /// No description provided for @contactsTitle.
  ///
  /// In ar, this message translates to:
  /// **'طلبات التواصل'**
  String get contactsTitle;

  /// No description provided for @contactsEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رسائل بعد'**
  String get contactsEmptyTitle;

  /// No description provided for @contactsEmptySubtitle.
  ///
  /// In ar, this message translates to:
  /// **'عندما يراسلك أحدهم بعد مسح رمز مركبتك ستظهر رسالته هنا.'**
  String get contactsEmptySubtitle;

  /// No description provided for @contactStatusNew.
  ///
  /// In ar, this message translates to:
  /// **'جديد'**
  String get contactStatusNew;

  /// No description provided for @contactStatusRead.
  ///
  /// In ar, this message translates to:
  /// **'مقروء'**
  String get contactStatusRead;

  /// No description provided for @contactStatusResponded.
  ///
  /// In ar, this message translates to:
  /// **'تم الرد'**
  String get contactStatusResponded;

  /// No description provided for @contactStatusClosed.
  ///
  /// In ar, this message translates to:
  /// **'مغلق'**
  String get contactStatusClosed;

  /// No description provided for @markResponded.
  ///
  /// In ar, this message translates to:
  /// **'تحديد أنه تم الرد'**
  String get markResponded;

  /// No description provided for @callVisitor.
  ///
  /// In ar, this message translates to:
  /// **'اتصال بالزائر'**
  String get callVisitor;

  /// No description provided for @visitorAnonymous.
  ///
  /// In ar, this message translates to:
  /// **'زائر'**
  String get visitorAnonymous;

  /// No description provided for @contactAboutVehicle.
  ///
  /// In ar, this message translates to:
  /// **'بخصوص {vehicle}'**
  String contactAboutVehicle(String vehicle);

  /// No description provided for @vehicleCodeTitle.
  ///
  /// In ar, this message translates to:
  /// **'رمز المركبة'**
  String get vehicleCodeTitle;

  /// No description provided for @removeCode.
  ///
  /// In ar, this message translates to:
  /// **'حذف الرمز'**
  String get removeCode;

  /// No description provided for @removeCodeConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'حذف رمز المركبة؟'**
  String get removeCodeConfirmTitle;

  /// No description provided for @removeCodeConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'سيتوقف الرمز الحالي عن العمل نهائيًا ولا يمكن ربطه من جديد. بعدها تستطيع ربط رمز جديد.'**
  String get removeCodeConfirmBody;

  /// No description provided for @codeRemoved.
  ///
  /// In ar, this message translates to:
  /// **'تم حذف الرمز'**
  String get codeRemoved;

  /// No description provided for @oneCodeNote.
  ///
  /// In ar, this message translates to:
  /// **'لكل مركبة رمز واحد. لتبديله احذف الحالي ثم اربط الجديد.'**
  String get oneCodeNote;

  /// No description provided for @noCodeYetTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد رمز مرتبط'**
  String get noCodeYetTitle;

  /// No description provided for @noCodeYetSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'امسح رمز الملصق لتصبح المركبة قابلة للتواصل.'**
  String get noCodeYetSubtitle;

  /// No description provided for @profileMyInfo.
  ///
  /// In ar, this message translates to:
  /// **'معلوماتي'**
  String get profileMyInfo;

  /// No description provided for @profileName.
  ///
  /// In ar, this message translates to:
  /// **'الاسم'**
  String get profileName;

  /// No description provided for @profileNameHint.
  ///
  /// In ar, this message translates to:
  /// **'اسمك كما تريد أن يظهر'**
  String get profileNameHint;

  /// No description provided for @editName.
  ///
  /// In ar, this message translates to:
  /// **'تعديل الاسم'**
  String get editName;

  /// No description provided for @notSet.
  ///
  /// In ar, this message translates to:
  /// **'غير محدد'**
  String get notSet;

  /// No description provided for @visibilityTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما يظهر للزائر'**
  String get visibilityTitle;

  /// No description provided for @visibilitySubtitle.
  ///
  /// In ar, this message translates to:
  /// **'افتراضيًا لا يرى الزائر شيئًا سوى إرسال رسالة لك. أضف معلوماتك واختر ما تريد إظهاره.'**
  String get visibilitySubtitle;

  /// No description provided for @showToVisitors.
  ///
  /// In ar, this message translates to:
  /// **'إظهار للزوار'**
  String get showToVisitors;

  /// No description provided for @proBadge.
  ///
  /// In ar, this message translates to:
  /// **'Plus'**
  String get proBadge;

  /// No description provided for @visibilityNoneShown.
  ///
  /// In ar, this message translates to:
  /// **'لا يظهر للزائر أي شيء الآن — يستطيع مراسلتك فقط.'**
  String get visibilityNoneShown;

  /// No description provided for @visibilitySharing.
  ///
  /// In ar, this message translates to:
  /// **'يظهر للزائر: {items}'**
  String visibilitySharing(String items);

  /// No description provided for @visibilityLockedHint.
  ///
  /// In ar, this message translates to:
  /// **'إضافة حسابات تواصل أخرى متاحة في باقة Plus.'**
  String get visibilityLockedHint;

  /// No description provided for @scanManualTitle.
  ///
  /// In ar, this message translates to:
  /// **'إدخال الرمز يدويًا'**
  String get scanManualTitle;

  /// No description provided for @scanManualHint.
  ///
  /// In ar, this message translates to:
  /// **'ألصق رابط الرمز أو الرمز نفسه'**
  String get scanManualHint;

  /// No description provided for @scanManualButton.
  ///
  /// In ar, this message translates to:
  /// **'ربط'**
  String get scanManualButton;

  /// No description provided for @scanCameraError.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر تشغيل الكاميرا'**
  String get scanCameraError;

  /// No description provided for @scanCameraPermission.
  ///
  /// In ar, this message translates to:
  /// **'اسمح للتطبيق باستخدام الكاميرا من إعدادات الهاتف ثم أعد المحاولة، أو أدخل الرمز يدويًا.'**
  String get scanCameraPermission;

  /// No description provided for @scanTorch.
  ///
  /// In ar, this message translates to:
  /// **'الفلاش'**
  String get scanTorch;

  /// No description provided for @homeStatVehicles.
  ///
  /// In ar, this message translates to:
  /// **'مركبات'**
  String get homeStatVehicles;

  /// No description provided for @homeStatNewMessages.
  ///
  /// In ar, this message translates to:
  /// **'رسائل جديدة'**
  String get homeStatNewMessages;

  /// No description provided for @homeLinkCode.
  ///
  /// In ar, this message translates to:
  /// **'ربط رمز'**
  String get homeLinkCode;

  /// No description provided for @homeNoCodeCta.
  ///
  /// In ar, this message translates to:
  /// **'اربط رمز المركبة'**
  String get homeNoCodeCta;

  /// No description provided for @sendMessage.
  ///
  /// In ar, this message translates to:
  /// **'إرسال'**
  String get sendMessage;

  /// No description provided for @replyHint.
  ///
  /// In ar, this message translates to:
  /// **'اكتب ردّك...'**
  String get replyHint;

  /// No description provided for @threadExpires.
  ///
  /// In ar, this message translates to:
  /// **'تُحذف هذه المحادثة تلقائيًا في {date}'**
  String threadExpires(String date);

  /// No description provided for @threadEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رسائل بعد.'**
  String get threadEmpty;

  /// No description provided for @scanModeQr.
  ///
  /// In ar, this message translates to:
  /// **'QR'**
  String get scanModeQr;

  /// No description provided for @scanModeNfc.
  ///
  /// In ar, this message translates to:
  /// **'NFC'**
  String get scanModeNfc;

  /// No description provided for @nfcHoldPhone.
  ///
  /// In ar, this message translates to:
  /// **'قرّب ظهر الهاتف من شريحة NFC على المركبة'**
  String get nfcHoldPhone;

  /// No description provided for @nfcUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'هذا الجهاز لا يدعم NFC'**
  String get nfcUnavailable;

  /// No description provided for @nfcDisabled.
  ///
  /// In ar, this message translates to:
  /// **'فعّل NFC من إعدادات الهاتف ثم عد إلى هنا'**
  String get nfcDisabled;

  /// No description provided for @nfcNoLink.
  ///
  /// In ar, this message translates to:
  /// **'لم نجد رابط Qreach في هذه الشريحة'**
  String get nfcNoLink;

  /// No description provided for @showQr.
  ///
  /// In ar, this message translates to:
  /// **'عرض رمز QR'**
  String get showQr;

  /// No description provided for @codeLinkLabel.
  ///
  /// In ar, this message translates to:
  /// **'الرابط المشفّر في الرمز'**
  String get codeLinkLabel;

  /// No description provided for @changePhoto.
  ///
  /// In ar, this message translates to:
  /// **'تغيير الصورة'**
  String get changePhoto;

  /// No description provided for @takePhoto.
  ///
  /// In ar, this message translates to:
  /// **'التقاط صورة'**
  String get takePhoto;

  /// No description provided for @chooseFromGallery.
  ///
  /// In ar, this message translates to:
  /// **'اختيار من المعرض'**
  String get chooseFromGallery;

  /// No description provided for @removePhoto.
  ///
  /// In ar, this message translates to:
  /// **'إزالة الصورة'**
  String get removePhoto;

  /// No description provided for @photoUpdated.
  ///
  /// In ar, this message translates to:
  /// **'تم تحديث الصورة'**
  String get photoUpdated;

  /// No description provided for @chooseLanguage.
  ///
  /// In ar, this message translates to:
  /// **'اختر اللغة'**
  String get chooseLanguage;

  /// No description provided for @languageArabic.
  ///
  /// In ar, this message translates to:
  /// **'العربية'**
  String get languageArabic;

  /// No description provided for @languageEnglish.
  ///
  /// In ar, this message translates to:
  /// **'English'**
  String get languageEnglish;

  /// No description provided for @languageKurdish.
  ///
  /// In ar, this message translates to:
  /// **'کوردی'**
  String get languageKurdish;

  /// No description provided for @plateTypeTitle.
  ///
  /// In ar, this message translates to:
  /// **'نوع اللوحة'**
  String get plateTypeTitle;

  /// No description provided for @plateLetter.
  ///
  /// In ar, this message translates to:
  /// **'الحرف'**
  String get plateLetter;

  /// No description provided for @plateGovernorate.
  ///
  /// In ar, this message translates to:
  /// **'المحافظة'**
  String get plateGovernorate;

  /// No description provided for @plateTypePrivate.
  ///
  /// In ar, this message translates to:
  /// **'خصوصي'**
  String get plateTypePrivate;

  /// No description provided for @plateTypeGovernment.
  ///
  /// In ar, this message translates to:
  /// **'حكومي'**
  String get plateTypeGovernment;

  /// No description provided for @plateTypeTruck.
  ///
  /// In ar, this message translates to:
  /// **'حمل'**
  String get plateTypeTruck;

  /// No description provided for @plateTypeTaxi.
  ///
  /// In ar, this message translates to:
  /// **'أجرة'**
  String get plateTypeTaxi;

  /// No description provided for @plateTypeAgricultural.
  ///
  /// In ar, this message translates to:
  /// **'زراعي'**
  String get plateTypeAgricultural;

  /// No description provided for @homeLinked.
  ///
  /// In ar, this message translates to:
  /// **'مربوطة'**
  String get homeLinked;

  /// No description provided for @homeNotLinked.
  ///
  /// In ar, this message translates to:
  /// **'غير مربوطة'**
  String get homeNotLinked;

  /// No description provided for @homeOwnerLabel.
  ///
  /// In ar, this message translates to:
  /// **'المالك'**
  String get homeOwnerLabel;

  /// No description provided for @homeConversations.
  ///
  /// In ar, this message translates to:
  /// **'آخر المحادثات'**
  String get homeConversations;

  /// No description provided for @homeConversationsEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد محادثات بعد.'**
  String get homeConversationsEmpty;

  /// No description provided for @homeActivities.
  ///
  /// In ar, this message translates to:
  /// **'النشاطات'**
  String get homeActivities;

  /// No description provided for @actLatest.
  ///
  /// In ar, this message translates to:
  /// **'آخر النشاطات'**
  String get actLatest;

  /// No description provided for @actCommunication.
  ///
  /// In ar, this message translates to:
  /// **'آخر تواصل'**
  String get actCommunication;

  /// No description provided for @actAccount.
  ///
  /// In ar, this message translates to:
  /// **'حسابي'**
  String get actAccount;

  /// No description provided for @actReports.
  ///
  /// In ar, this message translates to:
  /// **'البلاغات'**
  String get actReports;

  /// No description provided for @actNone.
  ///
  /// In ar, this message translates to:
  /// **'لا شيء بعد'**
  String get actNone;

  /// No description provided for @youPrefix.
  ///
  /// In ar, this message translates to:
  /// **'أنت: '**
  String get youPrefix;

  /// No description provided for @visShowName.
  ///
  /// In ar, this message translates to:
  /// **'إظهار اسمي للزائر'**
  String get visShowName;

  /// No description provided for @visNameHint.
  ///
  /// In ar, this message translates to:
  /// **'مخفي افتراضيًا. الزائر لا يرى اسمك إلا إذا فعّلت هذا.'**
  String get visNameHint;

  /// No description provided for @visShowAvatar.
  ///
  /// In ar, this message translates to:
  /// **'إظهار صورتي الشخصية للزائر'**
  String get visShowAvatar;

  /// No description provided for @visAvatarHint.
  ///
  /// In ar, this message translates to:
  /// **'مخفية افتراضيًا. أضف صورة من ملفك الشخصي أولًا.'**
  String get visAvatarHint;

  /// No description provided for @visAvatarLabel.
  ///
  /// In ar, this message translates to:
  /// **'صورتي الشخصية'**
  String get visAvatarLabel;

  /// No description provided for @visShowCover.
  ///
  /// In ar, this message translates to:
  /// **'إظهار صورة الغلاف للزائر'**
  String get visShowCover;

  /// No description provided for @visCoverHint.
  ///
  /// In ar, this message translates to:
  /// **'مخفية افتراضيًا. أضف صورة غلاف من ملفك الشخصي أولًا.'**
  String get visCoverHint;

  /// No description provided for @visCoverLabel.
  ///
  /// In ar, this message translates to:
  /// **'صورة الغلاف'**
  String get visCoverLabel;

  /// No description provided for @emergencySection.
  ///
  /// In ar, this message translates to:
  /// **'جهة اتصال الطوارئ'**
  String get emergencySection;

  /// No description provided for @emergencyHint.
  ///
  /// In ar, this message translates to:
  /// **'مثل الأب أو الأم — تظهر للزائر فقط إذا فعّلت العرض.'**
  String get emergencyHint;

  /// No description provided for @emergencyName.
  ///
  /// In ar, this message translates to:
  /// **'الاسم'**
  String get emergencyName;

  /// No description provided for @emergencyRelation.
  ///
  /// In ar, this message translates to:
  /// **'الصلة (أب، أم...)'**
  String get emergencyRelation;

  /// No description provided for @emergencyPhone.
  ///
  /// In ar, this message translates to:
  /// **'رقم الهاتف'**
  String get emergencyPhone;

  /// No description provided for @pickFromContacts.
  ///
  /// In ar, this message translates to:
  /// **'اختيار من جهات الاتصال'**
  String get pickFromContacts;

  /// No description provided for @methodsSection.
  ///
  /// In ar, this message translates to:
  /// **'طرق التواصل الإضافية'**
  String get methodsSection;

  /// No description provided for @addMethod.
  ///
  /// In ar, this message translates to:
  /// **'إضافة طريقة تواصل'**
  String get addMethod;

  /// No description provided for @methodValue.
  ///
  /// In ar, this message translates to:
  /// **'الحساب أو الرقم'**
  String get methodValue;

  /// No description provided for @methodLabel.
  ///
  /// In ar, this message translates to:
  /// **'الاسم الظاهر'**
  String get methodLabel;

  /// No description provided for @methodsLockedTitle.
  ///
  /// In ar, this message translates to:
  /// **'متاح في باقة Plus'**
  String get methodsLockedTitle;

  /// No description provided for @methodsLockedBody.
  ///
  /// In ar, this message translates to:
  /// **'تتيح لك باقة Plus إضافة إنستغرام وفيسبوك وسناب شات وتيك توك وواتساب، والتحكم بما يراه الزائر.'**
  String get methodsLockedBody;

  /// No description provided for @upgradeToPlus.
  ///
  /// In ar, this message translates to:
  /// **'الترقية إلى Plus'**
  String get upgradeToPlus;

  /// No description provided for @removeMethod.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get removeMethod;

  /// No description provided for @methodsEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لم تضف أي طريقة تواصل بعد.'**
  String get methodsEmpty;

  /// No description provided for @pickup.
  ///
  /// In ar, this message translates to:
  /// **'بيك أب'**
  String get pickup;

  /// No description provided for @bus.
  ///
  /// In ar, this message translates to:
  /// **'حافلة'**
  String get bus;

  /// No description provided for @serverSetup.
  ///
  /// In ar, this message translates to:
  /// **'إعداد الخادم'**
  String get serverSetup;

  /// No description provided for @serverAddress.
  ///
  /// In ar, this message translates to:
  /// **'عنوان الخادم (IP:المنفذ)'**
  String get serverAddress;

  /// No description provided for @serverSetupHelp.
  ///
  /// In ar, this message translates to:
  /// **'أدخل عنوان الخادم الذي يتصل به التطبيق، مثل 192.168.1.10:8000 أو اسم نطاق. يُحفظ على هذا الهاتف فقط.'**
  String get serverSetupHelp;

  /// No description provided for @serverTest.
  ///
  /// In ar, this message translates to:
  /// **'اختبار الاتصال'**
  String get serverTest;

  /// No description provided for @serverOk.
  ///
  /// In ar, this message translates to:
  /// **'تم الاتصال بالخادم بنجاح'**
  String get serverOk;

  /// No description provided for @serverFail.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر الوصول إلى الخادم. تأكد من العنوان والمنفذ وأن الهاتف على نفس الشبكة.'**
  String get serverFail;

  /// No description provided for @serverInvalid.
  ///
  /// In ar, this message translates to:
  /// **'العنوان غير صالح'**
  String get serverInvalid;

  /// No description provided for @serverHttpsOnly.
  ///
  /// In ar, this message translates to:
  /// **'النسخة النهائية تقبل عناوين https فقط'**
  String get serverHttpsOnly;

  /// No description provided for @serverSaved.
  ///
  /// In ar, this message translates to:
  /// **'تم حفظ عنوان الخادم'**
  String get serverSaved;

  /// No description provided for @serverReset.
  ///
  /// In ar, this message translates to:
  /// **'إعادة الضبط إلى الافتراضي'**
  String get serverReset;

  /// No description provided for @serverResetDone.
  ///
  /// In ar, this message translates to:
  /// **'رجع العنوان إلى الافتراضي'**
  String get serverResetDone;

  /// No description provided for @appearance.
  ///
  /// In ar, this message translates to:
  /// **'المظهر'**
  String get appearance;

  /// No description provided for @themeSystem.
  ///
  /// In ar, this message translates to:
  /// **'حسب الهاتف'**
  String get themeSystem;

  /// No description provided for @themeLight.
  ///
  /// In ar, this message translates to:
  /// **'فاتح'**
  String get themeLight;

  /// No description provided for @themeDark.
  ///
  /// In ar, this message translates to:
  /// **'داكن'**
  String get themeDark;

  /// No description provided for @sessionReplacedNotice.
  ///
  /// In ar, this message translates to:
  /// **'تم تسجيل خروجك لأن حسابك فُتح على جهاز آخر.'**
  String get sessionReplacedNotice;

  /// No description provided for @disableVehicle.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف المركبة'**
  String get disableVehicle;

  /// No description provided for @enableVehicle.
  ///
  /// In ar, this message translates to:
  /// **'إعادة تفعيل المركبة'**
  String get enableVehicle;

  /// No description provided for @disableVehicleTitle.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف هذه المركبة؟'**
  String get disableVehicleTitle;

  /// No description provided for @disableVehicleBody.
  ///
  /// In ar, this message translates to:
  /// **'أثناء الإيقاف، يعرض مسح رمز QR أو شريحة NFC صفحة \"غير متاح\": لن يتمكن الزوار من التواصل معك ولن تصلك أي تنبيهات. يبقى الرمز وبيانات المركبة ومحادثاتك السابقة كما هي، ويمكنك إعادة التفعيل في أي وقت.'**
  String get disableVehicleBody;

  /// No description provided for @disableConfirm.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف'**
  String get disableConfirm;

  /// No description provided for @enableVehicleTitle.
  ///
  /// In ar, this message translates to:
  /// **'إعادة تفعيل هذه المركبة؟'**
  String get enableVehicleTitle;

  /// No description provided for @enableVehicleBody.
  ///
  /// In ar, this message translates to:
  /// **'سيتمكن الزوار الذين يمسحون رمزها من التواصل معك مجددًا.'**
  String get enableVehicleBody;

  /// No description provided for @enableConfirm.
  ///
  /// In ar, this message translates to:
  /// **'إعادة التفعيل'**
  String get enableConfirm;

  /// No description provided for @vehicleDisabledBanner.
  ///
  /// In ar, this message translates to:
  /// **'هذه المركبة موقوفة. يعرض رمزها صفحة \"غير متاح\" للزوار.'**
  String get vehicleDisabledBanner;

  /// No description provided for @vehicleDisabledDone.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف المركبة'**
  String get vehicleDisabledDone;

  /// No description provided for @vehicleEnabledDone.
  ///
  /// In ar, this message translates to:
  /// **'تمت إعادة تفعيل المركبة'**
  String get vehicleEnabledDone;

  /// No description provided for @deleteVehicleTitle.
  ///
  /// In ar, this message translates to:
  /// **'حذف هذه المركبة؟'**
  String get deleteVehicleTitle;

  /// No description provided for @deleteVehicleBody.
  ///
  /// In ar, this message translates to:
  /// **'ستُزال المركبة من حسابك ويتوقف رمزها عن العمل. لا يمكن التراجع عن هذا الإجراء. إن أردت إيقافها مؤقتًا فقط فاستخدم \"إيقاف المركبة\" بدلًا من الحذف.'**
  String get deleteVehicleBody;

  /// No description provided for @attachPhoto.
  ///
  /// In ar, this message translates to:
  /// **'إرفاق صورة'**
  String get attachPhoto;

  /// No description provided for @removeAttachment.
  ///
  /// In ar, this message translates to:
  /// **'إزالة الصورة'**
  String get removeAttachment;

  /// No description provided for @imageTooLarge.
  ///
  /// In ar, this message translates to:
  /// **'الصورة كبيرة جدًا (الحد الأقصى 8 ميغابايت).'**
  String get imageTooLarge;

  /// No description provided for @imageTypeUnsupported.
  ///
  /// In ar, this message translates to:
  /// **'نوع الملف غير مدعوم. استخدم صورة JPG أو PNG أو WebP.'**
  String get imageTypeUnsupported;

  /// No description provided for @cameraPermissionDenied.
  ///
  /// In ar, this message translates to:
  /// **'اسمح بالوصول إلى الكاميرا من إعدادات الهاتف لالتقاط صورة.'**
  String get cameraPermissionDenied;

  /// No description provided for @photosPermissionDenied.
  ///
  /// In ar, this message translates to:
  /// **'اسمح بالوصول إلى الصور من إعدادات الهاتف لاختيار صورة.'**
  String get photosPermissionDenied;

  /// No description provided for @imageSendFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر إرسال الصورة. حاول مرة أخرى.'**
  String get imageSendFailed;

  /// No description provided for @payCheckoutTitle.
  ///
  /// In ar, this message translates to:
  /// **'الاشتراك'**
  String get payCheckoutTitle;

  /// No description provided for @payReviewTitle.
  ///
  /// In ar, this message translates to:
  /// **'ملخص الطلب'**
  String get payReviewTitle;

  /// No description provided for @payPriceLabel.
  ///
  /// In ar, this message translates to:
  /// **'السعر'**
  String get payPriceLabel;

  /// No description provided for @payBenefitsTitle.
  ///
  /// In ar, this message translates to:
  /// **'ماذا ستحصل عليه'**
  String get payBenefitsTitle;

  /// No description provided for @payMethodTitle.
  ///
  /// In ar, this message translates to:
  /// **'وسيلة الدفع'**
  String get payMethodTitle;

  /// No description provided for @payNoMethods.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد وسيلة دفع متاحة حاليًا. حاول لاحقًا.'**
  String get payNoMethods;

  /// No description provided for @payTestBadge.
  ///
  /// In ar, this message translates to:
  /// **'تجريبي'**
  String get payTestBadge;

  /// No description provided for @payNow.
  ///
  /// In ar, this message translates to:
  /// **'ادفع الآن'**
  String get payNow;

  /// No description provided for @payNote.
  ///
  /// In ar, this message translates to:
  /// **'ستنتقل إلى صفحة دفع آمنة. لا تُفعَّل باقتك إلا بعد تأكيد الدفع.'**
  String get payNote;

  /// No description provided for @payStatusTitle.
  ///
  /// In ar, this message translates to:
  /// **'الدفع'**
  String get payStatusTitle;

  /// No description provided for @payChecking.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ تأكيد الدفع…'**
  String get payChecking;

  /// No description provided for @payCheckingHint.
  ///
  /// In ar, this message translates to:
  /// **'يستغرق ذلك عادةً بضع ثوانٍ. تُفعَّل باقتك فور تأكيد الدفع.'**
  String get payCheckingHint;

  /// No description provided for @paySuccessTitle.
  ///
  /// In ar, this message translates to:
  /// **'تمت عملية الدفع'**
  String get paySuccessTitle;

  /// No description provided for @payFailedTitle.
  ///
  /// In ar, this message translates to:
  /// **'لم تكتمل عملية الدفع'**
  String get payFailedTitle;

  /// No description provided for @payFailedHint.
  ///
  /// In ar, this message translates to:
  /// **'لم يُخصم منك أي مبلغ. يمكنك المحاولة مرة أخرى.'**
  String get payFailedHint;

  /// No description provided for @payFailedExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت مهلة الدفع قبل إتمامه.'**
  String get payFailedExpired;

  /// No description provided for @payTryAgain.
  ///
  /// In ar, this message translates to:
  /// **'حاول مرة أخرى'**
  String get payTryAgain;

  /// No description provided for @payDone.
  ///
  /// In ar, this message translates to:
  /// **'تم'**
  String get payDone;

  /// No description provided for @payReceiptTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإيصال'**
  String get payReceiptTitle;

  /// No description provided for @payReceiptNumber.
  ///
  /// In ar, this message translates to:
  /// **'رقم الإيصال'**
  String get payReceiptNumber;

  /// No description provided for @payReceiptPlan.
  ///
  /// In ar, this message translates to:
  /// **'الباقة'**
  String get payReceiptPlan;

  /// No description provided for @payReceiptAmount.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ'**
  String get payReceiptAmount;

  /// No description provided for @payReceiptMethod.
  ///
  /// In ar, this message translates to:
  /// **'وسيلة الدفع'**
  String get payReceiptMethod;

  /// No description provided for @payReceiptDate.
  ///
  /// In ar, this message translates to:
  /// **'التاريخ'**
  String get payReceiptDate;

  /// No description provided for @payReceiptValidUntil.
  ///
  /// In ar, this message translates to:
  /// **'صالحة حتى'**
  String get payReceiptValidUntil;

  /// No description provided for @payOpenFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر فتح صفحة الدفع.'**
  String get payOpenFailed;

  /// No description provided for @payTestNotice.
  ///
  /// In ar, this message translates to:
  /// **'كانت هذه عملية دفع تجريبية. لم يُخصم أي مبلغ حقيقي.'**
  String get payTestNotice;

  /// No description provided for @paySubscribeButton.
  ///
  /// In ar, this message translates to:
  /// **'اشترك في هذه الباقة'**
  String get paySubscribeButton;

  /// No description provided for @tagTypeCode.
  ///
  /// In ar, this message translates to:
  /// **'رمز QR + NFC'**
  String get tagTypeCode;

  /// No description provided for @pickerSearch.
  ///
  /// In ar, this message translates to:
  /// **'بحث'**
  String get pickerSearch;

  /// No description provided for @pickerOther.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get pickerOther;

  /// No description provided for @pickerChoose.
  ///
  /// In ar, this message translates to:
  /// **'اختر'**
  String get pickerChoose;

  /// No description provided for @pickerNoResults.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد نتائج'**
  String get pickerNoResults;

  /// No description provided for @modelHint.
  ///
  /// In ar, this message translates to:
  /// **'اكتبه بنفسك، مثل: G class'**
  String get modelHint;

  /// No description provided for @editVehicle.
  ///
  /// In ar, this message translates to:
  /// **'تعديل المركبة'**
  String get editVehicle;

  /// No description provided for @editVehicleAction.
  ///
  /// In ar, this message translates to:
  /// **'تعديل'**
  String get editVehicleAction;

  /// No description provided for @vinLabel.
  ///
  /// In ar, this message translates to:
  /// **'رقم الهيكل (VIN) — اختياري'**
  String get vinLabel;

  /// No description provided for @vinInvalid.
  ///
  /// In ar, this message translates to:
  /// **'رقم الهيكل غير صالح (أرقام وحروف لاتينية بدون I وO وQ).'**
  String get vinInvalid;

  /// No description provided for @copyLink.
  ///
  /// In ar, this message translates to:
  /// **'نسخ الرابط'**
  String get copyLink;

  /// No description provided for @linkCopied.
  ///
  /// In ar, this message translates to:
  /// **'تم نسخ الرابط'**
  String get linkCopied;
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
      <String>['ar', 'ckb', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'ckb':
      return AppLocalizationsCkb();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
      'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
      'an issue with the localizations generation tool. Please file an issue '
      'on GitHub with a reproducible sample app and the gen-l10n configuration '
      'that was used.');
}
