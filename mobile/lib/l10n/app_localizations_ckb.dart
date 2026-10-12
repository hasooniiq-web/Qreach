// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Central Kurdish (`ckb`).
class AppLocalizationsCkb extends AppLocalizations {
  AppLocalizationsCkb([String locale = 'ckb']) : super(locale);

  @override
  String get appName => 'Qreach SmartTag';

  @override
  String get welcomeTitle => 'بەخێربێیت بۆ Qreach';

  @override
  String get welcomeSubtitle =>
      'کۆدی SmartTag بۆ ئۆتۆمبێلەکەت زیاد بکە تا بە سەلامەتی پەیوەندی بکرێت';

  @override
  String get createAccountButton => 'دروستکردنی هەژماری نوێ';

  @override
  String get alreadyHaveAccount => 'پێشتر هەژمارم هەیە';

  @override
  String get createAccountTitle => 'دروستکردنی هەژماری نوێ';

  @override
  String get createAccountSubtitle =>
      'ناو و ژمارەی مۆبایل و وشەی نهێنی بنووسە — کۆدێکی پشتڕاستکردنەوەی یەکجار بەکارهێنان بۆ دڵنیابوون لە ژمارەکەت دەنێرین';

  @override
  String get loginTitle => 'چوونەژوورەوە';

  @override
  String get loginSubtitle => 'ژمارەی مۆبایل و وشەی نهێنی بنووسە';

  @override
  String get phoneLabel => 'ژمارەی مۆبایل';

  @override
  String get phoneHint => '7xxxxxxxxx';

  @override
  String get sendCode => 'ناردنی کۆدی پشتڕاستکردنەوە';

  @override
  String get nameLabel => 'ناو';

  @override
  String get nameHint => 'ناوی تەواوت';

  @override
  String get passwordLabel => 'وشەی نهێنی';

  @override
  String get passwordHint => 'لانیکەم 8 پیت';

  @override
  String get createAccountSubmit => 'دروستکردنی هەژمار';

  @override
  String get loginSubmit => 'چوونەژوورەوە';

  @override
  String get passwordTooShort => 'وشەی نهێنی دەبێت لانیکەم 8 پیت بێت';

  @override
  String get forgotPasswordLink => 'وشەی نهێنیت بیرچووەتەوە؟';

  @override
  String get forgotPasswordTitle => 'گەڕاندنەوەی وشەی نهێنی';

  @override
  String get forgotPasswordSubtitle =>
      'ژمارەی مۆبایلت بنووسە، کۆدێکی پشتڕاستکردنەوە دەنێرین بۆ دانانی وشەی نهێنییەکی نوێ';

  @override
  String get forgotPasswordSendOtp => 'ناردنی کۆدی پشتڕاستکردنەوە';

  @override
  String get forgotPasswordCodeLabel => 'کۆدی پشتڕاستکردنەوە';

  @override
  String get forgotPasswordNewPasswordLabel => 'وشەی نهێنی نوێ';

  @override
  String get forgotPasswordSubmit => 'دانانی وشەی نهێنی و چوونەژوورەوە';

  @override
  String get changePasswordLabel => 'گۆڕینی وشەی نهێنی';

  @override
  String get setPasswordLabel => 'دانانی وشەی نهێنی';

  @override
  String get currentPasswordLabel => 'وشەی نهێنی ئێستا';

  @override
  String get newPasswordLabel => 'وشەی نهێنی نوێ';

  @override
  String get repeatPasswordLabel => 'دووبارەکردنەوەی وشەی نهێنی نوێ';

  @override
  String get passwordsDontMatch => 'هەردوو وشەی نهێنییەکە وەک یەک نین';

  @override
  String get passwordChanged => 'وشەی نهێنی گۆڕدرا';

  @override
  String get biometricSectionTitle => 'ئاسایش';

  @override
  String get biometricToggleLabel => 'چوونەژوورەوە بە پەنجەمۆر یان دەموچاو';

  @override
  String get biometricToggleHint =>
      'بێ نووسینی وشەی نهێنی لە هەموو جارێکدا بەرنامەکە بکەرەوە';

  @override
  String get biometricEnablePrompt =>
      'چوونەژوورەوە بە پەنجەمۆر یان دەموچاو بۆ ئەم هەژمارە چالاک بکە';

  @override
  String get lockScreenTitle => 'بەرنامەکە داخراوە';

  @override
  String get lockScreenSubtitle =>
      'پەنجەمۆر یان دەموچاوت بەکاربهێنە بۆ کردنەوەی Qreach SmartTag';

  @override
  String get lockScreenUnlockButton => 'کردنەوەی قوفڵ';

  @override
  String get lockScreenFailed =>
      'نەتوانرا قوفڵەکە بکرێتەوە. دووبارە هەوڵبدەرەوە.';

  @override
  String get otpTitle => 'کۆدی پشتڕاستکردنەوە بنووسە';

  @override
  String otpSubtitle(String phone) {
    return 'کۆدێکی 6 ژمارەییمان نارد بۆ $phone';
  }

  @override
  String get verify => 'پشتڕاستکردنەوە';

  @override
  String get resendCode => 'دووبارە ناردنی کۆد';

  @override
  String resendIn(int seconds) {
    return 'دووبارە ناردن دوای $seconds چرکە';
  }

  @override
  String get invalidOtp => 'کۆدی پشتڕاستکردنەوە دروست نییە';

  @override
  String homeGreetingWithName(String name) {
    return 'سڵاو، $name';
  }

  @override
  String get homeGreetingNoName => 'بەخێربێیت';

  @override
  String get quickActions => 'کارە خێراکان';

  @override
  String get notifications => 'ئاگادارکردنەوەکان';

  @override
  String get notificationsComingSoonTitle => 'ئاگادارکردنەوەکان بەم زووانە';

  @override
  String get notificationsComingSoonSubtitle =>
      'کار دەکەین بۆ زیادکردنی ناوەندێکی تەواوی ئاگادارکردنەوە بۆ سکانکردن و چالاکییەکان.';

  @override
  String get vehicleProtected => 'بەستراوەتەوە';

  @override
  String get vehicleNotProtectedYet => 'نەبەستراوەتەوە';

  @override
  String get noVehicleYetHomeTitle => 'یەکەم ئۆتۆمبێلەکەت زیاد بکە';

  @override
  String get noVehicleYetHomeSubtitle =>
      'ئۆتۆمبێلێک زیاد بکە بۆ دەستپێکردنی بەکارهێنانی SmartTag.';

  @override
  String get myVehicles => 'ئۆتۆمبێلەکانم';

  @override
  String get addVehicle => 'زیادکردنی ئۆتۆمبێل';

  @override
  String get noVehiclesTitle => 'هێشتا هیچ ئۆتۆمبێلێک نییە';

  @override
  String get noVehiclesSubtitle =>
      'یەکەم ئۆتۆمبێلەکەت زیاد بکە بۆ دەستپێکردنی بەکارهێنانی SmartTag';

  @override
  String get plateNumber => 'ژمارەی تابلۆ';

  @override
  String get nickname => 'ناو (ئارەزوومەندانە)';

  @override
  String get make => 'کۆمپانیای بەرهەمهێنەر';

  @override
  String get model => 'مۆدێل';

  @override
  String get year => 'ساڵی دروستکردن';

  @override
  String get color => 'ڕەنگ';

  @override
  String get vehicleType => 'جۆری ئۆتۆمبێل';

  @override
  String get save => 'پاشەکەوتکردن';

  @override
  String get cancel => 'هەڵوەشاندنەوە';

  @override
  String get linkedTags => 'کۆدە بەستراوەکان';

  @override
  String get noTagsYet => 'هیچ کۆدێک بەم ئۆتۆمبێلەوە نەبەستراوەتەوە';

  @override
  String get linkNewTag => 'بەستنەوەی کۆدێکی نوێ';

  @override
  String get scanTagInstructions =>
      'کامێراکە ڕووبکە لە کۆدی QR لەسەر ستیکەرەکە';

  @override
  String get activating => 'بەستنەوە ئەنجام دەدرێت...';

  @override
  String get activationSuccess => 'کۆدەکە بە سەرکەوتوویی بەسترایەوە';

  @override
  String get tagAlreadyActivated => 'ئەم کۆدە پێشتر چالاککراوە';

  @override
  String get tagRevoked => 'ئەم کۆدە هەڵوەشێندراوەتەوە';

  @override
  String get tagNotFound => 'کۆدەکە دروست نییە';

  @override
  String get profile => 'هەژمارەکەم';

  @override
  String get logout => 'چوونەدەرەوە';

  @override
  String get logoutAllDevices => 'چوونەدەرەوە لە هەموو ئامێرەکان';

  @override
  String get logoutConfirm => 'دڵنیای لە چوونەدەرەوە؟';

  @override
  String get settings => 'ڕێکخستنەکان';

  @override
  String get language => 'زمان';

  @override
  String get sedan => 'سیدان';

  @override
  String get suv => 'چوارچەرخ';

  @override
  String get truck => 'لۆری';

  @override
  String get motorcycle => 'مۆتۆرسکیل';

  @override
  String get van => 'ڤان';

  @override
  String get other => 'ئەوانی تر';

  @override
  String get statusActive => 'چالاک';

  @override
  String get statusAssigned => 'بەستراوەتەوە';

  @override
  String get statusRevoked => 'هەڵوەشێندراوەتەوە';

  @override
  String get genericError => 'هەڵەیەک ڕوویدا. دووبارە هەوڵبدەرەوە.';

  @override
  String get networkError =>
      'نەتوانرا پەیوەندی بە سێرڤەرەوە بکرێت. ئینتەرنێتەکەت بپشکنە.';

  @override
  String get retry => 'دووبارە هەوڵدانەوە';

  @override
  String get loading => 'بارکردن...';

  @override
  String get delete => 'سڕینەوە';

  @override
  String get deleteVehicleConfirm => 'دڵنیای لە سڕینەوەی ئەم ئۆتۆمبێلە؟';

  @override
  String get requiredField => 'ئەم خانەیە پێویستە';

  @override
  String get invalidPhone => 'ژمارەی مۆبایل دروست نییە';

  @override
  String get subscriptionTitle => 'ئابوونە';

  @override
  String get currentPlan => 'پاکێجی ئێستات';

  @override
  String get planDefaultFree => 'پاکێجی بنەڕەتیی بێبەرامبەر';

  @override
  String planEndsOn(String date) {
    return 'تەواو دەبێت لە $date';
  }

  @override
  String planExpiredBanner(String date) {
    return 'ئابوونەکەت لە $date تەواو بوو. گەڕایتەوە بۆ پاکێجی بنەڕەتی.';
  }

  @override
  String get planFeaturesTitle => 'ئەوەی پاکێجەکەت دەیدات';

  @override
  String get featureMessaging => 'وەرگرتنی پەیامی سەردانکەران';

  @override
  String get featurePublicPhone => 'پیشاندانی ژمارەی مۆبایلت بە سەردانکەران';

  @override
  String get featurePublicInstagram =>
      'زیادکردنی هەژمارەکانی پەیوەندی (ئینستاگرام، فەیسبووک، سناپ...)';

  @override
  String get featurePublicExtra =>
      'کۆنتڕۆڵکردنی ئەوەی سەردانکەر لە هەر هەژمارێک دەیبینێت';

  @override
  String get plansTitle => 'پاکێجەکان';

  @override
  String get planYourPlan => 'پاکێجەکەت';

  @override
  String get priceFree => 'بێبەرامبەر';

  @override
  String priceAmountYear(String amount) {
    return '$amount دینار / ساڵ';
  }

  @override
  String priceAmountDays(String amount, int days) {
    return '$amount دینار / $days ڕۆژ';
  }

  @override
  String get requestUpgrade => 'داواکردنی بەرزکردنەوە';

  @override
  String get upgradePending => 'داواکارییەکەت لە ژێر پێداچوونەوەدایە';

  @override
  String upgradeConfirmTitle(String plan) {
    return 'داواکردنی بەرزکردنەوە بۆ $plan؟';
  }

  @override
  String get upgradeConfirmBody =>
      'تیمی Qreach داواکارییەکەت پێداچوونەوە دەکات و پەیوەندیت پێوە دەکات بۆ تەواوکردنی ئابوونە و چالاککردنی پاکێجەکە.';

  @override
  String get upgradeSent => 'داواکاری بەرزکردنەوە نێردرا';

  @override
  String get subStatusActive => 'چالاک';

  @override
  String get voucherEntryTitle => 'کارتی چالاککردنت هەیە؟';

  @override
  String get voucherEntryBody =>
      'ژمارە 16 ڕەقەمییەکە بنووسە یان کۆدی QR سکان بکە بۆ چالاککردنی پاکێجەکە.';

  @override
  String get voucherTitle => 'چالاککردن بە کارت';

  @override
  String get voucherIntro =>
      'کارتی چالاککردنت لە دابەشکەرێک کڕیوە؟ ژمارە 16 ڕەقەمییەکەی بنووسە یان کۆدی QR ی سەر کارتەکە سکان بکە، پاکێجەکەت دەستبەجێ چالاک دەبێت.';

  @override
  String get voucherFieldLabel => 'ژمارەی کارت (16 ڕەقەم)';

  @override
  String get voucherActivate => 'چالاککردن';

  @override
  String get voucherScan => 'سکانکردنی کۆدی QR';

  @override
  String get voucherScanHint => 'کامێراکە ڕووبکە لە کۆدی QR ی سەر کارتەکە';

  @override
  String get voucherNote =>
      'هەر کارتێک تەنها یەکجار بەکاردێت. ئەگەر بەکارهاتبێت یان بەسەرچووبێت یان هەڵوەشێنرابێتەوە، بەرنامەکە پێت دەڵێت.';

  @override
  String voucherSuccessTitle(String plan) {
    return 'پاکێجی $plan چالاک کرا';
  }

  @override
  String voucherSuccessUntil(String date) {
    return 'پاکێجەکەت چالاکە هەتا $date.';
  }

  @override
  String voucherSuccessDays(int days) {
    return 'پاکێجەکە بۆ ماوەی $days ڕۆژ چالاک کرا.';
  }

  @override
  String get voucherErrInvalid =>
      'ئەم ژمارەی کارتە بوونی نییە. بیپشکنە و دووبارە هەوڵبدەرەوە.';

  @override
  String get voucherErrFormat =>
      'ژمارەکە تەواو نییە یان هەڵەیە. دڵنیابە لە 16 ڕەقەم.';

  @override
  String get voucherErrUsed => 'ئەم کارتە پێشتر بەکارهاتووە.';

  @override
  String get voucherErrExpired => 'ماوەی ئەم کارتە بەسەرچووە.';

  @override
  String get voucherErrRevoked =>
      'ئەم کارتە هەڵوەشێنراوەتەوە و ناتوانرێت بەکاربهێنرێت.';

  @override
  String get voucherErrThrottled =>
      'هەوڵی زۆر لە ماوەیەکی کورتدا. خولەکێک چاوەڕێ بکە و دووبارە هەوڵبدەرەوە.';

  @override
  String get voucherErrLocked =>
      'لە ژمارەی هەوڵە ڕێگەپێدراوەکان تێپەڕیت. دوای کاتژمێرێک هەوڵبدەرەوە.';

  @override
  String get onboard1Title => 'کۆدەکەت بە ئۆتۆمبێلەکەتەوە ببەستەرەوە';

  @override
  String get onboard1Body =>
      'ئۆتۆمبێلەکەت زیاد بکە، پاشان کۆدی QR سکان بکە یان مۆبایلەکەت لە چیپی NFC نزیک بکەرەوە بۆ بەستنەوەی. کۆدەکە لەسەر ئۆتۆمبێلەکە دەلکێنرێت و پاراستن دەستبەجێ دەست پێدەکات.';

  @override
  String get onboard2Title => 'ڕاپۆرت دەگات، نەک بێزارکردن';

  @override
  String get onboard2Body =>
      'کاتێک کەسێک کۆدەکە سکان دەکات هۆکاری ڕاپۆرتەکە هەڵدەبژێرێت و دەینێرێت، ئاگادارکردنەوەیەکت بۆ دێت و لە «گفتوگۆکان» وەڵامەکە دەبینیت بەبێ ئەوەی ژمارەکەت بزانێت.';

  @override
  String get onboard3Title => 'تۆ کۆنتڕۆڵی ئەوە دەکەیت کە دەردەکەوێت';

  @override
  String get onboard3Body =>
      'لە «ئەوەی سەردانکەر دەیبینێت» هەڵبژێرە سەردانکەر چی ببینێت: ناو یان مۆبایل یان پەیوەندی تەنگانە. لە «ئابوونە» پاکێجەکەت نوێ دەکەیتەوە و ڕێگای پەیوەندی زیاتر زیاد دەکەیت.';

  @override
  String get onboardSkip => 'تێپەڕاندن';

  @override
  String get onboardNext => 'دواتر';

  @override
  String get onboardStart => 'دەستپێبکە';

  @override
  String get subStatusGrace => 'ماوەی زیادە';

  @override
  String planGraceBanner(String date) {
    return 'ئابوونەکەت تەواو بوو. هەتا $date دەتوانیت نوێی بکەیتەوە بەبێ ئەوەی هیچ لەدەست بدەیت، دوای ئەوە دەگەڕێیتەوە بۆ پاکێجی بنەڕەتی و زانیارییەکانت دەپارێزرێن.';
  }

  @override
  String renewalCanceledNote(String date) {
    return 'نوێکردنەوە ڕاگیرا. پاکێجەکەت چالاک دەمێنێتەوە هەتا $date.';
  }

  @override
  String get cancelAnnualPlan => 'هەڵوەشاندنەوەی ئابوونەی ساڵانە';

  @override
  String get cancelAnnualTitle => 'ئابوونەی ساڵانە هەڵبوەشێنرێتەوە؟';

  @override
  String cancelAnnualBody(String date) {
    return 'ئابوونەکەت نوێ ناکرێتەوە. پاکێجەکەت کار دەکات هەتا $date و هیچ لە ڕۆژە ماوەکان لەدەست نادەیت.';
  }

  @override
  String get cancelAnnualConfirm => 'هەڵوەشاندنەوەی نوێکردنەوە';

  @override
  String get keepSubscription => 'هێشتنەوەی ئابوونە';

  @override
  String get resumeRenewal => 'دووبارە چالاککردنی نوێکردنەوە';

  @override
  String get renewalCanceledDone =>
      'نوێکردنەوە ڕاگیرا. پاکێجەکەت کار دەکات هەتا کۆتایی ماوەکە.';

  @override
  String get renewalResumedDone => 'نوێکردنەوە دووبارە چالاککرایەوە.';

  @override
  String get renewNow => 'ئێستا نوێی بکەرەوە';

  @override
  String get subStatusExpired => 'بەسەرچوو';

  @override
  String get subStatusCanceled => 'هەڵوەشێندراوەتەوە';

  @override
  String get subStatusPastDue => 'درەنگکەوتوو لە پارەدان';

  @override
  String get subStatusTrialing => 'تاقیکاری';

  @override
  String get publicContactTitle => 'زانیاری پەیوەندی گشتی';

  @override
  String get publicContactSubtitle =>
      'بۆ هەر کەسێک دەردەکەوێت کە کۆدی ئۆتۆمبێلەکەت سکان بکات. ئەوە هەڵبژێرە کە دەتەوێت هاوبەشی بکەیت.';

  @override
  String get publicContactLockedTitle => 'لە پاکێجی Plus بەردەستە';

  @override
  String get publicContactLockedBody =>
      'لە پاکێجی بنەڕەتی سەردانکەران تەنها دەتوانن پەیامت بۆ بنێرن. ژمارەی مۆبایل و هەژمارەکانت پێیان پیشان نادرێت.';

  @override
  String get viewPlans => 'بینینی پاکێجەکان';

  @override
  String get phoneShownLabel => 'ژمارەی مۆبایلی پیشاندراو بە سەردانکەران';

  @override
  String get instagramLabel => 'هەژماری ئینستاگرام';

  @override
  String get instagramHint => 'تەنها ناوی بەکارهێنەر';

  @override
  String get extraLabel => 'زانیاری زیادە';

  @override
  String get extraHint => 'نموونە: بەردەستم دوای کاتژمێر 5ی ئێوارە';

  @override
  String get saved => 'پاشەکەوتکرا';

  @override
  String get invalidInstagram => 'ناوی بەکارهێنەر دروست نییە';

  @override
  String get settingsAccount => 'هەژمار';

  @override
  String get settingsSecurity => 'ئاسایش';

  @override
  String get settingsPreferences => 'هەڵبژاردەکان';

  @override
  String get languageName => 'کوردی';

  @override
  String get loadErrorTitle => 'نەتوانرا زانیارییەکان باربکرێن';

  @override
  String get homeUpgradeTitle => 'هەژمارەکانی پەیوەندی زیاد بکە لەگەڵ Plus';

  @override
  String get homeUpgradeBody =>
      'پاکێجی Plus ڕێگەت دەدات ئینستاگرام و فەیسبووک و سناپ و هتد زیاد بکەیت و بیانخەیتە بەرچاوی سەردانکەر.';

  @override
  String tagsLinkedCount(int count) {
    return '$count کۆد بەستراوەتەوە';
  }

  @override
  String get activationSuccessSubtitle =>
      'ئێستا هەر کەسێک دەتوانێت کۆدەکە سکان بکات و پەیوەندیت پێوە بکات.';

  @override
  String get done => 'تەواو';

  @override
  String get protectionStatus => 'دۆخی پاراستن';

  @override
  String get vehicleDetails => 'وردەکاری ئۆتۆمبێل';

  @override
  String get tagTypeQr => 'کۆدی QR';

  @override
  String get tagTypeNfc => 'کۆدی NFC';

  @override
  String get vehicleFormIdentity => 'ناسنامەی ئۆتۆمبێل';

  @override
  String get vehicleFormMore => 'وردەکاری زیاتر';

  @override
  String get vehicleFormNicknameHint =>
      'ناوێک کە ئۆتۆمبێلەکەت لە ئەوانی تر جیا دەکاتەوە';

  @override
  String get notificationsEmptyTitle => 'هێشتا هیچ ئاگادارکردنەوەیەک نییە';

  @override
  String get notificationsEmptySubtitle =>
      'لێرە ئاگادارکردنەوە دەردەکەون کاتێک کەسێک مامەڵە لەگەڵ کۆدی ئۆتۆمبێلەکەت دەکات.';

  @override
  String get markAllRead => 'هەموو وەک خوێندراوە دیاری بکە';

  @override
  String get groupToday => 'ئەمڕۆ';

  @override
  String get groupYesterday => 'دوێنێ';

  @override
  String get groupEarlier => 'پێشتر';

  @override
  String get recentActivity => 'دوایین چالاکی';

  @override
  String get viewAll => 'بینینی هەمووی';

  @override
  String get unreadLabel => 'نەخوێندراوە';

  @override
  String get emergencyLabel => 'تەنگانە';

  @override
  String get noRecentActivity =>
      'هێشتا هیچ چالاکییەک نییە. کاتێک کەسێک کۆدی ئۆتۆمبێلەکەت سکان بکات لێرە دەیبینیت.';

  @override
  String get tabHome => 'سەرەکی';

  @override
  String get tabVehicles => 'ئۆتۆمبێلەکانم';

  @override
  String get tabContacts => 'پەیوەندی';

  @override
  String get tabProfile => 'هەژمارەکەم';

  @override
  String get contactsTitle => 'داواکارییەکانی پەیوەندی';

  @override
  String get contactsEmptyTitle => 'هێشتا هیچ پەیامێک نییە';

  @override
  String get contactsEmptySubtitle =>
      'کاتێک کەسێک دوای سکانکردنی کۆدی ئۆتۆمبێلەکەت پەیامت بۆ بنێرێت لێرە دەردەکەوێت.';

  @override
  String get contactStatusNew => 'نوێ';

  @override
  String get contactStatusRead => 'خوێندراوە';

  @override
  String get contactStatusResponded => 'وەڵام دراوەتەوە';

  @override
  String get contactStatusClosed => 'داخراوە';

  @override
  String get markResponded => 'دیاری بکە وەڵام دراوەتەوە';

  @override
  String get callVisitor => 'پەیوەندی بە سەردانکەر';

  @override
  String get visitorAnonymous => 'سەردانکەر';

  @override
  String contactAboutVehicle(String vehicle) {
    return 'دەربارەی $vehicle';
  }

  @override
  String get vehicleCodeTitle => 'کۆدی ئۆتۆمبێل';

  @override
  String get removeCode => 'سڕینەوەی کۆد';

  @override
  String get removeCodeConfirmTitle => 'کۆدی ئۆتۆمبێل بسڕدرێتەوە؟';

  @override
  String get removeCodeConfirmBody =>
      'کۆدی ئێستا بۆ هەمیشە کار ناکات و ناتوانرێت دووبارە ببەسترێتەوە. دوای ئەوە دەتوانیت کۆدێکی نوێ ببەستیتەوە.';

  @override
  String get codeRemoved => 'کۆدەکە سڕایەوە';

  @override
  String get oneCodeNote =>
      'هەر ئۆتۆمبێلێک یەک کۆدی هەیە. بۆ گۆڕینی، ئەوەی ئێستا بسڕەوە پاشان نوێیەکە ببەستەرەوە.';

  @override
  String get noCodeYetTitle => 'هیچ کۆدێک نەبەستراوەتەوە';

  @override
  String get noCodeYetSubtitle =>
      'کۆدی ستیکەرەکە سکان بکە تا ئۆتۆمبێلەکە بتوانرێت پەیوەندی پێوە بکرێت.';

  @override
  String get profileMyInfo => 'زانیارییەکانم';

  @override
  String get profileName => 'ناو';

  @override
  String get profileNameHint => 'ناوەکەت وەک دەتەوێت دەربکەوێت';

  @override
  String get editName => 'دەستکاریکردنی ناو';

  @override
  String get notSet => 'دیاری نەکراوە';

  @override
  String get visibilityTitle => 'ئەوەی سەردانکەر دەیبینێت';

  @override
  String get visibilitySubtitle =>
      'بە شێوەی بنەڕەتی سەردانکەر هیچ نابینێت جگە لە ناردنی پەیام بۆ تۆ. زانیارییەکانت زیاد بکە و ئەوە هەڵبژێرە کە دەتەوێت پیشانی بدەیت.';

  @override
  String get showToVisitors => 'پیشاندان بە سەردانکەران';

  @override
  String get proBadge => 'Plus';

  @override
  String get visibilityNoneShown =>
      'ئێستا هیچ شتێک بۆ سەردانکەر پیشان نادرێت — تەنها دەتوانێت پەیامت بۆ بنێرێت.';

  @override
  String visibilitySharing(String items) {
    return 'بۆ سەردانکەر پیشان دەدرێت: $items';
  }

  @override
  String get visibilityLockedHint =>
      'زیادکردنی هەژمارەکانی پەیوەندیی تر لە پاکێجی Plus بەردەستە.';

  @override
  String get scanManualTitle => 'نووسینی کۆد بە دەست';

  @override
  String get scanManualHint => 'بەستەری کۆدەکە یان خودی کۆدەکە دابنێ';

  @override
  String get scanManualButton => 'بەستنەوە';

  @override
  String get scanCameraError => 'نەتوانرا کامێرا کار پێبکرێت';

  @override
  String get scanCameraPermission =>
      'ڕێگە بە بەرنامەکە بدە کامێرا بەکاربهێنێت لە ڕێکخستنەکانی مۆبایل پاشان دووبارە هەوڵبدەرەوە، یان کۆدەکە بە دەست بنووسە.';

  @override
  String get scanTorch => 'فلاش';

  @override
  String get homeStatVehicles => 'ئۆتۆمبێل';

  @override
  String get homeStatNewMessages => 'پەیامی نوێ';

  @override
  String get homeLinkCode => 'بەستنەوەی کۆد';

  @override
  String get homeNoCodeCta => 'کۆدی ئۆتۆمبێل ببەستەرەوە';

  @override
  String get sendMessage => 'ناردن';

  @override
  String get replyHint => 'وەڵامەکەت بنووسە...';

  @override
  String threadExpires(String date) {
    return 'ئەم گفتوگۆیە خۆکارانە دەسڕدرێتەوە لە $date';
  }

  @override
  String get threadEmpty => 'هێشتا هیچ پەیامێک نییە.';

  @override
  String get scanModeQr => 'QR';

  @override
  String get scanModeNfc => 'NFC';

  @override
  String get nfcHoldPhone =>
      'پشتی مۆبایلەکەت لە چیپی NFC لەسەر ئۆتۆمبێلەکە نزیک بکەرەوە';

  @override
  String get nfcUnavailable => 'ئەم ئامێرە پشتگیری NFC ناکات';

  @override
  String get nfcDisabled =>
      'NFC لە ڕێکخستنەکانی مۆبایل چالاک بکە پاشان بگەڕێرەوە ئێرە';

  @override
  String get nfcNoLink => 'بەستەری Qreach لەم چیپەدا نەدۆزرایەوە';

  @override
  String get showQr => 'پیشاندانی کۆدی QR';

  @override
  String get codeLinkLabel => 'بەستەری شاراوە لە کۆدەکەدا';

  @override
  String get changePhoto => 'گۆڕینی وێنە';

  @override
  String get takePhoto => 'گرتنی وێنە';

  @override
  String get chooseFromGallery => 'هەڵبژاردن لە گاڵەری';

  @override
  String get removePhoto => 'لابردنی وێنە';

  @override
  String get photoUpdated => 'وێنەکە نوێکرایەوە';

  @override
  String get chooseLanguage => 'زمان هەڵبژێرە';

  @override
  String get languageArabic => 'العربية';

  @override
  String get languageEnglish => 'English';

  @override
  String get languageKurdish => 'کوردی';

  @override
  String get plateTypeTitle => 'جۆری تابلۆ';

  @override
  String get plateLetter => 'پیت';

  @override
  String get plateGovernorate => 'پارێزگا';

  @override
  String get plateTypePrivate => 'تایبەت';

  @override
  String get plateTypeGovernment => 'حکومی';

  @override
  String get plateTypeTruck => 'بار';

  @override
  String get plateTypeTaxi => 'تاکسی';

  @override
  String get plateTypeAgricultural => 'کشتوکاڵی';

  @override
  String get homeLinked => 'بەستراوەتەوە';

  @override
  String get homeNotLinked => 'نەبەستراوەتەوە';

  @override
  String get homeOwnerLabel => 'خاوەن';

  @override
  String get homeConversations => 'دوایین گفتوگۆکان';

  @override
  String get homeConversationsEmpty => 'هێشتا هیچ گفتوگۆیەک نییە.';

  @override
  String get homeActivities => 'چالاکییەکان';

  @override
  String get actLatest => 'دوایین چالاکییەکان';

  @override
  String get actCommunication => 'دوایین پەیوەندی';

  @override
  String get actAccount => 'هەژمارەکەم';

  @override
  String get actReports => 'ڕاپۆرتەکان';

  @override
  String get actNone => 'هێشتا هیچ نییە';

  @override
  String get youPrefix => 'تۆ: ';

  @override
  String get visShowName => 'ناوم پیشانی سەردانکەر بدە';

  @override
  String get visNameHint =>
      'بە شێوەی بنەڕەتی شاراوەیە. سەردانکەر ناوەکەت نابینێت مەگەر ئەمە چالاک بکەیت.';

  @override
  String get visShowAvatar => 'وێنەی کەسیم پیشانی سەردانکەر بدە';

  @override
  String get visAvatarHint =>
      'بە شێوەی بنەڕەتی شاراوەیە. سەرەتا وێنەیەک لە پڕۆفایلەکەتەوە زیاد بکە.';

  @override
  String get visAvatarLabel => 'وێنەی کەسیم';

  @override
  String get visShowCover => 'وێنەی بەرگ پیشانی سەردانکەر بدە';

  @override
  String get visCoverHint =>
      'بە شێوەی بنەڕەتی شاراوەیە. سەرەتا وێنەی بەرگێک لە پڕۆفایلەکەتەوە زیاد بکە.';

  @override
  String get visCoverLabel => 'وێنەی بەرگ';

  @override
  String get emergencySection => 'پەیوەندی تەنگانە';

  @override
  String get emergencyHint =>
      'وەک باوک یان دایک — تەنها کاتێک بۆ سەردانکەر دەردەکەوێت کە پیشاندانەکە چالاک بکەیت.';

  @override
  String get emergencyName => 'ناو';

  @override
  String get emergencyRelation => 'پەیوەندی (باوک، دایک...)';

  @override
  String get emergencyPhone => 'ژمارەی مۆبایل';

  @override
  String get methodsSection => 'ڕێگاکانی پەیوەندیی زیادە';

  @override
  String get addMethod => 'زیادکردنی ڕێگای پەیوەندی';

  @override
  String get methodValue => 'هەژمار یان ژمارە';

  @override
  String get methodLabel => 'ناوی پیشاندراو';

  @override
  String get methodsLockedTitle => 'لە پاکێجی Plus بەردەستە';

  @override
  String get methodsLockedBody =>
      'پاکێجی Plus ڕێگەت دەدات ئینستاگرام و فەیسبووک و سناپچات و تیکتۆک و واتساپ زیاد بکەیت و کۆنتڕۆڵی ئەوە بکەیت کە سەردانکەر دەیبینێت.';

  @override
  String get upgradeToPlus => 'بەرزکردنەوە بۆ Plus';

  @override
  String get removeMethod => 'سڕینەوە';

  @override
  String get methodsEmpty => 'هێشتا هیچ ڕێگایەکی پەیوەندیت زیاد نەکردووە.';

  @override
  String get pickup => 'پیکاپ';

  @override
  String get bus => 'پاس';

  @override
  String get serverSetup => 'ڕێکخستنی سێرڤەر';

  @override
  String get serverAddress => 'ناونیشانی سێرڤەر (IP:پۆرت)';

  @override
  String get serverSetupHelp =>
      'ناونیشانی ئەو سێرڤەرە بنووسە کە بەرنامەکە پێوەی پەیوەندی دەکات، وەک 192.168.1.10:8000 یان ناوی دۆمەین. تەنها لەسەر ئەم مۆبایلە پاشەکەوت دەکرێت.';

  @override
  String get serverTest => 'تاقیکردنەوەی پەیوەندی';

  @override
  String get serverOk => 'پەیوەندی بە سێرڤەرەوە سەرکەوتوو بوو';

  @override
  String get serverFail =>
      'نەتوانرا بگەیتە سێرڤەر. ناونیشان و پۆرتەکە بپشکنە و دڵنیابە مۆبایلەکە لەسەر هەمان تۆڕە.';

  @override
  String get serverInvalid => 'ناونیشانەکە دروست نییە';

  @override
  String get serverHttpsOnly =>
      'وەشانی کۆتایی تەنها ناونیشانی https قبووڵ دەکات';

  @override
  String get serverSaved => 'ناونیشانی سێرڤەر پاشەکەوتکرا';

  @override
  String get serverReset => 'گەڕاندنەوە بۆ بنەڕەت';

  @override
  String get serverResetDone => 'ناونیشانەکە گەڕایەوە بۆ بنەڕەت';

  @override
  String get appearance => 'ڕووکار';

  @override
  String get themeSystem => 'بەپێی مۆبایل';

  @override
  String get themeLight => 'ڕووناک';

  @override
  String get themeDark => 'تاریک';

  @override
  String get sessionReplacedNotice =>
      'چوویتە دەرەوە چونکە هەژمارەکەت لەسەر ئامێرێکی تر کرایەوە.';

  @override
  String get disableVehicle => 'ڕاگرتنی ئۆتۆمبێل';

  @override
  String get enableVehicle => 'دووبارە چالاککردنی ئۆتۆمبێل';

  @override
  String get disableVehicleTitle => 'ئەم ئۆتۆمبێلە ڕابگیرێت؟';

  @override
  String get disableVehicleBody =>
      'لە کاتی ڕاگرتندا، سکانکردنی کۆدی QR یان چیپی NFC لاپەڕەی \"بەردەست نییە\" پیشان دەدات: سەردانکەران ناتوانن پەیوەندیت پێوە بکەن و هیچ ئاگادارکردنەوەیەکت بۆ نایەت. کۆد و زانیاری ئۆتۆمبێل و گفتوگۆ پێشووەکانت وەک خۆیان دەمێننەوە و دەتوانیت لە هەر کاتێکدا دووبارە چالاکی بکەیتەوە.';

  @override
  String get disableConfirm => 'ڕاگرتن';

  @override
  String get enableVehicleTitle => 'ئەم ئۆتۆمبێلە دووبارە چالاک بکرێتەوە؟';

  @override
  String get enableVehicleBody =>
      'ئەو سەردانکەرانەی کۆدەکەی سکان دەکەن دووبارە دەتوانن پەیوەندیت پێوە بکەن.';

  @override
  String get enableConfirm => 'دووبارە چالاککردنەوە';

  @override
  String get vehicleDisabledBanner =>
      'ئەم ئۆتۆمبێلە ڕاگیراوە. کۆدەکەی لاپەڕەی \"بەردەست نییە\" بۆ سەردانکەران پیشان دەدات.';

  @override
  String get vehicleDisabledDone => 'ئۆتۆمبێلەکە ڕاگیرا';

  @override
  String get vehicleEnabledDone => 'ئۆتۆمبێلەکە دووبارە چالاککرایەوە';

  @override
  String get deleteVehicleTitle => 'ئەم ئۆتۆمبێلە بسڕدرێتەوە؟';

  @override
  String get deleteVehicleBody =>
      'ئۆتۆمبێلەکە لە هەژمارەکەت لادەبرێت و کۆدەکەی کار ناکات. ناتوانرێت ئەم کردارە بگەڕێندرێتەوە. ئەگەر دەتەوێت تەنها بۆ ماوەیەک ڕایبگریت، \"ڕاگرتنی ئۆتۆمبێل\" بەکاربهێنە لەبری سڕینەوە.';

  @override
  String get attachPhoto => 'هاوپێچکردنی وێنە';

  @override
  String get removeAttachment => 'لابردنی وێنە';

  @override
  String get imageTooLarge => 'وێنەکە زۆر گەورەیە (زۆرترین 8 مێگابایت).';

  @override
  String get imageTypeUnsupported =>
      'جۆری فایلەکە پشتگیری ناکرێت. وێنەی JPG یان PNG یان WebP بەکاربهێنە.';

  @override
  String get cameraPermissionDenied =>
      'ڕێگە بدە بە کامێرا لە ڕێکخستنەکانی مۆبایل بۆ گرتنی وێنە.';

  @override
  String get photosPermissionDenied =>
      'ڕێگە بدە بە وێنەکان لە ڕێکخستنەکانی مۆبایل بۆ هەڵبژاردنی وێنە.';

  @override
  String get imageSendFailed =>
      'نەتوانرا وێنەکە بنێردرێت. دووبارە هەوڵبدەرەوە.';

  @override
  String get payCheckoutTitle => 'ئابوونە';

  @override
  String get payReviewTitle => 'پوختەی داواکاری';

  @override
  String get payPriceLabel => 'نرخ';

  @override
  String get payBenefitsTitle => 'چی بەدەست دەهێنیت';

  @override
  String get payMethodTitle => 'شێوازی پارەدان';

  @override
  String get payNoMethods =>
      'ئێستا هیچ شێوازێکی پارەدان بەردەست نییە. دواتر هەوڵبدەرەوە.';

  @override
  String get payTestBadge => 'تاقیکاری';

  @override
  String get payNow => 'ئێستا پارە بدە';

  @override
  String get payNote =>
      'دەچیتە لاپەڕەیەکی پارەدانی سەلامەت. پاکێجەکەت تەنها دوای پشتڕاستکردنەوەی پارەدان چالاک دەکرێت.';

  @override
  String get payStatusTitle => 'پارەدان';

  @override
  String get payChecking => 'پارەدان پشتڕاست دەکرێتەوە…';

  @override
  String get payCheckingHint =>
      'ئەمە بەزۆری چەند چرکەیەک دەخایەنێت. پاکێجەکەت هەر کە پارەدان پشتڕاست بکرێتەوە چالاک دەبێت.';

  @override
  String get paySuccessTitle => 'پارەدان ئەنجام درا';

  @override
  String get payFailedTitle => 'پارەدان تەواو نەبوو';

  @override
  String get payFailedHint =>
      'هیچ بڕێک لێت نەبڕدراوە. دەتوانیت دووبارە هەوڵبدەیتەوە.';

  @override
  String get payFailedExpired =>
      'ماوەی پارەدان تەواو بوو پێش ئەوەی ئەنجام بدرێت.';

  @override
  String get payTryAgain => 'دووبارە هەوڵبدەرەوە';

  @override
  String get payDone => 'تەواو';

  @override
  String get payReceiptTitle => 'وەسڵ';

  @override
  String get payReceiptNumber => 'ژمارەی وەسڵ';

  @override
  String get payReceiptPlan => 'پاکێج';

  @override
  String get payReceiptAmount => 'بڕ';

  @override
  String get payReceiptMethod => 'شێوازی پارەدان';

  @override
  String get payReceiptDate => 'بەروار';

  @override
  String get payReceiptValidUntil => 'کاردەکات هەتا';

  @override
  String get payOpenFailed => 'نەتوانرا لاپەڕەی پارەدان بکرێتەوە.';

  @override
  String get payTestNotice =>
      'ئەمە پارەدانێکی تاقیکاری بوو. هیچ بڕێکی ڕاستەقینە نەبڕدرا.';

  @override
  String get paySubscribeButton => 'ئابوونەی ئەم پاکێجە بکە';

  @override
  String get tagTypeCode => 'کۆدی QR + NFC';

  @override
  String get pickerSearch => 'گەڕان';

  @override
  String get pickerOther => 'ئەوانی تر';

  @override
  String get pickerChoose => 'هەڵبژێرە';

  @override
  String get pickerNoResults => 'هیچ ئەنجامێک نییە';

  @override
  String get modelHint => 'خۆت بینووسە، وەک: G class';

  @override
  String get editVehicle => 'دەستکاریکردنی ئۆتۆمبێل';

  @override
  String get editVehicleAction => 'دەستکاری';

  @override
  String get vinLabel => 'ژمارەی شاسی (VIN) — ئارەزوومەندانە';

  @override
  String get vinInvalid =>
      'ژمارەی شاسی دروست نییە (ژمارە و پیتی لاتینی بەبێ I و O و Q).';

  @override
  String get copyLink => 'کۆپی کردنی بەستەر';

  @override
  String get linkCopied => 'بەستەرەکە کۆپی کرا';
}
