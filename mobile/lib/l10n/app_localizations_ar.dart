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
  String get profile => 'الملف الشخصي';

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
}
