<?php
// فتح وسم PHP لبدء كتابة الكود

require_once 'config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات (db.php) مرة واحدة فقط، وإذا لم يوجد يتوقف التنفيذ

require_once 'includes/functions.php';
// استدعاء ملف الدوال المساعدة (functions.php) الذي يحتوي على دوال مثل isLoggedIn() و redirect() و verifyCsrf() و csrfToken() و e() و setFlash()

// إذا كان المستخدم مسجلاً بالفعل → انتقل للوحة التحكم
if (isLoggedIn()) {
// فحص: إذا كان المستخدم مسجلاً دخوله بالفعل
    redirect(isAdmin() ? 'admin/dashboard.php' : 'customer/dashboard.php');
    // توجيهه مباشرة إلى لوحة المدير إذا كان admin، أو لوحة العميل إذا كان عميلاً عادياً
    // (لا داعي لعرض صفحة التسجيل لمستخدم مسجل بالفعل)
}

$errors = [];
// مصفوفة فارغة لتخزين جميع رسائل الخطأ التي ستُعرض للمستخدم

$old = ['full_name' => '', 'email' => '', 'phone' => ''];
// مصفوفة لتخزين القيم القديمة التي أدخلها المستخدم
// الهدف: إعادة ملء النموذج بها إذا حدث خطأ (حتى لا يعيد الكتابة من جديد)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// فحص: إذا تم إرسال النموذج بطريقة POST (أي أن المستخدم ضغط زر "إنشاء الحساب")

    $old['full_name'] = trim($_POST['full_name'] ?? '');
    // جلب الاسم الكامل من النموذج وإزالة المسافات الزائدة من البداية والنهاية
    // ?? '' تعني: إذا لم يوجد الحقل استخدم نصاً فارغاً

    $old['email']     = trim($_POST['email'] ?? '');
    // جلب البريد الإلكتروني وإزالة المسافات الزائدة

    $old['phone']     = trim($_POST['phone'] ?? '');
    // جلب رقم الهاتف وإزالة المسافات الزائدة

    $password         = $_POST['password'] ?? '';
    // جلب كلمة المرور (بدون trim لأن المسافات قد تكون جزءاً من كلمة المرور)

    $password2        = $_POST['password2'] ?? '';
    // جلب تأكيد كلمة المرور

    // 1) التحقق من CSRF
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
    // فحص رمز CSRF المُرسل مع النموذج
    // verifyCsrf() تتحقق من صحة الرمز لمنع هجمات تزوير الطلبات
        $errors[] = 'طلب غير صالح. أعد المحاولة.';
        // إضافة رسالة خطأ إلى المصفوفة
    }

    // 2) التحقق من المدخلات
    if (mb_strlen($old['full_name']) < 3) {
    // فحص طول الاسم الكامل باستخدام mb_strlen (للدعم الصحيح للعربية)
    // يجب أن يكون 3 أحرف على الأقل
        $errors[] = 'الاسم يجب أن يكون 3 أحرف على الأقل.';
        // إضافة رسالة خطأ
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    // فحص صيغة البريد الإلكتروني (example@domain.com)
        $errors[] = 'البريد الإلكتروني غير صالح.';
        // إضافة رسالة خطأ
    }
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $old['phone'])) {
    // فحص صيغة رقم الهاتف باستخدام التعبير النمطي (Regex)
    // يقبل: أرقام (0-9)، علامة +، شرطة -، ومسافات، بطول من 7 إلى 20 حرفاً
        $errors[] = 'رقم الهاتف غير صالح.';
        // إضافة رسالة خطأ
    }
    if (strlen($password) < 6) {
    // فحص طول كلمة المرور: يجب أن تكون 6 أحرف على الأقل
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
        // إضافة رسالة خطأ
    }
    if ($password !== $password2) {
    // فحص تطابق كلمتي المرور
        $errors[] = 'كلمتا المرور غير متطابقتين.';
        // إضافة رسالة خطأ
    }

    // 3) التحقق من عدم تكرار البريد
    if (empty($errors)) {
    // فحص: إذا لم توجد أخطاء حتى الآن (المدخلات صحيحة)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        // تحضير استعلام SQL آمن للبحث عن مستخدم بنفس البريد الإلكتروني
        // Prepared Statement يحمي من هجمات SQL Injection
        $stmt->execute([$old['email']]);
        // تنفيذ الاستعلام مع تمرير البريد الإلكتروني
        if ($stmt->fetch()) {
        // فحص: إذا وُجد مستخدم بنفس البريد
            $errors[] = 'البريد الإلكتروني مسجل مسبقاً.';
            // إضافة رسالة خطأ
        }
    }

    // 4) الحفظ في قاعدة البيانات
    if (empty($errors)) {
    // فحص: إذا لم توجد أي أخطاء (المدخلات كلها صحيحة)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // تشفير كلمة المرور باستخدام password_hash()
        // PASSWORD_DEFAULT تستخدم أقوى خوارزمية متاحة حالياً (bcrypt)
        // لا نخزن كلمة المرور كنص صريح أبداً لأسباب أمنية

        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)"
        );
        // تحضير استعلام SQL لإدخال مستخدم جديد في جدول users
        // استخدام ? يحمي من SQL Injection

        $stmt->execute([$old['full_name'], $old['email'], $old['phone'], $hash]);
        // تنفيذ الاستعلام مع تمرير القيم الأربعة

        setFlash('success', 'تم إنشاء حسابك بنجاح! يمكنك تسجيل الدخول الآن.');
        // تخزين رسالة نجاح مؤقتة (تظهر مرة واحدة في الصفحة التالية)

        redirect('login.php');
        // توجيه المستخدم إلى صفحة تسجيل الدخول بعد نجاح التسجيل
    }
}

$pageTitle = 'إنشاء حساب';
// عنوان الصفحة الذي سيظهر في تبويب المتصفح

$baseUrl = '';
// متغير يُستخدم لبناء الروابط (فارغ لأننا في الصفحة الرئيسية)

include 'includes/header.php';
// استدعاء ملف الهيدر (رأس الصفحة: القوائم، CSS، إلخ)
?>

<!-- إغلاق وسم PHP والانتقال لكتابة HTML -->

<div class="row justify-content-center">
<!-- صف شبكي مع توسيط المحتوى أفقياً -->
  <div class="col-md-6">
  <!-- عمود بعرض 6/12 من الشاشة (نموذج متوسط الحجم في الوسط) -->
    <div class="card shadow-sm">
    <!-- بطاقة مع ظل خفيف -->
      <div class="card-body p-4">
      <!-- جسم البطاقة مع حشوة داخلية كبيرة -->
        <h3 class="mb-4 text-center">
        <!-- عنوان بحجم 3 مع هامش سفلي ووسط النص -->
          <i class="bi bi-person-plus text-primary"></i> إنشاء حساب جديد
          <!-- أيقونة إضافة شخص بلون أزرق + نص العنوان -->
        </h3>

        <?php if ($errors): ?>
        <!-- فحص: إذا كانت هناك أخطاء مخزنة في المصفوفة -->
          <div class="alert alert-danger">
          <!-- صندوق تنبيه أحمر -->
            <ul class="mb-0">
            <!-- قائمة غير مرتبة لعرض كل الأخطاء -->
              <?php foreach ($errors as $err): ?>
              <!-- حلقة تكرار على كل خطأ في المصفوفة -->
                <li><?= e($err) ?></li>
                <!-- عرض الخطأ كعنصر قائمة مع تأمينه بـ e() ضد XSS -->
              <?php endforeach; ?>
              <!-- إغلاق حلقة التكرار -->
            </ul>
          </div>
        <?php endif; ?>
        <!-- إغلاق الشرط -->

        <form method="post" novalidate>
        <!-- نموذج يُرسل بطريقة POST، و novalidate تعني: لا تستخدم التحقق الافتراضي من المتصفح -->
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <!-- حقل مخفي يحتوي على رمز CSRF لتأمين النموذج ضد هجمات التزوير -->

          <div class="mb-3">
          <!-- حاوية بهامش سفلي -->
            <label class="form-label">الاسم الكامل</label>
            <!-- تسمية حقل الاسم -->
            <input type="text" name="full_name" class="form-control"
                   value="<?= e($old['full_name']) ?>"
                   required minlength="3" maxlength="100">
            <!-- حقل إدخال الاسم:
                 - value: يعيد ملء القيمة المُدخلة سابقاً (مع تأمينها بـ e())
                 - required: حقل مطلوب
                 - minlength=3: الحد الأدنى 3 أحرف
                 - maxlength=100: الحد الأقصى 100 حرف -->
          </div>

          <div class="mb-3">
          <!-- حاوية بهامش سفلي -->
            <label class="form-label">البريد الإلكتروني</label>
            <!-- تسمية حقل البريد الإلكتروني -->
            <input type="email" name="email" class="form-control"
                   value="<?= e($old['email']) ?>" required>
            <!-- حقل إدخال البريد الإلكتروني:
                 - type=email: يتحقق من الصيغة
                 - value: يعيد ملء القيمة المُدخلة سابقاً
                 - required: حقل مطلوب -->
          </div>

          <div class="mb-3">
          <!-- حاوية بهامش سفلي -->
            <label class="form-label">رقم الهاتف</label>
            <!-- تسمية حقل رقم الهاتف -->
            <input type="text" name="phone" class="form-control"
                   value="<?= e($old['phone']) ?>"
                   required placeholder="مثال: 0500000000">
            <!-- حقل إدخال رقم الهاتف:
                 - value: يعيد ملء القيمة المُدخلة سابقاً
                 - required: حقل مطلوب
                 - placeholder: نص إرشادي يظهر داخل الحقل -->
          </div>

          <div class="row">
          <!-- صف شبكي لتقسيم حقلي كلمة المرور أفقياً -->
            <div class="col-md-6 mb-3">
            <!-- العمود الأول: نصف العرض -->
              <label class="form-label">كلمة المرور</label>
              <!-- تسمية حقل كلمة المرور -->
              <input type="password" name="password" class="form-control"
                     required minlength="6">
              <!-- حقل إدخال كلمة المرور:
                   - type=password: إخفاء النص أثناء الكتابة
                   - required: حقل مطلوب
                   - minlength=6: الحد الأدنى 6 أحرف -->
            </div>
            <div class="col-md-6 mb-3">
            <!-- العمود الثاني: نصف العرض -->
              <label class="form-label">تأكيد كلمة المرور</label>
              <!-- تسمية حقل تأكيد كلمة المرور -->
              <input type="password" name="password2" class="form-control"
                     required minlength="6">
              <!-- حقل إدخال تأكيد كلمة المرور (يجب أن يطابق الأول) -->
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2">
          <!-- زر إرسال النموذج:
               - btn-primary: بلون أزرق
               - w-100: عرض كامل
               - py-2: حشوة رأسية متوسطة -->
            <i class="bi bi-check-circle"></i> إنشاء الحساب
            <!-- أيقونة علامة صح + نص الزر -->
          </button>
        </form>

        <p class="text-center mt-3 mb-0">
        <!-- فقرة بوسط النص مع هامش علوي وبدون هامش سفلي -->
          لديك حساب بالفعل؟
          <!-- نص السؤال -->
          <a href="login.php" class="fw-bold">سجّل الدخول</a>
          <!-- رابط لصفحة تسجيل الدخول بخط عريض -->
        </p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<!-- استدعاء ملف الفوتر (تذييل الصفحة: الحقوق، السكربتات) -->