<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي (مجلد admin)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة

if (!isAdmin()) {
// فحص: إذا لم يكن المستخدم مديراً
    setFlash('danger', 'لا تملك صلاحية الوصول.');
    // رسالة خطأ مؤقتة
    redirect('../login.php');
    // توجيه لصفحة الدخول
}

$errors = [];
// مصفوفة فارغة لتخزين رسائل الخطأ

// ========== CREATE (إضافة نوع جديد) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
// فحص شرطين:
// 1. الطلب بطريقة POST
// 2. قيمة action = 'create' (تمييز عملية الإضافة)

    if (!verifyCsrf($_POST['csrf'] ?? '')) $errors[] = 'طلب غير صالح.';
    // فحص رمز CSRF

    $name        = trim($_POST['name'] ?? '');
    // اسم النوع (مع إزالة المسافات)
    $description = trim($_POST['description'] ?? '');
    // الوصف (اختياري)
    $price       = (float)($_POST['price_per_night'] ?? 0);
    // السعر الليلي (تحويل لعدد عشري float)
    $capacity    = (int)($_POST['capacity'] ?? 2);
    // السعة (تحويل لرقم صحيح، الافتراضي 2)

    if (mb_strlen($name) < 2)   $errors[] = 'اسم النوع مطلوب (حرفين على الأقل).';
    // فحص: إذا كان الاسم أقل من حرفين (mb_strlen للدعم الصحيح للعربية)
    if ($price <= 0)            $errors[] = 'السعر يجب أن يكون أكبر من صفر.';
    // فحص: إذا كان السعر صفر أو سالب
    if ($capacity < 1)          $errors[] = 'السعة يجب أن تكون 1 على الأقل.';
    // فحص: إذا كانت السعة أقل من 1

    if (empty($errors)) {
    // إذا لم توجد أخطاء → نفّذ الإدخال
        $stmt = $pdo->prepare(
            "INSERT INTO room_types (name, description, price_per_night, capacity)
             VALUES (?, ?, ?, ?)"
        );
        // تحضير استعلام INSERT لإضافة نوع جديد
        // Prepared Statement يحمي من SQL Injection

        $stmt->execute([$name, $description, $price, $capacity]);
        // تنفيذ الإدخال مع تمرير القيم

        setFlash('success', 'تمت إضافة نوع الغرفة بنجاح.');
        // رسالة نجاح
        redirect('room-types.php');
        // إعادة التوجيه
    }
}

// ========== UPDATE (تعديل نوع) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
// فحص: POST + action='update'

    if (!verifyCsrf($_POST['csrf'] ?? '')) $errors[] = 'طلب غير صالح.';
    // فحص CSRF

    $id          = (int)($_POST['id'] ?? 0);
    // معرف النوع
    $name        = trim($_POST['name'] ?? '');
    // الاسم
    $description = trim($_POST['description'] ?? '');
    // الوصف
    $price       = (float)($_POST['price_per_night'] ?? 0);
    // السعر
    $capacity    = (int)($_POST['capacity'] ?? 2);
    // السعة

    if ($id <= 0)               $errors[] = 'معرف غير صالح.';
    // فحص: إذا كان المعرف غير صالح
    if (mb_strlen($name) < 2)   $errors[] = 'اسم النوع مطلوب.';
    // فحص: إذا كان الاسم أقل من حرفين
    if ($price <= 0)            $errors[] = 'السعر يجب أن يكون أكبر من صفر.';
    // فحص: إذا كان السعر غير صالح
    if ($capacity < 1)          $errors[] = 'السعة يجب أن تكون 1 على الأقل.';
    // فحص: إذا كانت السعة غير صالحة

    if (empty($errors)) {
    // إذا لم توجد أخطاء → نفّذ التحديث
        $stmt = $pdo->prepare(
            "UPDATE room_types
             SET name = ?, description = ?, price_per_night = ?, capacity = ?
             WHERE id = ?"
        );
        // تحضير استعلام UPDATE

        $stmt->execute([$name, $description, $price, $capacity, $id]);
        // تنفيذ التحديث مع تمرير القيم

        setFlash('success', 'تم تحديث نوع الغرفة بنجاح.');
        redirect('room-types.php');
    }
}

// ========== DELETE (حذف نوع) ==========
if (isset($_GET['delete'])) {
// فحص: إذا وُجد معامل delete في الرابط

    if (!verifyCsrf($_GET['csrf'] ?? '')) {
    // فحص رمز CSRF
        setFlash('danger', 'طلب غير صالح.');
        redirect('room-types.php');
    }

    $id = (int)$_GET['delete'];
    // معرف النوع المراد حذفه

    // التحقق: هل يوجد غرف مرتبطة بهذا النوع؟
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ?");
    // تحضير استعلام لعد الغرف المرتبطة
    $stmt->execute([$id]);
    // تنفيذ الاستعلام
    $count = $stmt->fetchColumn();
    // جلب العدد

    if ($count > 0) {
    // فحص: إذا كانت هناك غرف مرتبطة
        setFlash('danger', "لا يمكن الحذف: يوجد $count غرفة مرتبطة بهذا النوع.");
        // رفض الحذف مع عرض العدد (حماية تكامل البيانات)
        redirect('room-types.php');
    }

    $pdo->prepare("DELETE FROM room_types WHERE id = ?")->execute([$id]);
    // حذف النوع (بعد التأكد من عدم وجود غرف مرتبطة)
    // نستخدم سلسلة prepare()->execute() في سطر واحد

    setFlash('success', 'تم حذف نوع الغرفة.');
    redirect('room-types.php');
}

// ========== READ (للتعديل) ==========
$edit = null;
// متغير فارغ لتخزين بيانات النوع المراد تعديله
if (isset($_GET['edit'])) {
// فحص: إذا وُجد معامل edit في الرابط
    $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
    // جلب بيانات النوع (أو false إذا لم يوجد)
}

// ========== READ (قائمة الأنواع) ==========
$types = $pdo->query(
    "SELECT rt.*,
        (SELECT COUNT(*) FROM rooms WHERE room_type_id = rt.id) AS rooms_count
     FROM room_types rt
     ORDER BY rt.price_per_night"
)->fetchAll();
// استعلام لجلب جميع أنواع الغرف مع عدد الغرف المرتبطة بكل نوع
// rt.* = جميع أعمدة room_types
// (SELECT COUNT(*) ...) AS rooms_count = استعلام فرعي (Subquery) يحسب عدد الغرف لكل نوع
// ORDER BY rt.price_per_night = ترتيب حسب السعر

$pageTitle = 'إدارة أنواع الغرف';
// عنوان الصفحة

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر
?>

<!-- إغلاق وسم PHP والانتقال لـ HTML -->

<div class="d-flex justify-content-between align-items-center mb-4">
<!-- حاوية مرنة: توزيع بين البداية والنهاية -->
  <h2><i class="bi bi-tags text-primary"></i> إدارة أنواع الغرف</h2>
  <!-- العنوان مع أيقونة وسوم -->
  <a href="dashboard.php" class="btn btn-secondary">
  <!-- زر رمادي للرجوع للوحة -->
    <i class="bi bi-arrow-right"></i> رجوع للوحة
  </a>
</div>

<?php if ($errors): ?>
<!-- فحص: إذا كانت هناك أخطاء -->
  <div class="alert alert-danger">
  <!-- صندوق تنبيه أحمر -->
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      <!-- حلقة تكرار على كل خطأ (في سطر واحد) -->
    </ul>
  </div>
<?php endif; ?>

<!-- ========== نموذج إضافة / تعديل ========== -->
<div class="card mb-4">
<!-- بطاقة بهامش سفلي -->
  <div class="card-header bg-white">
    <h5 class="mb-0">
      <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle' ?>"></i>
      <!-- الأيقونة تتغير حسب الوضع:
           - $edit موجود → أيقونة قلم (تعديل)
           - $edit غير موجود → أيقونة + (إضافة) -->
      <?= $edit ? 'تعديل نوع الغرفة' : 'إضافة نوع جديد' ?>
      <!-- العنوان يتغير حسب الوضع -->
    </h5>
  </div>
  <div class="card-body">
    <form method="post">
    <!-- نموذج يُرسل بطريقة POST -->
      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
      <!-- رمز CSRF -->
      <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
      <!-- حقل مخفي action:
           - $edit موجود → 'update'
           - غير موجود → 'create' -->
      <?php if ($edit): ?>
      <!-- فحص: إذا كنا في وضع التعديل -->
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <!-- معرف النوع المراد تعديله -->
      <?php endif; ?>

      <div class="row g-3">
      <!-- صف شبكي بمسافات -->
        <div class="col-md-3">
        <!-- عمود بربع العرض -->
          <label class="form-label">اسم النوع</label>
          <input type="text" name="name" class="form-control"
                 value="<?= e($edit['name'] ?? '') ?>" required maxlength="100">
          <!-- حقل الاسم:
               - إذا كان $edit موجوداً → املأ بالاسم الحالي
               - وإلا → اتركه فارغاً -->
        </div>

        <div class="col-md-4">
        <!-- عمود بثلث العرض -->
          <label class="form-label">الوصف</label>
          <input type="text" name="description" class="form-control"
                 value="<?= e($edit['description'] ?? '') ?>" maxlength="255">
          <!-- حقل الوصف (اختياري، بحد أقصى 255 حرفاً) -->
        </div>

        <div class="col-md-2">
          <label class="form-label">السعر / الليلة ($)</label>
          <input type="number" name="price_per_night" class="form-control"
                 value="<?= e($edit['price_per_night'] ?? '') ?>"
                 min="1" step="0.01" required>
          <!-- حقل السعر:
               - min=1 → الحد الأدنى 1
               - step=0.01 → يسمح بالكسور العشرية -->
        </div>

        <div class="col-md-1">
        <!-- عمود بجزء صغير -->
          <label class="form-label">السعة</label>
          <input type="number" name="capacity" class="form-control"
                 value="<?= e($edit['capacity'] ?? 2) ?>" min="1" max="10" required>
          <!-- حقل السعة (1-10 أشخاص، الافتراضي 2) -->
        </div>

        <div class="col-md-2 d-flex align-items-end">
        <!-- عمود مع محاذاة سفلية -->
          <button class="btn btn-<?= $edit ? 'warning' : 'success' ?> w-100">
          <!-- الزر يتغير حسب الوضع:
               - $edit → أصفر (تحديث)
               - غير موجود → أخضر (إضافة) -->
            <i class="bi bi-<?= $edit ? 'check' : 'plus' ?>"></i>
            <!-- الأيقونة تتغير: ✓ أو + -->
            <?= $edit ? 'تحديث' : 'إضافة' ?>
            <!-- النص يتغير: "تحديث" أو "إضافة" -->
          </button>
        </div>
      </div>

      <?php if ($edit): ?>
      <!-- فحص: إذا كنا في وضع التعديل -->
        <a href="room-types.php" class="btn btn-sm btn-secondary mt-3">
        <!-- زر صغير لإلغاء التعديل (يعود بدون معامل edit) -->
          <i class="bi bi-x"></i> إلغاء التعديل
        </a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- ========== جدول الأنواع ========== -->
<div class="card">
<!-- بطاقة تحتوي الجدول -->
  <div class="card-header bg-white">
    <h5 class="mb-0">
      <i class="bi bi-list"></i> الأنواع (<?= count($types) ?>)
      <!-- العنوان مع عدد الأنواع -->
    </h5>
  </div>
  <div class="card-body p-0">
  <!-- جسم البطاقة بدون حشوة (الجدول يملأ كامل العرض) -->
    <div class="table-responsive">
    <!-- حاوية للتمرير الأفقي -->
      <table class="table table-hover align-middle mb-0">
      <!-- جدول Bootstrap مع تظليل الصفوف ومحاذاة وسطية -->
        <thead class="table-dark">
        <!-- رأس الجدول بخلفية داكنة -->
          <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>الوصف</th>
            <th>السعر/الليلة</th>
            <th>السعة</th>
            <th>عدد الغرف</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($types as $t): ?>
          <!-- حلقة تكرار على كل نوع -->
            <tr>
              <td><?= $t['id'] ?></td>
              <!-- المعرف -->
              <td><strong><?= e($t['name']) ?></strong></td>
              <!-- الاسم (بخط عريض) -->
              <td><?= e($t['description']) ?></td>
              <!-- الوصف -->
              <td><?= number_format($t['price_per_night'], 2) ?> $</td>
              <!-- السعر الليلي -->
              <td><?= $t['capacity'] ?> أشخاص</td>
              <!-- السعة -->
              <td>
                <span class="badge bg-info"><?= $t['rooms_count'] ?> غرفة</span>
                <!-- شارة زرقاء تعرض عدد الغرف المرتبطة بهذا النوع -->
              </td>
              <td>
                <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-warning">
                <!-- زر أصفر للتعديل -->
                  <i class="bi bi-pencil"></i> تعديل
                </a>
                <?php if ($t['rooms_count'] == 0): ?>
                <!-- فحص: إذا لم توجد غرف مرتبطة بهذا النوع -->
                  <a href="?delete=<?= $t['id'] ?>&csrf=<?= csrfToken() ?>"
                     class="btn btn-sm btn-danger confirm-delete">
                  <!-- زر أحمر للحذف (مع تأكيد JavaScript) -->
                    <i class="bi bi-trash"></i> حذف
                  </a>
                <?php else: ?>
                <!-- وإلا (توجد غرف مرتبطة) → اعرض زر معطّل -->
                  <button class="btn btn-sm btn-secondary" disabled
                          title="لا يمكن الحذف (مرتبط بغرف)">
                  <!-- زر رمادي معطّل مع تلميح توضيحي -->
                    <i class="bi bi-lock"></i>
                    <!-- أيقونة قفل تدل على عدم إمكانية الحذف -->
                  </button>
                <?php endif; ?>
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