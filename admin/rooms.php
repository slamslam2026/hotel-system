<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي للملف (مجلد admin)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة (isAdmin، verifyCsrf، csrfToken، e، setFlash، redirect)

if (!isAdmin()) {
// فحص: إذا لم يكن المستخدم الحالي مديراً
    setFlash('danger', 'لا تملك صلاحية الوصول.');
    // تخزين رسالة خطأ مؤقتة (تظهر مرة واحدة في الصفحة التالية)
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول
}

$errors = [];
// مصفوفة فارغة لتخزين رسائل الخطأ التي ستُعرض للمستخدم

// ========== UPDATE (تعديل غرفة) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
// فحص شرطين:
// 1. الطلب بطريقة POST (تم إرسال نموذج)
// 2. قيمة action = 'update' (لتحديد نوع العملية ومنع تنفيذ كود آخر)

    if (!verifyCsrf($_POST['csrf'] ?? '')) $errors[] = 'طلب غير صالح.';
    // فحص رمز CSRF، وإذا فشل → إضافة خطأ

    $id          = (int)($_POST['id'] ?? 0);
    // معرف الغرفة (تحويل لرقم صحيح)
    $room_number = trim($_POST['room_number'] ?? '');
    // رقم الغرفة (مع إزالة المسافات)
    $floor       = (int)($_POST['floor'] ?? 1);
    // الطابق (الافتراضي 1)
    $type_id     = (int)($_POST['room_type_id'] ?? 0);
    // معرف نوع الغرفة
    $status      = $_POST['status'] ?? 'available';
    // الحالة (الافتراضية 'available')

    if ($id <= 0)            $errors[] = 'معرف غير صالح.';
    // فحص: إذا كان المعرف غير صالح
    if (empty($room_number)) $errors[] = 'رقم الغرفة مطلوب.';
    // فحص: إذا كان رقم الغرفة فارغاً
    if ($type_id <= 0)       $errors[] = 'يجب اختيار نوع الغرفة.';
    // فحص: إذا لم يُختَر نوع

    if (!in_array($status, ['available', 'occupied', 'maintenance'])) {
    // فحص: إذا كانت الحالة غير موجودة في القائمة المسموحة
        $errors[] = 'حالة الغرفة غير صالحة.';
        // إضافة خطأ (حماية من إرسال قيم عشوائية)
    }

    if (empty($errors)) {
    // إذا لم توجد أخطاء → نفّذ التحديث
        $stmt = $pdo->prepare(
            "UPDATE rooms
             SET room_number = ?, floor = ?, room_type_id = ?, status = ?
             WHERE id = ?"
        );
        // تحضير استعلام UPDATE لتحديث بيانات الغرفة
        // Prepared Statement يحمي من SQL Injection

        $stmt->execute([$room_number, $floor, $type_id, $status, $id]);
        // تنفيذ التحديث مع تمرير القيم بالترتيب

        setFlash('success', 'تم تحديث الغرفة بنجاح.');
        // رسالة نجاح
        redirect('rooms.php');
        // إعادة التوجيه لصفحة الغرف
    }
}

// ========== DELETE (حذف غرفة) ==========
if (isset($_GET['delete'])) {
// فحص: إذا وُجد معامل delete في الرابط (مثال: rooms.php?delete=5)

    if (!verifyCsrf($_GET['csrf'] ?? '')) {
    // فحص رمز CSRF المُرسل في الرابط
        setFlash('danger', 'طلب غير صالح.');
        redirect('rooms.php');
    }

    $id = (int)$_GET['delete'];
    // جلب معرف الغرفة المراد حذفها وتحويله لرقم صحيح

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ?");
    // تحضير استعلام لعد الحجوزات المرتبطة بهذه الغرفة
    $stmt->execute([$id]);
    // تنفيذ الاستعلام
    if ($stmt->fetchColumn() > 0) {
    // فحص: إذا كانت هناك حجوزات مرتبطة
        setFlash('danger', 'لا يمكن الحذف: توجد حجوزات مرتبطة بهذه الغرفة.');
        // رفض الحذف (حماية تكامل البيانات)
        redirect('rooms.php');
    }

    $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
    // حذف الغرفة (بعد التأكد من عدم وجود حجوزات)
    // نستخدم سلسلة prepare()->execute() في سطر واحد

    setFlash('success', 'تم حذف الغرفة.');
    redirect('rooms.php');
}

// ========== READ (للتعديل) ==========
$edit = null;
// متغير فارغ لتخزين بيانات الغرفة المراد تعديلها
if (isset($_GET['edit'])) {
// فحص: إذا وُجد معامل edit في الرابط (مثال: rooms.php?edit=3)
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    // تحضير استعلام لجلب بيانات الغرفة
    $stmt->execute([(int)$_GET['edit']]);
    // تنفيذ الاستعلام مع تمرير المعرف
    $edit = $stmt->fetch();
    // جلب بيانات الغرفة (أو false إذا لم توجد)
}
// ملاحظة: إذا وُجد $edit، سيظهر نموذج التعديل في HTML

// ========== READ (قائمة الغرف) ==========
$rooms = $pdo->query(
    "SELECT r.*, rt.name AS type_name, rt.price_per_night, rt.capacity
     FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id
     ORDER BY r.room_number"
)->fetchAll();
// استعلام مباشر لجلب جميع الغرف مع بيانات نوعها
// r.* = جميع أعمدة rooms
// rt.name AS type_name = اسم النوع
// rt.price_per_night = السعر الليلي
// rt.capacity = السعة
// JOIN room_types = ربط الغرف بأنواعها
// ORDER BY r.room_number = ترتيب تصاعدي حسب رقم الغرفة

$roomTypes = $pdo->query("SELECT * FROM room_types ORDER BY name")->fetchAll();
// جلب جميع أنواع الغرف (لعرضها في قائمة الاختيار بنموذج التعديل)

$pageTitle = 'إدارة الغرف';
// عنوان الصفحة

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى لأننا داخل admin)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر

$statusLabels = [
    'available'   => 'متاحة',
    'occupied'    => 'مشغولة',
    'maintenance' => 'صيانة',
];
// مصفوفة تربط كل حالة بنص عربي (للعرض)

$statusColors = [
    'available'   => 'success',
    'occupied'    => 'danger',
    'maintenance' => 'warning',
];
// مصفوفة تربط كل حالة بلون Bootstrap (للشارات)
?>

<!-- إغلاق وسم PHP والانتقال لـ HTML -->

<div class="d-flex justify-content-between align-items-center mb-4">
<!-- حاوية مرنة: توزيع بين البداية والنهاية، محاذاة وسطية، هامش سفلي -->
  <h2><i class="bi bi-door-open text-primary"></i> إدارة الغرف</h2>
  <!-- العنوان الرئيسي مع أيقونة باب -->
  <a href="dashboard.php" class="btn btn-secondary">
  <!-- زر رمادي للرجوع للوحة التحكم -->
    <i class="bi bi-arrow-right"></i> رجوع للوحة
  </a>
</div>

<?php if ($errors): ?>
<!-- فحص: إذا كانت هناك أخطاء -->
  <div class="alert alert-danger">
  <!-- صندوق تنبيه أحمر -->
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      <!-- حلقة تكرار على كل خطأ (مكتوبة في سطر واحد) مع تأمينه بـ e() -->
    </ul>
  </div>
<?php endif; ?>

<!-- ========== نموذج التعديل (يظهر فقط عند تعديل غرفة) ========== -->
<?php if ($edit): ?>
<!-- فحص: إذا كان هناك غرفة قيد التعديل -->
<div class="card mb-4">
<!-- بطاقة بهامش سفلي -->
  <div class="card-header bg-white">
  <!-- رأس البطاقة بخلفية بيضاء -->
    <h5 class="mb-0">
      <i class="bi bi-pencil-square"></i> تعديل غرفة
      <!-- عنوان مع أيقونة قلم -->
    </h5>
  </div>
  <div class="card-body">
  <!-- جسم البطاقة -->
    <form method="post">
    <!-- نموذج يُرسل بطريقة POST -->
      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
      <!-- رمز CSRF -->
      <input type="hidden" name="action" value="update">
      <!-- نوع العملية: تحديث (يُستخدم في PHP للتمييز) -->
      <input type="hidden" name="id" value="<?= $edit['id'] ?>">
      <!-- معرف الغرفة المراد تعديلها -->

      <div class="row g-3">
      <!-- صف شبكي بمسافات -->
        <div class="col-md-2">
        <!-- عمود بسدس العرض -->
          <label class="form-label">رقم الغرفة</label>
          <input type="text" name="room_number" class="form-control"
                 value="<?= e($edit['room_number']) ?>" required maxlength="10">
          <!-- حقل رقم الغرفة (معبأ بالقيمة الحالية، بحد أقصى 10 أحرف) -->
        </div>

        <div class="col-md-2">
          <label class="form-label">الطابق</label>
          <input type="number" name="floor" class="form-control"
                 value="<?= e($edit['floor']) ?>" min="0" required>
          <!-- حقل الطابق (الحد الأدنى 0) -->
        </div>

        <div class="col-md-3">
        <!-- عمود بربع العرض -->
          <label class="form-label">نوع الغرفة</label>
          <select name="room_type_id" class="form-select" required>
          <!-- قائمة اختيار النوع -->
            <option value="">— اختر النوع —</option>
            <!-- خيار افتراضي فارغ -->
            <?php foreach ($roomTypes as $t): ?>
            <!-- حلقة تكرار على كل نوع -->
              <option value="<?= $t['id'] ?>"
                <?= ($edit['room_type_id'] == $t['id']) ? 'selected' : '' ?>>
                <!-- فحص: إذا كان هذا النوع مطابقاً للنوع الحالي → حدده -->
                <?= e($t['name']) ?> (<?= number_format($t['price_per_night'], 0) ?> $)
                <!-- نص الخيار: اسم النوع + السعر (بدون خانات عشرية) -->
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">الحالة</label>
          <select name="status" class="form-select" required>
          <!-- قائمة اختيار الحالة -->
            <option value="available"   <?= ($edit['status'] == 'available')   ? 'selected' : '' ?>>متاحة</option>
            <option value="occupied"    <?= ($edit['status'] == 'occupied')    ? 'selected' : '' ?>>مشغولة</option>
            <option value="maintenance" <?= ($edit['status'] == 'maintenance') ? 'selected' : '' ?>>صيانة</option>
            <!-- كل خيار يُحدد تلقائياً إذا كانت الحالة الحالية مطابقة -->
          </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
        <!-- عمود مع محاذاة سفلية (لمحاذاة الزر مع الحقول) -->
          <button class="btn btn-warning w-100">
          <!-- زر أصفر بعرض كامل -->
            <i class="bi bi-check"></i> تحديث
          </button>
        </div>
      </div>

      <a href="rooms.php" class="btn btn-sm btn-secondary mt-3">
      <!-- زر صغير رمادي لإلغاء التعديل (يعود بدون معامل edit) -->
        <i class="bi bi-x"></i> إلغاء التعديل
      </a>
    </form>
  </div>
</div>
<?php endif; ?>
<!-- إغلاق شرط نموذج التعديل -->

<!-- ========== جدول قائمة الغرف ========== -->
<div class="card">
<!-- بطاقة تحتوي الجدول -->
  <div class="card-header bg-white">
    <h5 class="mb-0">
      <i class="bi bi-list"></i> قائمة الغرف (<?= count($rooms) ?>)
      <!-- العنوان مع عدد الغرف -->
    </h5>
  </div>
  <div class="card-body p-0">
  <!-- جسم البطاقة بدون حشوة (p-0) لأن الجدول يملأ كامل البطاقة -->
    <div class="table-responsive">
    <!-- حاوية للتمرير الأفقي على الشاشات الصغيرة -->
      <table class="table table-hover align-middle mb-0">
      <!-- جدول Bootstrap: تظليل عند المرور + محاذاة وسطية -->
        <thead class="table-dark">
        <!-- رأس الجدول بخلفية داكنة -->
          <tr>
            <th>#</th>
            <th>رقم الغرفة</th>
            <th>النوع</th>
            <th>الطابق</th>
            <th>السعر/الليلة</th>
            <th>السعة</th>
            <th>الحالة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rooms as $r): ?>
          <!-- حلقة تكرار على كل غرفة -->
            <tr>
              <td><?= $r['id'] ?></td>
              <!-- المعرف -->
              <td><strong><?= e($r['room_number']) ?></strong></td>
              <!-- رقم الغرفة (بخط عريض، مع تأمينه) -->
              <td><?= e($r['type_name']) ?></td>
              <!-- اسم النوع -->
              <td><?= $r['floor'] ?></td>
              <!-- الطابق -->
              <td><?= number_format($r['price_per_night'], 2) ?> $</td>
              <!-- السعر الليلي (بخانتين عشريتين) -->
              <td><?= $r['capacity'] ?> أشخاص</td>
              <!-- السعة -->
              <td>
                <span class="badge bg-<?= $statusColors[$r['status']] ?? 'secondary' ?>">
                <!-- شارة بلون الحالة (أو رمادي إذا لم توجد) -->
                  <?= $statusLabels[$r['status']] ?? $r['status'] ?>
                  <!-- نص الحالة (أو الحالة الأصلية إذا لم توجد في المصفوفة) -->
                </span>
              </td>
              <td>
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-warning">
                <!-- زر أصفر للتعديل (يمرر edit=ID في الرابط) -->
                  <i class="bi bi-pencil"></i> تعديل
                </a>
                <a href="?delete=<?= $r['id'] ?>&csrf=<?= csrfToken() ?>"
                   class="btn btn-sm btn-danger confirm-delete">
                <!-- زر أحمر للحذف:
                     - يمرر delete=ID و csrf في الرابط
                     - class="confirm-delete" لتأكيد JavaScript قبل الحذف -->
                  <i class="bi bi-trash"></i> حذف
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<!-- استدعاء الفوتر -->