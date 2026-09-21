<?php
// فتح وسم PHP لبدء كتابة الكود

require_once 'config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات (db.php) مرة واحدة فقط، وإذا لم يوجد يتوقف التنفيذ

require_once 'includes/functions.php';
// استدعاء ملف الدوال المساعدة (functions.php) الذي يحتوي على دوال مثل isLoggedIn() و redirect() و verifyCsrf() و csrfToken() و e() و setFlash()

// إذا كان المستخدم مسجلاً → انتقل للوحة التحكم
if (isLoggedIn()) {
// فحص: إذا كان المستخدم مسجلاً دخوله بالفعل
    redirect(isAdmin() ? 'admin/dashboard.php' : 'customer/dashboard.php');
    // توجيهه مباشرة إلى لوحة المدير إذا كان admin، أو لوحة العميل إذا كان عميلاً عادياً
    // (لا داعي لعرض صفحة تسجيل الدخول لمستخدم مسجل بالفعل)
}

$error = '';
// متغير فارغ لتخزين رسائل الخطأ التي ستُعرض للمستخدم لاحقاً

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// فحص: إذا تم إرسال النموذج بطريقة POST (أي أن المستخدم ضغط زر "دخول")
    // 1) التحقق من CSRF
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
    // فحص رمز CSRF المُرسل مع النموذج، و ?? '' تعني: إذا لم يوجد الرمز استخدم نصاً فارغاً
    // verifyCsrf() تتحقق من صحة الرمز لمنع هجمات تزوير الطلبات
        $error = 'طلب غير صالح. أعد المحاولة.';
        // إذا كان الرمز غير صالح، نخزن رسالة خطأ
    } else {
    // إذا كان رمز CSRF صالحاً، نكمل عملية تسجيل الدخول
        $email = trim($_POST['email'] ?? '');
        // جلب البريد الإلكتروني المُرسل من النموذج وإزالة المسافات الزائدة من البداية والنهاية
        $pass  = $_POST['password'] ?? '';
        // جلب كلمة المرور المُرسلة من النموذج (بدون trim لأن المسافات قد تكون جزءاً من كلمة المرور)

        // 2) التحقق من المدخلات
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // فحص: إذا كان البريد الإلكتروني غير صالح بالصيغة الصحيحة (example@domain.com)
            $error = 'البريد الإلكتروني غير صالح.';
            // تخزين رسالة خطأ
        } elseif (empty($pass)) {
        // وإلا: إذا كانت كلمة المرور فارغة
            $error = 'كلمة المرور مطلوبة.';
            // تخزين رسالة خطأ
        } else {
        // وإلا (المدخلات صحيحة) → نبدأ عملية التحقق من قاعدة البيانات
            // 3) جلب المستخدم من قاعدة البيانات (Prepared Statement)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            // تحضير استعلام SQL آمن لجلب جميع بيانات المستخدم حسب البريد الإلكتروني
            // استخدام ? (Prepared Statement) يحمي من هجمات SQL Injection
            $stmt->execute([$email]);
            // تنفيذ الاستعلام مع تمرير البريد الإلكتروني كقيمة
            $user = $stmt->fetch();
            // جلب صف المستخدم كنتيجة (أو false إذا لم يوجد)

            // 4) التحقق من كلمة المرور
            if ($user && password_verify($pass, $user['password'])) {
            // فحص: إذا وُجد المستخدم AND كانت كلمة المرور المُدخلة مطابقة للمشفرة في قاعدة البيانات
            // password_verify() تقارن كلمة المرور النصية مع الكلمة المشفرة بـ password_hash()
                // 5) تجديد الجلسة (حماية من Session Fixation)
                session_regenerate_id(true);
                // إنشاء معرف جلسة جديد وحذف القديم، لحماية المستخدم من هجوم تثبيت الجلسة

                $_SESSION['user_id'] = $user['id'];
                // تخزين معرف المستخدم في الجلسة
                $_SESSION['name']    = $user['full_name'];
                // تخزين اسم المستخدم الكامل في الجلسة
                $_SESSION['role']    = $user['role'];
                // تخزين دور المستخدم (admin أو customer) في الجلسة

                setFlash('success', 'مرحباً بك، ' . $user['full_name'] . '!');
                // تخزين رسالة نجاح مؤقتة (تظهر مرة واحدة في الصفحة التالية)

                redirect($user['role'] === 'admin'
                    ? 'admin/dashboard.php'
                    : 'customer/dashboard.php');
                // توجيه المستخدم إلى لوحة التحكم المناسبة حسب دوره
            } else {
            // وإلا (المستخدم غير موجود أو كلمة المرور خاطئة)
                $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
                // رسالة خطأ عامة (لا نكشف أيهما الخطأ لأسباب أمنية)
            }
        }
    }
}

$pageTitle = 'تسجيل الدخول';
// عنوان الصفحة الذي سيظهر في تبويب المتصفح

$baseUrl = '';
// متغير يُستخدم لبناء الروابط (فارغ لأننا في الصفحة الرئيسية)

include 'includes/header.php';
// استدعاء ملف الهيدر (رأس الصفحة: القوائم، CSS، إلخ)
?>

<!-- إغلاق وسم PHP والانتقال لكتابة HTML -->

<div class="row justify-content-center">
<!-- صف شبكي مع توسيط المحتوى أفقياً -->
  <div class="col-md-5">
  <!-- عمود بعرض 5/12 من الشاشة المتوسطة (نموذج متوسط الحجم في الوسط) -->
    <div class="card shadow-sm">
    <!-- بطاقة مع ظل خفيف -->
      <div class="card-body p-4">
      <!-- جسم البطاقة مع حشوة داخلية كبيرة -->
        <h3 class="mb-4 text-center">
        <!-- عنوان بحجم 3 مع هامش سفلي ووسط النص -->
          <i class="bi bi-box-arrow-in-right text-primary"></i> تسجيل الدخول
          <!-- أيقونة سهم دخول بلون أزرق + نص العنوان -->
        </h3>

        <?php if ($error): ?>
        <!-- فحص: إذا كان هناك رسالة خطأ مخزنة -->
          <div class="alert alert-danger">
          <!-- صندوق تنبيه أحمر -->
            <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
            <!-- أيقونة تحذير + عرض نص الخطأ مع تأمينه بـ e() ضد XSS -->
          </div>
        <?php endif; ?>
        <!-- إغلاق الشرط -->

        <form method="post" novalidate>
        <!-- نموذج يُرسل بطريقة POST، و novalidate تعني: لا تستخدم التحقق الافتراضي من المتصفح (لأننا نتحقق بأنفسنا) -->
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <!-- حقل مخفي يحتوي على رمز CSRF لتأمين النموذج ضد هجمات التزوير -->

          <div class="mb-3">
          <!-- حاوية بهامش سفلي -->
            <label class="form-label">البريد الإلكتروني</label>
            <!-- تسمية حقل البريد الإلكتروني -->
            <input type="email" name="email" class="form-control"
                   value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
            <!-- حقل إدخال البريد الإلكتروني:
                 - type=email: يتحقق من الصيغة
                 - value: يعيد ملء القيمة المُدخلة سابقاً إذا فشل التسجيل (مع تأمينها بـ e())
                 - required: حقل مطلوب
                 - autofocus: التركيز التلقائي على الحقل عند فتح الصفحة -->
          </div>

          <div class="mb-3">
          <!-- حاوية بهامش سفلي -->
            <label class="form-label">كلمة المرور</label>
            <!-- تسمية حقل كلمة المرور -->
            <input type="password" name="password" class="form-control" required>
            <!-- حقل إدخال كلمة المرور:
                 - type=password: إخفاء النص أثناء الكتابة
                 - required: حقل مطلوب
                 (لا نعيد ملء كلمة المرور لأسباب أمنية) -->
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2">
          <!-- زر إرسال النموذج:
               - btn-primary: بلون أزرق
               - w-100: عرض كامل
               - py-2: حشوة رأسية متوسطة -->
            <i class="bi bi-box-arrow-in-right"></i> دخول
            <!-- أيقونة سهم دخول + نص الزر -->
          </button>
        </form>

        <p class="text-center mt-3 mb-0">
        <!-- فقرة بوسط النص مع هامش علوي وبدون هامش سفلي -->
          ليس لديك حساب؟
          <!-- نص السؤال -->
          <a href="register.php" class="fw-bold">أنشئ حساباً جديداً</a>
          <!-- رابط لصفحة التسجيل بخط عريض -->
        </p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<!-- استدعاء ملف الفوتر (تذييل الصفحة: الحقوق، السكربتات) -->