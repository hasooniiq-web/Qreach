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
  String get createAccountButton => 'إنشاء حساب جديد';

  @override
  String get alreadyHaveAccount => 'لدي حساب بالفعل';

  @override
  String get createAccountTitle => 'إنشاء حساب جديد';

  @override
  String get createAccountSubtitle =>
      'أدخل اسمك ورقم هاتفك وكلمة مرور — سنرسل لك رمز تحقق لمرة واحدة لتأكيد رقمك';

  @override
  String get loginTitle => 'تسجيل الدخول';

  @override
  String get loginSubtitle => 'أدخل رقم هاتفك وكلمة المرور';

  @override
  String get phoneLabel => 'رقم الهاتف';

  @override
  String get phoneHint => '7xxxxxxxxx';

  @override
  String get sendCode => 'إرسال رمز التحقق';

  @override
  String get nameLabel => 'الاسم';

  @override
  String get nameHint => 'اسمك الكامل';

  @override
  String get passwordLabel => 'كلمة المرور';

  @override
  String get passwordHint => '8 أحرف على الأقل';

  @override
  String get createAccountSubmit => 'إنشاء الحساب';

  @override
  String get loginSubmit => 'تسجيل الدخول';

  @override
  String get passwordTooShort => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';

  @override
  String get forgotPasswordLink => 'هل نسيت كلمة المرور؟';

  @override
  String get forgotPasswordTitle => 'استعادة كلمة المرور';

  @override
  String get forgotPasswordSubtitle =>
      'أدخل رقم هاتفك وسنرسل لك رمز تحقق لتعيين كلمة مرور جديدة';

  @override
  String get forgotPasswordSendOtp => 'إرسال رمز التحقق';

  @override
  String get forgotPasswordCodeLabel => 'رمز التحقق';

  @override
  String get forgotPasswordNewPasswordLabel => 'كلمة المرور الجديدة';

  @override
  String get forgotPasswordSubmit => 'تعيين كلمة المرور وتسجيل الدخول';

  @override
  String get changePasswordLabel => 'تغيير كلمة المرور';

  @override
  String get setPasswordLabel => 'تعيين كلمة مرور';

  @override
  String get currentPasswordLabel => 'كلمة المرور الحالية';

  @override
  String get newPasswordLabel => 'كلمة المرور الجديدة';

  @override
  String get repeatPasswordLabel => 'تكرار كلمة المرور الجديدة';

  @override
  String get passwordsDontMatch => 'كلمتا المرور غير متطابقتين';

  @override
  String get passwordChanged => 'تم تغيير كلمة المرور';

  @override
  String get biometricSectionTitle => 'الأمان';

  @override
  String get biometricToggleLabel => 'الدخول بالبصمة أو الوجه';

  @override
  String get biometricToggleHint =>
      'افتح التطبيق دون إدخال كلمة المرور في كل مرة';

  @override
  String get biometricEnablePrompt =>
      'فعّل الدخول بالبصمة أو الوجه لهذا الحساب';

  @override
  String get lockScreenTitle => 'التطبيق مُقفل';

  @override
  String get lockScreenSubtitle => 'استخدم بصمتك أو وجهك لفتح Qreach SmartTag';

  @override
  String get lockScreenUnlockButton => 'فتح القفل';

  @override
  String get lockScreenFailed => 'تعذّر فتح القفل. حاول مرة أخرى.';

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
  String get vehicleProtected => 'مربوطة';

  @override
  String get vehicleNotProtectedYet => 'غير مربوطة';

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
  String get model => 'الموديل';

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
  String get statusAssigned => 'مربوطة';

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
  String get voucherEntryTitle => 'لديك قسيمة تفعيل؟';

  @override
  String get voucherEntryBody =>
      'أدخل الرقم المكوّن من 12 خانة أو امسح رمز QR لتفعيل الباقة.';

  @override
  String get voucherTitle => 'تفعيل بقسيمة';

  @override
  String get voucherIntro =>
      'اشتريت قسيمة تفعيل من أحد الموزّعين؟ أدخل رقمها المكوّن من 12 خانة (أحرف وأرقام) أو امسح رمز QR المطبوع عليها، فتُفعَّل باقتك فورًا.';

  @override
  String get voucherFieldLabel => 'رقم القسيمة (12 خانة)';

  @override
  String get voucherActivate => 'تفعيل';

  @override
  String get voucherScan => 'مسح رمز QR';

  @override
  String get voucherScanHint => 'وجّه الكاميرا نحو رمز QR المطبوع على القسيمة';

  @override
  String get voucherNote =>
      'تُستخدم كل قسيمة مرة واحدة فقط. إن كانت مستخدمة أو منتهية أو ملغاة فسيخبرك التطبيق.';

  @override
  String voucherSuccessTitle(String plan) {
    return 'تم تفعيل باقة $plan';
  }

  @override
  String voucherSuccessUntil(String date) {
    return 'باقتك فعّالة حتى $date.';
  }

  @override
  String voucherSuccessDays(int days) {
    return 'تم تفعيل الباقة لمدة $days يومًا.';
  }

  @override
  String get voucherErrInvalid =>
      'رقم القسيمة غير موجود. تأكد منه وأعد المحاولة.';

  @override
  String get voucherErrFormat =>
      'الرقم غير مكتمل أو غير صحيح. تأكد أنه 12 خانة من الأحرف والأرقام.';

  @override
  String get voucherErrUsed => 'هذه القسيمة مستخدمة من قبل.';

  @override
  String get voucherErrExpired => 'انتهت صلاحية هذه القسيمة.';

  @override
  String get voucherErrRevoked => 'هذه القسيمة ملغاة ولا يمكن استخدامها.';

  @override
  String get voucherErrThrottled =>
      'محاولات كثيرة خلال وقت قصير. انتظر دقيقة ثم حاول مجددًا.';

  @override
  String get voucherErrLocked =>
      'تجاوزت عدد المحاولات المسموح بها. حاول بعد ساعة.';

  @override
  String get onboard1Title => 'اربط رمزك بمركبتك';

  @override
  String get onboard1Body =>
      'أضف مركبتك، ثم امسح رمز QR أو قرّب هاتفك من شريحة NFC لربطه بها. يلصق الرمز على المركبة وتبدأ الحماية فورًا.';

  @override
  String get onboard2Title => 'يصلك بلاغ لا إزعاج';

  @override
  String get onboard2Body =>
      'عندما يمسح أحدهم الرمز يختار سبب البلاغ ويرسله، فيصلك إشعار وتتابع الرد من «المحادثات» دون أن يعرف رقمك.';

  @override
  String get onboard3Title => 'أنت تتحكم بما يظهر';

  @override
  String get onboard3Body =>
      'من «ما يظهر للزائر» اختر ما يراه الزائر: الاسم أو الهاتف أو جهة اتصال للطوارئ. وفي «الاشتراك» تجدّد باقتك وتضيف وسائل تواصل أكثر.';

  @override
  String get onboardSkip => 'تخطي';

  @override
  String get onboardNext => 'التالي';

  @override
  String get onboardStart => 'ابدأ الآن';

  @override
  String get subStatusGrace => 'فترة سماح';

  @override
  String planGraceBanner(String date) {
    return 'انتهى اشتراكك. لديك حتى $date لتجديده دون أن تخسر شيئًا، وبعدها تعود باقتك إلى الأساسية وتبقى بياناتك محفوظة.';
  }

  @override
  String renewalCanceledNote(String date) {
    return 'تم إيقاف التجديد. تبقى باقتك فعّالة حتى $date.';
  }

  @override
  String trialCardTitle(String days) {
    return 'جرّب باقة بلس مجانًا لمدة $days أيام';
  }

  @override
  String get trialCardBody =>
      'بدون أي دفع، وتُمنح مرة واحدة فقط لكل حساب. تنتهي تلقائيًا ولا تتجدد.';

  @override
  String get trialStartButton => 'ابدأ التجربة المجانية';

  @override
  String get trialConfirmTitle => 'بدء التجربة المجانية؟';

  @override
  String trialConfirmBody(String days) {
    return 'ستعمل باقة بلس لمدة $days أيام ثم تعود إلى الأساسية. لا يمكن إعادة التجربة بعد استخدامها.';
  }

  @override
  String get trialStarted => 'بدأت تجربتك المجانية لباقة بلس.';

  @override
  String trialRunningBanner(String date) {
    return 'تجربتك المجانية لباقة بلس تنتهي في $date. اشترك قبل ذلك لتبقى الميزات.';
  }

  @override
  String get trialEndedBanner =>
      'انتهت تجربتك المجانية. اشترك في بلس لتستمر في استخدام ميزاته.';

  @override
  String get referralTitle => 'ادعُ صديقًا';

  @override
  String referralHeadline(String days, String count) {
    return 'ادعُ أصدقاءك: عندما ينضم $count منهم تحصل على $days يومًا من Plus مجانًا';
  }

  @override
  String referralBody(String days, String count) {
    return 'شارك رمزك. عندما يُدخل $count من أصدقائك رمزك تُضاف $days يومًا من Plus إلى باقتك، مرة واحدة لكل حساب.';
  }

  @override
  String get referralTileBody => 'ادعُ أصدقاءك واحصل على شهر Plus مجانًا';

  @override
  String get referralYourCode => 'رمز دعوتك';

  @override
  String get referralCopy => 'نسخ الرمز';

  @override
  String get referralCopied => 'تم نسخ الرمز';

  @override
  String get referralShareButton => 'مشاركة الدعوة';

  @override
  String referralShareText(String code, String link) {
    return 'جرّب Qreach SmartTag: ملصق ذكي على مركبتك يوصل الرسائل دون كشف رقمك. بعد تثبيت التطبيق أدخل رمز دعوتي $code\n$link';
  }

  @override
  String get referralStatInvited => 'المدعوون';

  @override
  String get referralStatSubscribed => 'دعوات متبقية';

  @override
  String get referralStatDays => 'أيام مجانية';

  @override
  String get referralHaveCode => 'لديّ رمز دعوة من صديق';

  @override
  String get referralCodeField => 'رمز الدعوة';

  @override
  String get referralApply => 'تأكيد الرمز';

  @override
  String get referralApplied => 'تم حفظ رمز الدعوة. حُسبت دعوتك لصاحب الرمز.';

  @override
  String get referralDisabledNote => 'الدعوات متوقفة حاليًا.';

  @override
  String get referralErrInvalid =>
      'رمز الدعوة غير صحيح. تأكد منه وأعد المحاولة.';

  @override
  String get referralErrSelf => 'لا يمكنك استخدام رمز دعوتك أنت.';

  @override
  String get referralErrAlready => 'أدخلت رمز دعوة من قبل.';

  @override
  String get referralErrNotNew => 'رمز الدعوة للمستخدمين الذين لم يشتركوا بعد.';

  @override
  String get referralErrUnavailable => 'الدعوات غير متاحة حاليًا.';

  @override
  String waitMinutes(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n دقيقة',
      few: '$n دقائق',
      two: 'دقيقتين',
      one: 'دقيقة',
    );
    return '$_temp0';
  }

  @override
  String waitHours(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n ساعة',
      few: '$n ساعات',
      two: 'ساعتين',
      one: 'ساعة',
    );
    return '$_temp0';
  }

  @override
  String waitDays(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n يومًا',
      few: '$n أيام',
      two: 'يومين',
      one: 'يوم',
    );
    return '$_temp0';
  }

  @override
  String authLocked(String duration) {
    return 'تم إيقاف المحاولات مؤقتًا بسبب إدخال بيانات خاطئة 3 مرات. حاول مرة أخرى بعد $duration.';
  }

  @override
  String otpLimit(String duration) {
    return 'وصلت إلى الحد الأقصى لطلبات رمز التحقق. حاول مرة أخرى بعد $duration.';
  }

  @override
  String otpCooldown(int seconds) {
    return 'انتظر $seconds ثانية قبل طلب رمز جديد.';
  }

  @override
  String get rateLimited => 'عدد الطلبات كبير. حاول بعد قليل.';

  @override
  String attemptsLeft(int left) {
    String _temp0 = intl.Intl.pluralLogic(
      left,
      locale: localeName,
      other: '$left محاولة',
      few: '$left محاولات',
      two: 'محاولتان',
      one: 'محاولة واحدة',
    );
    return 'بقي لك $_temp0 قبل الإيقاف المؤقت.';
  }

  @override
  String relinkPreviousTag(String serial) {
    return 'إعادة ربط آخر رمز محذوف ($serial)';
  }

  @override
  String get codeRelinked => 'أُعيد ربط الرمز بالمركبة.';

  @override
  String get channelsTitle => 'طرق فتح الرمز';

  @override
  String get channelQr => 'رمز QR المطبوع';

  @override
  String get channelNfc => 'شريحة NFC';

  @override
  String get channelsHint =>
      'أوقف أحدهما أو كليهما: الزائر لا يستطيع فتح الرمز من الطريقة الموقوفة.';

  @override
  String get channelsBothOff =>
      'الطريقتان موقوفتان: لن يظهر الرمز لأحد حتى تُفعّل إحداهما.';

  @override
  String get referralErrFull =>
      'وصل صاحب هذا الرمز إلى الحد الأقصى من الدعوات.';

  @override
  String referralRemaining(int n) {
    return 'دعوات متبقية: $n';
  }

  @override
  String get cancelAnnualPlan => 'إلغاء الاشتراك السنوي';

  @override
  String get cancelAnnualTitle => 'إلغاء الاشتراك السنوي؟';

  @override
  String cancelAnnualBody(String date) {
    return 'لن يتجدد اشتراكك. تبقى باقتك تعمل حتى $date ولا تخسر أيًّا من الأيام المتبقية.';
  }

  @override
  String get cancelAnnualConfirm => 'إلغاء التجديد';

  @override
  String get keepSubscription => 'إبقاء الاشتراك';

  @override
  String get resumeRenewal => 'إعادة تفعيل التجديد';

  @override
  String get renewalCanceledDone =>
      'تم إيقاف التجديد. باقتك تعمل حتى نهاية المدة.';

  @override
  String get renewalResumedDone => 'تمت إعادة تفعيل التجديد.';

  @override
  String get renewNow => 'تجديد الآن';

  @override
  String get subStatusExpired => 'منتهي';

  @override
  String get subStatusCanceled => 'ملغى';

  @override
  String get subStatusPastDue => 'متأخر السداد';

  @override
  String get subStatusTrialEnded => 'انتهت التجربة';

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
  String get activationFreeYears =>
      'الخدمة الأساسية لهذا الملصق مجانية لمدة 10 سنوات من تاريخ التفعيل.';

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
  String get languageKurdish => 'کوردی';

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
  String get visShowAvatar => 'إظهار صورتي الشخصية للزائر';

  @override
  String get visAvatarHint => 'مخفية افتراضيًا. أضف صورة من ملفك الشخصي أولًا.';

  @override
  String get visAvatarLabel => 'صورتي الشخصية';

  @override
  String get visShowCover => 'إظهار صورة الغلاف للزائر';

  @override
  String get visCoverHint =>
      'مخفية افتراضيًا. أضف صورة غلاف من ملفك الشخصي أولًا.';

  @override
  String get visCoverLabel => 'صورة الغلاف';

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
  String get pickFromContacts => 'اختيار من جهات الاتصال';

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
      'تتيح لك باقة Plus إضافة إنستغرام وفيسبوك وسناب شات وتيك توك وواتساب، والتحكم بما يراه الزائر.';

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

  @override
  String get sessionReplacedNotice =>
      'تم تسجيل خروجك لأن حسابك فُتح على جهاز آخر.';

  @override
  String get disableVehicle => 'إيقاف المركبة';

  @override
  String get enableVehicle => 'إعادة تفعيل المركبة';

  @override
  String get disableVehicleTitle => 'إيقاف هذه المركبة؟';

  @override
  String get disableVehicleBody =>
      'أثناء الإيقاف، يعرض مسح رمز QR أو شريحة NFC صفحة \"غير متاح\": لن يتمكن الزوار من التواصل معك ولن تصلك أي تنبيهات. يبقى الرمز وبيانات المركبة ومحادثاتك السابقة كما هي، ويمكنك إعادة التفعيل في أي وقت.';

  @override
  String get disableConfirm => 'إيقاف';

  @override
  String get enableVehicleTitle => 'إعادة تفعيل هذه المركبة؟';

  @override
  String get enableVehicleBody =>
      'سيتمكن الزوار الذين يمسحون رمزها من التواصل معك مجددًا.';

  @override
  String get enableConfirm => 'إعادة التفعيل';

  @override
  String get vehicleDisabledBanner =>
      'هذه المركبة موقوفة. يعرض رمزها صفحة \"غير متاح\" للزوار.';

  @override
  String get vehicleDisabledDone => 'تم إيقاف المركبة';

  @override
  String get vehicleEnabledDone => 'تمت إعادة تفعيل المركبة';

  @override
  String get deleteVehicleTitle => 'حذف هذه المركبة؟';

  @override
  String get deleteVehicleBody =>
      'ستُزال المركبة من حسابك ويتوقف رمزها عن العمل. لا يمكن التراجع عن هذا الإجراء. إن أردت إيقافها مؤقتًا فقط فاستخدم \"إيقاف المركبة\" بدلًا من الحذف.';

  @override
  String get attachPhoto => 'إرفاق صورة';

  @override
  String get removeAttachment => 'إزالة الصورة';

  @override
  String get imageTooLarge => 'الصورة كبيرة جدًا (الحد الأقصى 8 ميغابايت).';

  @override
  String get imageTypeUnsupported =>
      'نوع الملف غير مدعوم. استخدم صورة JPG أو PNG أو WebP.';

  @override
  String get cameraPermissionDenied =>
      'اسمح بالوصول إلى الكاميرا من إعدادات الهاتف لالتقاط صورة.';

  @override
  String get photosPermissionDenied =>
      'اسمح بالوصول إلى الصور من إعدادات الهاتف لاختيار صورة.';

  @override
  String get imageSendFailed => 'تعذّر إرسال الصورة. حاول مرة أخرى.';

  @override
  String get payCheckoutTitle => 'الاشتراك';

  @override
  String get payReviewTitle => 'ملخص الطلب';

  @override
  String get payPriceLabel => 'السعر';

  @override
  String get payBenefitsTitle => 'ماذا ستحصل عليه';

  @override
  String get payMethodTitle => 'وسيلة الدفع';

  @override
  String get payNoMethods => 'لا توجد وسيلة دفع متاحة حاليًا. حاول لاحقًا.';

  @override
  String get payTestBadge => 'تجريبي';

  @override
  String get payNow => 'ادفع الآن';

  @override
  String get payNote =>
      'ستنتقل إلى صفحة دفع آمنة. لا تُفعَّل باقتك إلا بعد تأكيد الدفع.';

  @override
  String get payStatusTitle => 'الدفع';

  @override
  String get payChecking => 'جارٍ تأكيد الدفع…';

  @override
  String get payCheckingHint =>
      'يستغرق ذلك عادةً بضع ثوانٍ. تُفعَّل باقتك فور تأكيد الدفع.';

  @override
  String get paySuccessTitle => 'تمت عملية الدفع';

  @override
  String get payFailedTitle => 'لم تكتمل عملية الدفع';

  @override
  String get payFailedHint => 'لم يُخصم منك أي مبلغ. يمكنك المحاولة مرة أخرى.';

  @override
  String get payFailedExpired => 'انتهت مهلة الدفع قبل إتمامه.';

  @override
  String get payTryAgain => 'حاول مرة أخرى';

  @override
  String get payDone => 'تم';

  @override
  String get payReceiptTitle => 'الإيصال';

  @override
  String get payReceiptNumber => 'رقم الإيصال';

  @override
  String get payReceiptPlan => 'الباقة';

  @override
  String get payReceiptAmount => 'المبلغ';

  @override
  String get payReceiptMethod => 'وسيلة الدفع';

  @override
  String get payReceiptDate => 'التاريخ';

  @override
  String get payReceiptValidUntil => 'صالحة حتى';

  @override
  String get payOpenFailed => 'تعذّر فتح صفحة الدفع.';

  @override
  String get payTestNotice =>
      'كانت هذه عملية دفع تجريبية. لم يُخصم أي مبلغ حقيقي.';

  @override
  String get paySubscribeButton => 'اشترك في هذه الباقة';

  @override
  String get tagTypeCode => 'رمز QR + NFC';

  @override
  String get pickerSearch => 'بحث';

  @override
  String get pickerOther => 'أخرى';

  @override
  String get pickerChoose => 'اختر';

  @override
  String get pickerNoResults => 'لا توجد نتائج';

  @override
  String get modelHint => 'اكتبه بنفسك، مثل: G class';

  @override
  String get editVehicle => 'تعديل المركبة';

  @override
  String get editVehicleAction => 'تعديل';

  @override
  String get vinLabel => 'رقم الهيكل (VIN) — اختياري';

  @override
  String get vinInvalid =>
      'رقم الهيكل غير صالح (أرقام وحروف لاتينية بدون I وO وQ).';

  @override
  String get copyLink => 'نسخ الرابط';

  @override
  String get linkCopied => 'تم نسخ الرابط';

  @override
  String get plateStyleTitle => 'تصميم اللوحة';

  @override
  String get plateStyleEnglish => 'إنكليزي';

  @override
  String get plateStyleGerman => 'ألماني';

  @override
  String get plateStyleOther => 'أخرى';

  @override
  String get vehicleLimitTitle => 'وصلت إلى حد باقتك';

  @override
  String vehicleLimitFree(int max) {
    return 'الباقة المجانية تتيح مركبة واحدة فقط. فعّل باقة Plus لإضافة مركبات أخرى (حتى $max مركبات في المجموع).';
  }

  @override
  String vehicleLimitPlus(int max) {
    return 'وصلت إلى الحد الأقصى ($max مركبات) في باقتك.';
  }

  @override
  String get vehicleLimitGo => 'الانتقال إلى الاشتراك';

  @override
  String get vehicleLockedBanner =>
      'هذه المركبة غير فعّالة لأن باقتك الحالية لا تشملها. تتفعّل تلقائيًا عند تفعيل باقة Plus.';

  @override
  String get vehicleLockedBadge => 'غير فعّالة';

  @override
  String get vehicleLockedAction => 'تفعيل الباقة';

  @override
  String get scanErrorTitle => 'تعذّر استخدام هذا الرمز';

  @override
  String get scanErrorClose => 'حسنًا';

  @override
  String get navScanQr => 'مسح الرمز';

  @override
  String get scanVehicleHint => 'وجّه الكاميرا إلى رمز QR على المركبة';

  @override
  String get scanReportTitle => 'أبلغ مالك المركبة';

  @override
  String get scanReportSubtitle =>
      'اختر السبب ثم أرسل. تُحذف المحادثة تلقائيًا بعد 7 أيام.';

  @override
  String get scanReportSend => 'إرسال البلاغ';

  @override
  String get scanReportExisting =>
      'لديك محادثة مفتوحة مع هذه المركبة، وسيُضاف البلاغ إليها.';

  @override
  String scanOwnerLine(String name) {
    return 'المالك: $name';
  }

  @override
  String get scanContactsTitle => 'تواصل مباشر';

  @override
  String get reasonBlocking => 'سيارتك تعيق الطريق';

  @override
  String get reasonDamage => 'ضرر في المركبة';

  @override
  String get reasonMalfunction => 'يوجد عطل';

  @override
  String get reasonSafety => 'مشكلة سلامة';

  @override
  String get reasonAssistance => 'أحتاج مساعدة';

  @override
  String get reasonLights => 'الأضواء مشتعلة';

  @override
  String get contactStartedByYou => 'بدأتَها أنت';

  @override
  String get contactWithOwner => 'مالك المركبة';

  @override
  String get contactWithOwnerSubtitle => 'محادثة بدأتَها بعد مسح رمز المركبة';

  @override
  String get threadMenuShowIdentity =>
      'إظهار هويتي للمالك (الاسم والصورة والرقم)';

  @override
  String get threadMenuHideIdentity => 'إخفاء هويتي';

  @override
  String get threadMenuReport => 'إبلاغ عن مشكلة';

  @override
  String get identityShownSnack =>
      'يرى المالك الآن اسمك وصورتك ورقمك ويمكنه الاتصال بك.';

  @override
  String get identityHiddenSnack =>
      'أُخفي اسمك. يرى المالك أنك حساب موثّق فقط.';

  @override
  String get badgeTrusted => 'مستخدم موثوق';

  @override
  String get badgeVerified => 'حساب موثّق برقم هاتف';

  @override
  String get senderFromApp => 'من تطبيق Qreach';

  @override
  String get reportTitle => 'الإبلاغ عن مشكلة';

  @override
  String get reportSubtitle =>
      'يصل البلاغ إلى فريق Qreach، ولا يعرف الطرف الآخر أنك أبلغت.';

  @override
  String get reportReasonHarassment => 'مضايقة';

  @override
  String get reportReasonSpam => 'إزعاج متكرر';

  @override
  String get reportReasonAbuse => 'إساءة أو ألفاظ نابية';

  @override
  String get reportReasonScam => 'احتيال';

  @override
  String get reportReasonOther => 'سبب آخر';

  @override
  String get reportDetailsHint => 'تفاصيل (اختياري)';

  @override
  String get reportSent => 'وصل بلاغك إلى فريق Qreach.';

  @override
  String get supportTitle => 'الدعم الفني';

  @override
  String get supportRowValue => 'واتساب وتذاكر';

  @override
  String get supportWhatsappTitle => 'تواصل عبر واتساب';

  @override
  String get supportNoNumbers =>
      'لا توجد أرقام واتساب حاليًا. افتح تذكرة وسنرد عليك.';

  @override
  String get supportOpenTicket => 'فتح تذكرة جديدة';

  @override
  String get supportMyTickets => 'تذاكري';

  @override
  String get supportNoTickets => 'لا توجد تذاكر بعد.';

  @override
  String get supportNewTitle => 'تذكرة جديدة';

  @override
  String get supportCategory => 'نوع المشكلة';

  @override
  String get supportCatGeneral => 'استفسار عام';

  @override
  String get supportCatAccount => 'الحساب';

  @override
  String get supportCatVehicle => 'المركبة والرمز';

  @override
  String get supportCatPayment => 'الدفع والاشتراك';

  @override
  String get supportCatBug => 'مشكلة في التطبيق';

  @override
  String get supportCatOther => 'أخرى';

  @override
  String get supportSubject => 'العنوان';

  @override
  String get supportBody => 'اشرح المشكلة';

  @override
  String get supportSend => 'إرسال التذكرة';

  @override
  String get supportStatusOpen => 'بانتظار الدعم';

  @override
  String get supportStatusAnswered => 'تم الرد';

  @override
  String get supportStatusClosed => 'مغلقة';

  @override
  String get supportClosedNote =>
      'أُغلقت هذه التذكرة. افتح تذكرة جديدة إن احتجت مساعدة.';

  @override
  String get supportStaff => 'الدعم الفني';
}
