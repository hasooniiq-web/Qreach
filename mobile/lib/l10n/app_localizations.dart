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
  /// **'محمية'**
  String get vehicleProtected;

  /// No description provided for @vehicleNotProtectedYet.
  ///
  /// In ar, this message translates to:
  /// **'غير محمية بعد'**
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
  /// **'عرض حساب إنستغرام'**
  String get featurePublicInstagram;

  /// No description provided for @featurePublicExtra.
  ///
  /// In ar, this message translates to:
  /// **'معلومات تواصل إضافية'**
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
  /// **'متاحة في الباقة الاحترافية'**
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
  /// **'أظهر بيانات تواصلك للزوار'**
  String get homeUpgradeTitle;

  /// No description provided for @homeUpgradeBody.
  ///
  /// In ar, this message translates to:
  /// **'الباقة الاحترافية تعرض رقمك وإنستغرام عند مسح الرمز.'**
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
  /// **'الملف الشخصي'**
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
  /// **'احترافية'**
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
  /// **'إظهار المعلومات للزوار متاح في الباقة الاحترافية.'**
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
