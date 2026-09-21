<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي (مجلد admin)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة (isAdmin، verifyCsrf، csrfToken، e، setFlash، redirect)

if (!isAdmin()) {
// فحص: إذا لم يكن المستخدم مديراً
    setFlash('danger', 'لا تملك صلاحية الوصول.');
    // رسالة خطأ مؤقتة
    redirect('../login.php');
    // توجيه لصفحة الدخول
}

$errors = [];
// مصفوفة فارغة لتخزين رسائل الخطأ (غير مستخدمة فعلياً هنا لأننا نستخدم setFlash)

// ========== تغيير الدور ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
// فحص شرطين:
// 1. الطلب بطريقة POST
// 2. يوجد حقل user_id وحقل role (لتمييز عملية تغيير الدور)

    if (!verifyCsrf($_POST['csrf'] ?? '')) {
    // فحص رمز CSRF
        setFlash('danger', 'طلب غير صالح.');
        redirect('users.php');
    }

    $user_id = (int)$_POST['user_id'];
    // معرف المستخدم المراد تغيير دوره (تحويل لرقم صحيح)
    $role    = $_POST['role'];
    // الدور الجديد

    if (!in_array($role, ['admin', 'customer'])) {
    // فحص: إذا كان الدور غير موجود في القائمة المسموحة
    // (حماية من إرسال قيم عشوائية عبر تعديل HTML)
        setFlash('danger', 'دور غير صالح.');
        redirect('users.php');
    }

    // منع المدير من تغيير دوره لنفسه
    if ($user_id === (int)$_SESSION['user_id']) {
    // فحص: إذا كان المستخدم يحاول تغيير دوره الشخصي
        setFlash('warning', 'لا يمكنك تغيير دورك بنفسك.');
        // منع العملية (حماية من تخفيض صلاحياته بالخطأ)
        redirect('users.php');
    }

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    // تحضير استعلام UPDATE لتحديث دور المستخدم
    // Prepared Statement يحمي من SQL Injection

    $stmt->execute([$role, $user_id]);
    // تنفيذ التحديث مع تمرير الدور والمعرف

    setFlash('success', 'تم تحديث دور المستخدم.');
    redirect('users.php');
}

// ========== الحذف ==========
if (isset($_GET['delete'])) {
// فحص: إذا وُجد معامل delete في الرابط (مثال: users.php?delete=5)

    if (!verifyCsrf($_GET['csrf'] ?? '')) {
    // فحص رمز CSRF المُرسل في الرابط
        setFlash('danger', 'طلب غير صالح.');
        redirect('users.php');
    }

    $id = (int)$_GET['delete'];
    // معرف المستخدم المراد حذفه

    // منع حذف الذات
    if ($id === (int)$_SESSION['user_id']) {
    // فحص: إذا كان المستخدم يحاول حذف حسابه الشخصي
        setFlash('warning', 'لا يمكنك حذف حسابك.');
        // منع العملية (حماية من فقدان الوصول)
        redirect('users.php');
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    // حذف المستخدم
    // نستخدم سلسلة prepare()->execute() في سطر واحد

    setFlash('success', 'تم حذف المستخدم.');
    redirect('users.php');
}

// ========== القراءة ==========
$users = $pdo->query(
    "SELECT u.*,
        (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) AS bookings_count
     FROM users u
     ORDER BY u.created_at DESC"
)->fetchAll();
// استعلام لجلب جميع المستخدمين مع عدد حجوزات كل مستخدم
// u.* = جميع أعمدة users
// (SELECT COUNT(*) ...) AS bookings_count = استعلام فرعي (Subquery) يحسب عدد الحجوزات
// ORDER BY u.created_at DESC = ترتيب حسب تاريخ الإنشاء (الأحدث أولاً)
// fetchAll() = جلب جميع الصفوف

$pageTitle = 'إدارة المستخدمين';
// عنوان الصفحة

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى لأننا في admin)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر
?>

<!-- إغلاق وسم PHP والانتقال لـ HTML -->

<div class="d-flex justify-content-between align-items-center mb-4">
<!-- حاوية مرنة: توزيع بين البداية والنهاية، محاذاة وسطية، هامش سفلي -->
  <h2><i class="bi bi-people text-primary"></i> إدارة المستخدمين</h2>
  <!-- العنوان الرئيسي مع أيقونة أشخاص -->
  <a href="dashboard.php" class="btn btn-secondary">
  <!-- زر رمادي للرجوع للوحة التحكم -->
    <i class="bi bi-arrow-right"></i> رجوع للوحة
  </a>
</div>

<div class="card">
<!-- بطاقة تحتوي الجدول -->
  <div class="card-header bg-white">
  <!-- رأس البطاقة بخلفية بيضاء -->
    <h5 class="mb-0">
      <i class="bi bi-list"></i> قائمة المستخدمين (<?= count($users) ?>)
      <!-- العنوان مع عدد المستخدمين -->
    </h5>
  </div>
  <div class="card-body p-0">
  <!-- جسم البطاقة بدون حشوة (الجدول يملأ كامل العرض) -->
    <div class="table-responsive">
    <!-- حاوية للتمرير الأفقي على الشاشات الصغيرة -->
      <table class="table table-hover align-middle mb-0">
      <!-- جدول Bootstrap: تظليل عند المرور + محاذاة وسطية -->
        <thead class="table-dark">
        <!-- رأس الجدول بخلفية داكنة -->
          <tr>
            <th>#</th>
            <th>الاسم الكامل</th>
            <th>البريد الإلكتروني</th>
            <th>الهاتف</th>
            <th>الدور</th>
            <th>الحجوزات</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <!-- حلقة تكرار على كل مستخدم -->
            <tr>
              <td><?= $u['id'] ?></td>
              <!-- المعرف -->
              <td>
                <strong><?= e($u['full_name']) ?></strong>
                <!-- الاسم الكامل (بخط عريض، مع تأمينه) -->
                <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                <!-- فحص: إذا كان هذا المستخدم هو نفسه المستخدم الحالي -->
                  <span class="badge bg-info">أنت</span>
                  <!-- شارة زرقاء "أنت" لتمييز حسابه -->
                <?php endif; ?>
              </td>
              <td><?= e($u['email']) ?></td>
              <!-- البريد الإلكتروني -->
              <td><?= e($u['phone']) ?></td>
              <!-- الهاتف -->
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                <!-- فحص: إذا كان الدور مديراً -->
                  <span class="badge bg-danger">مدير</span>
                  <!-- شارة حمراء "مدير" -->
                <?php else: ?>
                <!-- وإلا (عميل) -->
                  <span class="badge bg-secondary">عميل</span>
                  <!-- شارة رمادية "عميل" -->
                <?php endif; ?>
              </td>
              <td>
                <span class="badge bg-primary"><?= $u['bookings_count'] ?></span>
                <!-- شارة زرقاء تعرض عدد حجوزات المستخدم (من الاستعلام الفرعي) -->
              </td>
              <td>
                <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                <!-- فحص: إذا كان هذا هو المستخدم الحالي (نفسه) -->
                  <span class="text-muted small">لا يمكن التعديل</span>
                  <!-- نص رمادي بدلاً من الأزرار (منع تعديل الذات) -->
                <?php else: ?>
                <!-- وإلا (مستخدم آخر) → اعرض الأزرار -->

                  <!-- ===== زر تغيير الدور ===== -->
                  <form method="post" class="d-inline">
                  <!-- نموذج POST في نفس السطر -->
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <!-- رمز CSRF -->
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <!-- معرف المستخدم -->

                    <?php if ($u['role'] === 'admin'): ?>
                    <!-- فحص: إذا كان المستخدم مديراً حالياً -->
                      <input type="hidden" name="role" value="customer">
                      <!-- الدور الجديد: عميل (لتخفيض الصلاحية) -->
                      <button class="btn btn-sm btn-outline-secondary"
                              title="إرجاع لعميل">
                      <!-- زر رمادي شفاف -->
                        <i class="bi bi-person"></i> عميل
                        <!-- أيقونة شخص + نص "عميل" -->
                      </button>
                    <?php else: ?>
                    <!-- وإلا (عميل حالياً) -->
                      <input type="hidden" name="role" value="admin">
                      <!-- الدور الجديد: مدير (للترقية) -->
                      <button class="btn btn-sm btn-outline-danger"
                              title="ترقية لمدير">
                      <!-- زر أحمر شفاف -->
                        <i class="bi bi-shield-check"></i> مدير
                        <!-- أيقونة درع + نص "مدير" -->
                      </button>
                    <?php endif; ?>
                  </form>

                  <!-- ===== زر الحذف ===== -->
                  <a href="?delete=<?= $u['id'] ?>&csrf=<?= csrfToken() ?>"
                     class="btn btn-sm btn-danger confirm-delete" title="حذف">
                  <!-- رابط حذف:
                       - يمرر delete=ID و csrf في الرابط
                       - class="confirm-delete" لتأكيد JavaScript قبل الحذف -->
                    <i class="bi bi-trash"></i>
                    <!-- أيقونة سلة مهملات -->
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <!-- إغلاق حلقة التكرار -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<!-- استدعاء الفوتر -->