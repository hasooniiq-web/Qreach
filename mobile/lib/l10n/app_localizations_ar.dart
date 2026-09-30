// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appName => 'Qreach SmartTag';

  @override
  String get welcomeTitle => 'مرحبًا بك في Qreach';

  @override
  String get welcomeSubtitle => 'أضف رمز SmartTag إلى مركبتك للتواصل بأمان';

  @override
  String get phoneLabel => 'رقم الهاتف';

  @override
  String get phoneHint => '7xxxxxxxxx';

  @override
  String get sendCode => 'إرسال رمز التحقق';

  @override
  String get otpTitle => 'أدخل رمز التحقق';

  @override
  String otpSubtitle(String phone) {
    return 'أرسلنا رمزًا مكونًا من 6 أرقام إلى $phone';
  }

  @override
  String get verify => 'تحقق';

  @override
  String get resendCode => 'إعادة إرسال الرمز';

  @override
  String resendIn(int seconds) {
    return 'إعادة الإرسال بعد $seconds ثانية';
  }

  @override
  String get invalidOtp => 'رمز التحقق غير صحيح';

  @override
  String homeGreetingWithName(String name) {
    return 'مرحبًا، $name';
  }

  @override
  String get homeGreetingNoName => 'مرحبًا بك';

  @override
  String get quickActions => 'إجراءات سريعة';

  @override
  String get notifications => 'التنبيهات';

  @override
  String get notificationsComingSoonTitle => 'الإشعارات قريبًا';

  @override
  String get notificationsComingSoonSubtitle =>
      'نعمل على إضافة مركز إشعارات كامل لعمليات المسح والنشاط.';

  @override
  String get vehicleProtected => 'محمية';

  @override
  String get vehicleNotProtectedYet => 'غير محمية بعد';

  @override
  String get noVehicleYetHomeTitle => 'أضف مركبتك الأولى';

  @override
  String get noVehicleYetHomeSubtitle => 'أضف مركبة للبدء في استخدام SmartTag.';

  @override
  String get myVehicles => 'مركباتي';

  @override
  String get addVehicle => 'إضافة مركبة';

  @override
  String get noVehiclesTitle => 'لا توجد مركبات بعد';

  @override
  String get noVehiclesSubtitle =>
      'أضف مركبتك الأولى للبدء في استخدام SmartTag';

  @override
  String get plateNumber => 'رقم اللوحة';

  @override
  String get nickname => 'الاسم (اختياري)';

  @override
  String get make => 'الشركة المصنعة';

  @override
  String get model => 'الطراز';

  @override
  String get year => 'سنة الصنع';

  @override
  String get color => 'اللون';

  @override
  String get vehicleType => 'نوع المركبة';

  @override
  String get save => 'حفظ';

  @override
  String get cancel => 'إلغاء';

  @override
  String get linkedTags => 'الرموز المرتبطة';

  @override
  String get noTagsYet => 'لا توجد رموز مرتبطة بهذه المركبة';

  @override
  String get linkNewTag => 'ربط رمز جديد';

  @override
  String get scanTagInstructions =>
      'وجّه الكاميرا نحو رمز QR الموجود على الملصق';

  @override
  String get activating => 'جارٍ الربط...';

  @override
  String get activationSuccess => 'تم ربط الرمز بنجاح';

  @override
  String get tagAlreadyActivated => 'هذا الرمز مُفعّل بالفعل';

  @override
  String get tagRevoked => 'تم إلغاء هذا الرمز';

  @override
  String get tagNotFound => 'الرمز غير صالح';

  @override
  String get profile => 'حسابي';

  @override
  String get logout => 'تسجيل الخروج';

  @override
  String get logoutAllDevices => 'تسجيل الخروج من جميع الأجهزة';

  @override
  String get logoutConfirm => 'هل أنت متأكد من تسجيل الخروج؟';

  @override
  String get settings => 'الإعدادات';

  @override
  String get language => 'اللغة';

  @override
  String get sedan => 'سيدان';

  @override
  String get suv => 'دفع رباعي';

  @override
  String get truck => 'شاحنة';

  @override
  String get motorcycle => 'دراجة نارية';

  @override
  String get van => 'فان';

  @override
  String get other => 'أخرى';

  @override
  String get statusActive => 'نشط';

  @override
  String get statusAssigned => 'مربوط';

  @override
  String get statusRevoked => 'ملغى';

  @override
  String get genericError => 'حدث خطأ ما. حاول مرة أخرى.';

  @override
  String get networkError => 'تعذر الاتصال بالخادم. تحقق من اتصالك بالإنترنت.';

  @override
  String get retry => 'إعادة المحاولة';

  @override
  String get loading => 'جارٍ التحميل...';

  @override
  String get delete => 'حذف';

  @override
  String get deleteVehicleConfirm => 'هل أنت متأكد من حذف هذه المركبة؟';

  @override
  String get requiredField => 'هذا الحقل مطلوب';

  @override
  String get invalidPhone => 'رقم هاتف غير صالح';

  @override
  String get subscriptionTitle => 'الاشتراك';

  @override
  String get currentPlan => 'باقتك الحالية';

  @override
  String get planDefaultFree => 'الباقة الافتراضية المجانية';

  @override
  String planEndsOn(String date) {
    return 'ينتهي في $date';
  }

  @override
  String planExpiredBanner(String date) {
    return 'انتهى اشتراكك في $date. عدت إلى الباقة الأساسية.';
  }

  @override
  String get planFeaturesTitle => 'ما تتيحه باقتك';

  @override
  String get featureMessaging => 'استقبال رسائل الزوار';

  @override
  String get featurePublicPhone => 'عرض رقم هاتفك للزوار';

  @override
  String get featurePublicInstagram =>
      'إضافة حسابات التواصل (إنستغرام، فيسبوك، سناب...)';

  @override
  String get featurePublicExtra => 'التحكم بما يراه الزائر من كل حساب';

  @override
  String get plansTitle => 'الباقات';

  @override
  String get planYourPlan => 'باقتك';

  @override
  String get priceFree => 'مجاني';

  @override
  String priceAmountYear(String amount) {
    return '$amount د.ع / سنة';
  }

  @override
  String priceAmountDays(String amount, int days) {
    return '$amount د.ع / $days يومًا';
  }

  @override
  String get requestUpgrade => 'طلب الترقية';

  @override
  String get upgradePending => 'طلبك قيد المراجعة';

  @override
  String upgradeConfirmTitle(String plan) {
    return 'طلب الترقية إلى $plan؟';
  }

  @override
  String get upgradeConfirmBody =>
      'سيراجع فريق Qreach طلبك ويتواصل معك لإتمام الاشتراك وتفعيل الباقة.';

  @override
  String get upgradeSent => 'تم إرسال طلب الترقية';

  @override
  String get subStatusActive => 'فعّال';

  @override
  String get subStatusExpired => 'منتهي';

  @override
  String get subStatusCanceled => 'ملغى';

  @override
  String get subStatusPastDue => 'متأخر السداد';

  @override
  String get subStatusTrialing => 'تجريبي';

  @override
  String get publicContactTitle => 'بيانات التواصل العامة';

  @override
  String get publicContactSubtitle =>
      'تظهر لأي شخص يمسح رمز مركبتك. اختر ما تريد مشاركته.';

  @override
  String get publicContactLockedTitle => 'متاحة في باقة Plus';

  @override
  String get publicContactLockedBody =>
      'في الباقة الأساسية يستطيع الزوار مراسلتك فقط. رقم هاتفك وحسابك لا يظهران لهم.';

  @override
  String get viewPlans => 'عرض الباقات';

  @override
  String get phoneShownLabel => 'رقم الهاتف الذي يظهر للزوار';

  @override
  String get instagramLabel => 'حساب إنستغرام';

  @override
  String get instagramHint => 'اسم المستخدم فقط';

  @override
  String get extraLabel => 'معلومات إضافية';

  @override
  String get extraHint => 'مثال: متاح بعد الخامسة مساءً';

  @override
  String get saved => 'تم الحفظ';

  @override
  String get invalidInstagram => 'اسم مستخدم غير صالح';

  @override
  String get settingsAccount => 'الحساب';

  @override
  String get settingsSecurity => 'الأمان';

  @override
  String get settingsPreferences => 'التفضيلات';

  @override
  String get languageName => 'العربية';

  @override
  String get loadErrorTitle => 'تعذّر تحميل البيانات';

  @override
  String get homeUpgradeTitle => 'أضف حسابات التواصل مع Plus';

  @override
  String get homeUpgradeBody =>
      'باقة Plus تتيح إضافة إنستغرام وفيسبوك وسناب وغيرها وعرضها للزائر.';

  @override
  String tagsLinkedCount(int count) {
    return '$count رمز مرتبط';
  }

  @override
  String get activationSuccessSubtitle =>
      'أصبح بإمكان أي شخص مسح الرمز والتواصل معك.';

  @override
  String get done => 'تم';

  @override
  String get protectionStatus => 'حالة الحماية';

  @override
  String get vehicleDetails => 'تفاصيل المركبة';

  @override
  String get tagTypeQr => 'رمز QR';

  @override
  String get tagTypeNfc => 'رمز NFC';

  @override
  String get vehicleFormIdentity => 'هوية المركبة';

  @override
  String get vehicleFormMore => 'تفاصيل إضافية';

  @override
  String get vehicleFormNicknameHint => 'اسم يميّز مركبتك عن غيرها';

  @override
  String get notificationsEmptyTitle => 'لا توجد إشعارات بعد';

  @override
  String get notificationsEmptySubtitle =>
      'ستظهر هنا تنبيهات عندما يتفاعل أحدهم مع رمز مركبتك.';

  @override
  String get markAllRead => 'تحديد الكل كمقروء';

  @override
  String get groupToday => 'اليوم';

  @override
  String get groupYesterday => 'أمس';

  @override
  String get groupEarlier => 'سابقًا';

  @override
  String get recentActivity => 'آخر النشاط';

  @override
  String get viewAll => 'عرض الكل';

  @override
  String get unreadLabel => 'غير مقروء';

  @override
  String get emergencyLabel => 'طارئ';

  @override
  String get noRecentActivity =>
      'لا يوجد نشاط بعد. عندما يمسح أحدهم رمز مركبتك ستراه هنا.';

  @override
  String get tabHome => 'الرئيسية';

  @override
  String get tabVehicles => 'مركباتي';

  @override
  String get tabContacts => 'التواصل';

  @override
  String get tabProfile => 'حسابي';

  @override
  String get contactsTitle => 'طلبات التواصل';

  @override
  String get contactsEmptyTitle => 'لا توجد رسائل بعد';

  @override
  String get contactsEmptySubtitle =>
      'عندما يراسلك أحدهم بعد مسح رمز مركبتك ستظهر رسالته هنا.';

  @override
  String get contactStatusNew => 'جديد';

  @override
  String get contactStatusRead => 'مقروء';

  @override
  String get contactStatusResponded => 'تم الرد';

  @override
  String get contactStatusClosed => 'مغلق';

  @override
  String get markResponded => 'تحديد أنه تم الرد';

  @override
  String get callVisitor => 'اتصال بالزائر';

  @override
  String get visitorAnonymous => 'زائر';

  @override
  String contactAboutVehicle(String vehicle) {
    return 'بخصوص $vehicle';
  }

  @override
  String get vehicleCodeTitle => 'رمز المركبة';

  @override
  String get removeCode => 'حذف الرمز';

  @override
  String get removeCodeConfirmTitle => 'حذف رمز المركبة؟';

  @override
  String get removeCodeConfirmBody =>
      'سيتوقف الرمز الحالي عن العمل نهائيًا ولا يمكن ربطه من جديد. بعدها تستطيع ربط رمز جديد.';

  @override
  String get codeRemoved => 'تم حذف الرمز';

  @override
  String get oneCodeNote =>
      'لكل مركبة رمز واحد. لتبديله احذف الحالي ثم اربط الجديد.';

  @override
  String get noCodeYetTitle => 'لا يوجد رمز مرتبط';

  @override
  String get noCodeYetSubtitle =>
      'امسح رمز الملصق لتصبح المركبة قابلة للتواصل.';

  @override
  String get profileMyInfo => 'معلوماتي';

  @override
  String get profileName => 'الاسم';

  @override
  String get profileNameHint => 'اسمك كما تريد أن يظهر';

  @override
  String get editName => 'تعديل الاسم';

  @override
  String get notSet => 'غير محدد';

  @override
  String get visibilityTitle => 'ما يظهر للزائر';

  @override
  String get visibilitySubtitle =>
      'افتراضيًا لا يرى الزائر شيئًا سوى إرسال رسالة لك. أضف معلوماتك واختر ما تريد إظهاره.';

  @override
  String get showToVisitors => 'إظهار للزوار';

  @override
  String get proBadge => 'Plus';

  @override
  String get visibilityNoneShown =>
      'لا يظهر للزائر أي شيء الآن — يستطيع مراسلتك فقط.';

  @override
  String visibilitySharing(String items) {
    return 'يظهر للزائر: $items';
  }

  @override
  String get visibilityLockedHint =>
      'إضافة حسابات تواصل أخرى متاحة في باقة Plus.';

  @override
  String get scanManualTitle => 'إدخال الرمز يدويًا';

  @override
  String get scanManualHint => 'ألصق رابط الرمز أو الرمز نفسه';

  @override
  String get scanManualButton => 'ربط';

  @override
  String get scanCameraError => 'تعذّر تشغيل الكاميرا';

  @override
  String get scanCameraPermission =>
      'اسمح للتطبيق باستخدام الكاميرا من إعدادات الهاتف ثم أعد المحاولة، أو أدخل الرمز يدويًا.';

  @override
  String get scanTorch => 'الفلاش';

  @override
  String get homeStatVehicles => 'مركبات';

  @override
  String get homeStatNewMessages => 'رسائل جديدة';

  @override
  String get homeLinkCode => 'ربط رمز';

  @override
  String get homeNoCodeCta => 'اربط رمز المركبة';

  @override
  String get sendMessage => 'إرسال';

  @override
  String get replyHint => 'اكتب ردّك...';

  @override
  String threadExpires(String date) {
    return 'تُحذف هذه المحادثة تلقائيًا في $date';
  }

  @override
  String get threadEmpty => 'لا توجد رسائل بعد.';

  @override
  String get scanModeQr => 'QR';

  @override
  String get scanModeNfc => 'NFC';

  @override
  String get nfcHoldPhone => 'قرّب ظهر الهاتف من شريحة NFC على المركبة';

  @override
  String get nfcUnavailable => 'هذا الجهاز لا يدعم NFC';

  @override
  String get nfcDisabled => 'فعّل NFC من إعدادات الهاتف ثم عد إلى هنا';

  @override
  String get nfcNoLink => 'لم نجد رابط Qreach في هذه الشريحة';

  @override
  String get showQr => 'عرض رمز QR';

  @override
  String get codeLinkLabel => 'الرابط المشفّر في الرمز';

  @override
  String get changePhoto => 'تغيير الصورة';

  @override
  String get takePhoto => 'التقاط صورة';

  @override
  String get chooseFromGallery => 'اختيار من المعرض';

  @override
  String get removePhoto => 'إزالة الصورة';

  @override
  String get photoUpdated => 'تم تحديث الصورة';

  @override
  String get chooseLanguage => 'اختر اللغة';

  @override
  String get languageArabic => 'العربية';

  @override
  String get languageEnglish => 'English';

  @override
  String get plateTypeTitle => 'نوع اللوحة';

  @override
  String get plateLetter => 'الحرف';

  @override
  String get plateGovernorate => 'المحافظة';

  @override
  String get plateTypePrivate => 'خصوصي';

  @override
  String get plateTypeGovernment => 'حكومي';

  @override
  String get plateTypeTruck => 'حمل';

  @override
  String get plateTypeTaxi => 'أجرة';

  @override
  String get plateTypeAgricultural => 'زراعي';

  @override
  String get homeLinked => 'مربوطة';

  @override
  String get homeNotLinked => 'غير مربوطة';

  @override
  String get homeOwnerLabel => 'المالك';

  @override
  String get homeConversations => 'آخر المحادثات';

  @override
  String get homeConversationsEmpty => 'لا توجد محادثات بعد.';

  @override
  String get homeActivities => 'النشاطات';

  @override
  String get actLatest => 'آخر النشاطات';

  @override
  String get actCommunication => 'آخر تواصل';

  @override
  String get actAccount => 'حسابي';

  @override
  String get actReports => 'البلاغات';

  @override
  String get actNone => 'لا شيء بعد';

  @override
  String get youPrefix => 'أنت: ';

  @override
  String get visShowName => 'إظهار اسمي للزائر';

  @override
  String get visNameHint =>
      'مخفي افتراضيًا. الزائر لا يرى اسمك إلا إذا فعّلت هذا.';

  @override
  String get emergencySection => 'جهة اتصال الطوارئ';

  @override
  String get emergencyHint =>
      'مثل الأب أو الأم — تظهر للزائر فقط إذا فعّلت العرض.';

  @override
  String get emergencyName => 'الاسم';

  @override
  String get emergencyRelation => 'الصلة (أب، أم...)';

  @override
  String get emergencyPhone => 'رقم الهاتف';

  @override
  String get methodsSection => 'طرق التواصل الإضافية';

  @override
  String get addMethod => 'إضافة طريقة تواصل';

  @override
  String get methodValue => 'الحساب أو الرقم';

  @override
  String get methodLabel => 'الاسم الظاهر';

  @override
  String get methodsLockedTitle => 'متاح في باقة Plus';

  @override
  String get methodsLockedBody =>
      'أضف إنستغرام وفيسبوك وسناب وتيك توك وواتساب وغيرها، وتحكّم بما يراه الزائر.';

  @override
  String get upgradeToPlus => 'الترقية إلى Plus';

  @override
  String get removeMethod => 'حذف';

  @override
  String get methodsEmpty => 'لم تضف أي طريقة تواصل بعد.';

  @override
  String get pickup => 'بيك أب';

  @override
  String get bus => 'حافلة';

  @override
  String get serverSetup => 'إعداد الخادم';

  @override
  String get serverAddress => 'عنوان الخادم (IP:المنفذ)';

  @override
  String get serverSetupHelp =>
      'أدخل عنوان الخادم الذي يتصل به التطبيق، مثل 192.168.1.10:8000 أو اسم نطاق. يُحفظ على هذا الهاتف فقط.';

  @override
  String get serverTest => 'اختبار الاتصال';

  @override
  String get serverOk => 'تم الاتصال بالخادم بنجاح';

  @override
  String get serverFail =>
      'تعذّر الوصول إلى الخادم. تأكد من العنوان والمنفذ وأن الهاتف على نفس الشبكة.';

  @override
  String get serverInvalid => 'العنوان غير صالح';

  @override
  String get serverHttpsOnly => 'النسخة النهائية تقبل عناوين https فقط';

  @override
  String get serverSaved => 'تم حفظ عنوان الخادم';

  @override
  String get serverReset => 'إعادة الضبط إلى الافتراضي';

  @override
  String get serverResetDone => 'رجع العنوان إلى الافتراضي';

  @override
  String get appearance => 'المظهر';

  @override
  String get themeSystem => 'حسب الهاتف';

  @override
  String get themeLight => 'فاتح';

  @override
  String get themeDark => 'داكن';
}
