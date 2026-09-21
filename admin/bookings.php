<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ تعني: المسار الحالي للملف
// '/../' تعني: اصعد مجلداً واحداً للأعلى (لأننا داخل مجلد admin)
// النتيجة النهائية: الوصول إلى config/db.php من داخل مجلد admin

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة من مجلد includes (بعد الصعود مجلداً للأعلى)

if (!isAdmin()) {
// فحص: إذا كان المستخدم الحالي ليس مديراً
    setFlash('danger', 'لا تملك صلاحية الوصول.');
    // تخزين رسالة خطأ مؤقتة
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول (مع الصعود مجلداً للأعلى)
}

// ==========================================
// تغيير الحالة
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
// فحص شرطين:
// 1. الطلب بطريقة POST (تم إرسال نموذج)
// 2. يوجد حقل action وحقل booking_id في الطلب
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
    // فحص صحة رمز CSRF
        setFlash('danger', 'طلب غير صالح.');
        // رسالة خطأ
        redirect('bookings.php');
        // إعادة التوجيه لصفحة الحجوزات
    }

    $booking_id = (int)$_POST['booking_id'];
    // جلب رقم الحجز وتحويله لرقم صحيح للأمان

    $new_status = $_POST['action'];
    // جلب الحالة الجديدة من حقل action

    if (!in_array($new_status, ['confirmed', 'cancelled', 'completed', 'pending'])) {
    // فحص: إذا كانت الحالة الجديدة غير موجودة في القائمة المسموحة
    // (لمنع المستخدم من إرسال حالات عشوائية)
        setFlash('danger', 'حالة غير صالحة.');
        // رسالة خطأ
        redirect('bookings.php');
        // إعادة التوجيه
    }

    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    // تحضير استعلام لتحديث حالة الحجز
    // Prepared Statement يحمي من SQL Injection

    $stmt->execute([$new_status, $booking_id]);
    // تنفيذ الاستعلام مع تمرير الحالة الجديدة ورقم الحجز

    $labels = [      //[متغير يحمل مصفوفة التسميات
        'confirmed' => 'تأكيد الحجز',
        'cancelled' => 'إلغاء الحجز',
        'completed' => 'إكمال الحجز',
        'pending'   => 'إرجاع الحجز للانتظار',
    ];
    // مصفوفة تربط كل حالة بنص عربي وصفي (لعرضه في رسالة النجاح)

    setFlash('success', 'تم ' . ($labels[$new_status] ?? 'التحديث') . ' بنجاح.');
    // تخزين رسالة مؤقت نجاح تحتوي على وصف العملية
    // ?? 'التحديث' تعني: إذا لم توجد الحالة في المصفوفة، استخدم "التحديث"

    redirect('bookings.php');
    // إعادة التوجيه لصفحة الحجوزات
}

// ==========================================
// الحذف
// ==========================================
if (isset($_GET['delete'])) {
// فحص: إذا وُجد معامل delete في الرابط (مثال: bookings.php?delete=5)
    if (!verifyCsrf($_GET['csrf'] ?? '')) {
    // فحص صحة رمز CSRF المُرسل في الرابط
        setFlash('danger', 'طلب غير صالح.');
        redirect('bookings.php');
    }
    $id = (int)$_GET['delete'];
    // جلب رقم الحجز المراد حذفه وتحويله لرقم صحيح

    $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$id]);
    // تحضير وتنفيذ استعلام حذف الحجز مباشرة
    // (استخدام السلسلة: prepare()->execute() في سطر واحد)

    setFlash('success', 'تم حذف الحجز.');
    // رسالة نجاح
    redirect('bookings.php');
    // إعادة التوجيه
}

// ==========================================
// الفلترة
// ==========================================
$filter = $_GET['status'] ?? 'all';
// جلب حالة الفلترة من الرابط، وإذا لم توجد → 'all' (عرض الكل)

$where = '';
// متغير فارغ لتخزين شرط WHERE

$params = [];
// مصفوفة فارغة لتخزين معاملات الاستعلام

if (in_array($filter, ['pending', 'confirmed', 'cancelled', 'completed'])) {
// فحص: إذا كانت الفلترة واحدة من الحالات الأربع الصحيحة
    $where = "WHERE b.status = ?";
    // إضافة شرط WHERE لتصفية الحجوزات حسب الحالة
    $params[] = $filter;
    // إضافة قيمة الحالة إلى المعاملات
}
// ملاحظة: إذا كانت الفلترة 'all'، لن يُضاف شرط WHERE (لعرض كل الحجوزات)

// ==========================================
// القراءة
// ==========================================
$sql = "SELECT b.*, 
               COALESCE(b.customer_name, u.full_name) AS full_name, 
               COALESCE(b.customer_phone, u.phone) AS phone,
               r.room_number, rt.name AS type_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        $where
        ORDER BY b.created_at DESC";
// بناء استعلام SQL لجلب الحجوزات مع كل البيانات المرتبطة:

// SELECT b.* → جميع أعمدة جدول bookings
// COALESCE(b.customer_name, u.full_name) AS full_name:
//   → إذا كان اسم العميل مخزناً في الحجز استخدمه، وإلا استخدم اسم المستخدم من جدول users
//   COALESCE ترجع أول قيمة غير NULL
// COALESCE(b.customer_phone, u.phone) AS phone → نفس المنطق لرقم الهاتف
// r.room_number → رقم الغرفة
// rt.name AS type_name → اسم نوع الغرفة

// FROM bookings b → من جدول الحجوزات
// LEFT JOIN users u ON b.user_id = u.id:
//   → ربط بجدول المستخدمين (LEFT لأنه قد يكون الحجز بدون مستخدم مسجل)
// JOIN rooms r ON b.room_id = r.id → ربط بجدول الغرف
// JOIN room_types rt ON r.room_type_id = rt.id → ربط بجدول أنواع الغرف
// $where → شرط الفلترة (فارغ أو WHERE b.status = ?)
// ORDER BY b.created_at DESC → ترتيب حسب تاريخ الإنشاء (الأحدث أولاً)

$stmt = $pdo->prepare($sql);
// تحضير الاستعلام

$stmt->execute($params);
// تنفيذ الاستعلام مع المعاملات

$bookings = $stmt->fetchAll();
// جلب جميع الحجوزات كمصفوفة

// ==========================================
// إحصائيات الحجوزات (لأزرار الفلترة)
// ==========================================
$counts = [
    'all'       => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    // عدد جميع الحجوزات
    'pending'   => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
    // عدد الحجوزات قيد الانتظار
    'confirmed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
    // عدد الحجوزات المؤكدة
    'cancelled' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn(),
    // عدد الحجوزات الملغاة
    'completed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn(),
    // عدد الحجوزات المكتملة
];
// fetchColumn() ترجع قيمة واحدة (العدد)

$pageTitle = 'إدارة الحجوزات';
// عنوان الصفحة

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى لأننا داخل admin)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر من مجلد includes

$statusColors = [
    'pending'   => 'warning',
    'confirmed' => 'success',
    'cancelled' => 'danger',
    'completed' => 'secondary',
];
// مصفوفة تربط كل حالة بلون Bootstrap (للشارات)

$statusLabels = [
    'pending'   => 'قيد الانتظار',
    'confirmed' => 'مؤكد',
    'cancelled' => 'ملغى',
    'completed' => 'مكتمل',
];
// مصفوفة تربط كل حالة بنص عربي (للعرض في الجدول)
?>

<!-- إغلاق وسم PHP والانتقال لكتابة HTML -->

<div class="d-flex justify-content-between align-items-center mb-4">
<!-- حاوية مرنة: توزيع بين البداية والنهاية، محاذاة وسطية، هامش سفلي -->
  <h2><i class="bi bi-calendar-check text-primary"></i> إدارة الحجوزات</h2>
  <!-- العنوان الرئيسي مع أيقونة تقويم -->
  <div>
    <a href="new-booking.php" class="btn btn-success">
    <!-- زر أخضر لإنشاء حجز جديد -->
      <i class="bi bi-calendar-plus"></i> حجز جديد
      <!-- أيقونة تقويم + نص -->
    </a>
    <a href="dashboard.php" class="btn btn-secondary">
    <!-- زر رمادي للرجوع للوحة التحكم -->
      <i class="bi bi-arrow-right"></i> رجوع
      <!-- أيقونة سهم + نص -->
    </a>
  </div>
</div>

<!-- فلاتر -->
<div class="mb-3">
  <?php
  $filters = [
      'all'       => 'الكل (' . $counts['all'] . ')',
      'pending'   => 'قيد الانتظار (' . $counts['pending'] . ')',
      'confirmed' => 'مؤكدة (' . $counts['confirmed'] . ')',
      'cancelled' => 'ملغاة (' . $counts['cancelled'] . ')',
      'completed' => 'مكتملة (' . $counts['completed'] . ')',
  ];
  // مصفوفة تربط كل فلتر بنصه + عدد الحجوزات بين قوسين
  ?>
  <?php foreach ($filters as $key => $label): ?>
  <!-- حلقة تكرار على كل فلتر -->
    <a href="?status=<?= $key ?>"
       class="btn btn-sm btn-<?= $filter === $key ? 'primary' : 'outline-primary' ?> me-1 mb-1">
      <?= e($label) ?>
    </a>
    <!-- زر الفلترة:
         - إذا كان الفلتر الحالي مطابقاً → أزرق معبأ (نشط)
         - وإلا → أزرق شفاف (غير نشط)
         - الرابط: ?status=KEY -->
  <?php endforeach; ?>
</div>

<!-- جدول الحجوزات -->
<div class="card">
<!-- بطاقة تحتوي الجدول -->
  <div class="card-body p-0">
  <!-- جسم البطاقة بدون حشوة (p-0) لأن الجدول يملأ كامل البطاقة -->

    <?php if (empty($bookings)): ?>
    <!-- فحص: إذا لم توجد حجوزات -->
      <p class="text-muted text-center py-5 mb-0">
      <!-- فقرة بوسط النص مع حشوة رأسية كبيرة -->
        <i class="bi bi-inbox fs-1 d-block"></i>
        <!-- أيقونة صندوق فارغ بحجم كبير مع d-block (لتكون في سطر منفصل) -->
        لا توجد حجوزات بهذه الحالة.
        <!-- نص توضيحي -->
      </p>
    <?php else: ?>
    <!-- وإلا (يوجد حجوزات) → اعرض الجدول -->

      <div class="table-responsive">
      <!-- حاوية تجعل الجدول قابلاً للتمرير أفقياً على الشاشات الصغيرة -->
        <table class="table table-hover align-middle mb-0">
        <!-- جدول Bootstrap:
             - table-hover: تظليل الصف عند المرور بالفأرة
             - align-middle: محاذاة المحتوى عمودياً في الوسط
             - mb-0: بدون هامش سفلي -->
          <thead class="table-dark">
          <!-- رأس الجدول بخلفية داكنة -->
            <tr>
              <th>رقم الحجز</th>
              <th>العميل</th>
              <th>رقم الهوية</th>
              <th>الجنسية</th>
              <th>الغرفة</th>
              <th>من</th>
              <th>إلى</th>
              <th>المبلغ</th>
              <th>الحالة</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <!-- حلقة تكرار على كل حجز -->
              <tr>
                <td><strong>#<?= $b['id'] ?></strong></td>
                <!-- رقم الحجز بخط عريض مع # -->
                <td>
                  <strong><?= e($b['full_name'] ?? 'غير معروف') ?></strong>
                  <!-- اسم العميل (مع تأمينه، أو "غير معروف" إذا كان NULL) -->
                  <small class="text-muted d-block">
                  <!-- نص صغير بلون رمادي في سطر منفصل -->
                    <i class="bi bi-telephone"></i> <?= e($b['phone'] ?? '—') ?>
                    <!-- أيقونة هاتف + رقم الهاتف (أو "—" إذا لم يوجد) -->
                  </small>
                </td>
                <td>
                  <span class="badge bg-secondary"><?= e($b['id_number'] ?? '—') ?></span>
                  <!-- شارة رمادية لرقم الهوية -->
                </td>
                <td><?= e($b['nationality'] ?? '—') ?></td>
                <!-- الجنسية (أو "—" إذا لم توجد) -->
                <td>
                  <strong><?= e($b['room_number']) ?></strong>
                  <!-- رقم الغرفة بخط عريض -->
                  <small class="text-muted d-block"><?= e($b['type_name']) ?></small>
                  <!-- اسم نوع الغرفة في سطر منفصل -->
                </td>
                <td><?= $b['check_in'] ?></td>
                <!-- تاريخ الوصول -->
                <td><?= $b['check_out'] ?></td>
                <!-- تاريخ المغادرة -->
                <td><?= number_format($b['total_price'], 2) ?> $</td>
                <!-- المبلغ الإجمالي بتنسيق رقمي -->
                <td>
                  <span class="badge bg-<?= $statusColors[$b['status']] ?? 'secondary' ?>">
                  <!-- شارة بلون الحالة:
                       - warning (أصفر) للانتظار
                       - success (أخضر) للمؤكد
                       - danger (أحمر) للملغى
                       - secondary (رمادي) للمكتمل -->
                    <?= e($statusLabels[$b['status']] ?? $b['status']) ?>
                    <!-- نص الحالة بالعربية -->
                  </span>
                </td>
                <td>
                  <a href="../customer/invoice.php?id=<?= $b['id'] ?>"
                     class="btn btn-sm btn-info" title="طباعة الفاتورة">
                  <!-- زر لعرض/طباعة الفاتورة (في مجلد customer، نصعد مجلداً) -->
                    <i class="bi bi-receipt"></i> فاتورة
                    <!-- أيقونة إيصال + نص -->
                  </a>

                  <?php if ($b['status'] === 'confirmed'): ?>
                  <!-- فحص: إذا كان الحجز مؤكداً → اسمح بإكماله -->
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('إكمال الحجز؟');">
                    <!-- نموذج POST لإكمال الحجز:
                         - d-inline: عرض النموذج في نفس السطر
                         - onsubmit: تأكيد قبل الإرسال -->
                      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                      <!-- رمز CSRF -->
                      <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                      <!-- رقم الحجز -->
                      <input type="hidden" name="action" value="completed">
                      <!-- الحالة الجديدة: مكتمل -->
                      <button class="btn btn-sm btn-success" title="إكمال">
                      <!-- زر أخضر صغير -->
                        <i class="bi bi-flag"></i>
                        <!-- أيقونة علم -->
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if (in_array($b['status'], ['cancelled', 'completed'])): ?>
                  <!-- فحص: إذا كانت الحالة ملغاة أو مكتملة → اسمح بالحذف -->
                    <a href="?delete=<?= $b['id'] ?>&csrf=<?= csrfToken() ?>"
                       class="btn btn-sm btn-danger confirm-delete" title="حذف">
                    <!-- رابط حذف مع رمز CSRF، و confirm-delete دالة JavaScript للتأكيد -->
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
    <?php endif; ?>
    <!-- إغلاق شرط عرض الجدول/الرسالة -->
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<!-- استدعاء الفوتر من مجلد includes -->