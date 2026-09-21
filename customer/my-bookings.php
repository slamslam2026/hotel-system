<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي (مجلد customer)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة (isLoggedIn، isAdmin، verifyCsrf، csrfToken، e، setFlash، redirect)

if (!isLoggedIn()) {
// فحص: إذا لم يكن المستخدم مسجلاً دخوله
    setFlash('warning', 'يجب تسجيل الدخول أولاً.');
    // رسالة تحذير مؤقتة
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول
}

if (isAdmin()) {
// فحص: إذا كان المستخدم مديراً
    redirect('../admin/dashboard.php');
    // توجيهه للوحة المدير (هذه الصفحة للعملاء فقط)
}

$uid = $_SESSION['user_id'];
// تخزين معرف المستخدم الحالي من الجلسة

// ==========================================
// معالجة إلغاء الحجز
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
// فحص شرطين:
// 1. الطلب بطريقة POST
// 2. يوجد حقل cancel_id في الطلب

    if (!verifyCsrf($_POST['csrf'] ?? '')) {
    // فحص رمز CSRF
        setFlash('danger', 'طلب غير صالح.');
        redirect('my-bookings.php');
    }

    $cancel_id = (int)$_POST['cancel_id'];
    // جلب معرف الحجز المراد إلغاؤه (تحويل لرقم صحيح)

    $stmt = $pdo->prepare(
        "UPDATE bookings
         SET status = 'cancelled'
         WHERE id = ? AND user_id = ? AND status = 'pending'"
    );
    // تحضير استعلام لتحديث حالة الحجز إلى 'cancelled'
    // الشروط الثلاثة مهمة جداً:
    // 1. id = ? → الحجز المطلوب
    // 2. AND user_id = ? → يجب أن يكون حجز هذا المستخدم (حماية من IDOR)
    // 3. AND status = 'pending' → فقط الحجوزات قيد الانتظار (لا يمكن إلغاء مؤكد/مكتمل)

    $stmt->execute([$cancel_id, $uid]);
    // تنفيذ الاستعلام مع تمرير معرف الحجز ومعرف المستخدم

    if ($stmt->rowCount() > 0) {
    // فحص: إذا تم تعديل صف واحد على الأقل
    // rowCount() ترجع عدد الصفوف المتأثرة بالاستعلام
    // إذا > 0 → الحجز تم إلغاؤه بنجاح
        setFlash('success', 'تم إلغاء الحجز بنجاح.');
    } else {
    // وإلا (لم يتم تعديل أي صف) → يعني:
    // - الحجز غير موجود
    // - أو لا يخص هذا المستخدم
    // - أو حالته ليست 'pending'
        setFlash('danger', 'لا يمكن إلغاء هذا الحجز.');
    }
    redirect('my-bookings.php');
    // إعادة التوجيه لصفحة الحجوزات
}

// ==========================================
// جلب جميع حجوزات المستخدم
// ==========================================
$stmt = $pdo->prepare(
    "SELECT b.*, r.room_number, r.floor, rt.name AS type_name, rt.price_per_night
     FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
// استعلام لجلب جميع حجوزات المستخدم:
// b.* = جميع أعمدة bookings
// r.room_number, r.floor = رقم الغرفة والطابق
// rt.name AS type_name = اسم نوع الغرفة
// rt.price_per_night = السعر الليلي
// JOIN rooms = ربط الحجوزات بالغرف
// JOIN room_types = ربط الغرف بأنواعها
// WHERE b.user_id = ? = حجوزات هذا المستخدم فقط (حماية)
// ORDER BY b.created_at DESC = الأحدث أولاً

$stmt->execute([$uid]);
// تنفيذ الاستعلام مع تمرير معرف المستخدم

$bookings = $stmt->fetchAll();
// جلب جميع الحجوزات كمصفوفة

$pageTitle = 'حجوزاتي';
// عنوان الصفحة

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر
?>

<!-- إغلاق وسم PHP والانتقال لـ HTML -->

<div class="d-flex justify-content-between align-items-center mb-4">
<!-- حاوية مرنة: توزيع بين البداية والنهاية -->
  <h2><i class="bi bi-list-check text-primary"></i> حجوزاتي</h2>
  <!-- العنوان مع أيقونة قائمة -->
  <a href="book.php" class="btn btn-primary">
  <!-- زر أزرق للحجز الجديد -->
    <i class="bi bi-plus-circle"></i> حجز جديد
  </a>
</div>

<?php if (empty($bookings)): ?>
<!-- فحص: إذا لم توجد حجوزات -->
  <div class="card">
  <!-- بطاقة -->
    <div class="card-body text-center py-5">
    <!-- جسم البطاقة بوسط النص وحشوة رأسية كبيرة -->
      <i class="bi bi-inbox fs-1 text-muted"></i>
      <!-- أيقونة صندوق فارغ كبيرة -->
      <h4 class="mt-3">لا توجد حجوزات بعد</h4>
      <!-- عنوان -->
      <p class="text-muted">احجز غرفتك الأولى الآن!</p>
      <!-- نص تشجيعي -->
      <a href="book.php" class="btn btn-primary">
      <!-- زر للحجز -->
        <i class="bi bi-calendar-plus"></i> احجز غرفة
      </a>
    </div>
  </div>
<?php else: ?>
<!-- وإلا (يوجد حجوزات) → اعرض الجدول -->
  <div class="card">
  <!-- بطاقة -->
    <div class="card-body p-0">
    <!-- جسم البطاقة بدون حشوة -->
      <div class="table-responsive">
      <!-- حاوية للتمرير الأفقي -->
        <table class="table table-hover align-middle mb-0">
        <!-- جدول Bootstrap -->
          <thead class="table-dark">
          <!-- رأس الجدول بخلفية داكنة -->
            <tr>
              <th>#</th>
              <!-- رقم الحجز -->
              <th>الغرفة</th>
              <!-- رقم + نوع الغرفة -->
              <th>من</th>
              <!-- تاريخ الوصول -->
              <th>إلى</th>
              <!-- تاريخ المغادرة -->
              <th>الضيوف</th>
              <!-- عدد الضيوف -->
              <th>السعر</th>
              <!-- المبلغ الإجمالي -->
              <th>الحالة</th>
              <!-- حالة الحجز -->
              <th>إجراءات</th>
              <!-- الأزرار (فاتورة + إلغاء) -->
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <!-- حلقة تكرار على كل حجز -->
              <tr>
                <td><?= $b['id'] ?></td>
                <!-- رقم الحجز -->
                <td>
                  <strong><?= e($b['room_number']) ?></strong>
                  <!-- رقم الغرفة بخط عريض -->
                  <small class="text-muted d-block">
                  <!-- نص صغير في سطر منفصل -->
                    <?= e($b['type_name']) ?> — الطابق <?= $b['floor'] ?>
                    <!-- اسم النوع — الطابق -->
                  </small>
                </td>
                <td><?= $b['check_in'] ?></td>
                <!-- تاريخ الوصول -->
                <td><?= $b['check_out'] ?></td>
                <!-- تاريخ المغادرة -->
                <td><?= $b['guests'] ?></td>
                <!-- عدد الضيوف -->
                <td><?= number_format($b['total_price'], 2) ?> $</td>
                <!-- المبلغ بتنسيق رقمي -->
                <td>
                  <?php
                  $colors = [
                      'pending'   => 'warning',
                      'confirmed' => 'success',
                      'cancelled' => 'danger',
                      'completed' => 'secondary',
                  ];
                  // مصفوفة تربط كل حالة بلون Bootstrap

                  $labels = [
                      'pending'   => 'قيد الانتظار',
                      'confirmed' => 'مؤكد',
                      'cancelled' => 'ملغى',
                      'completed' => 'مكتمل',
                  ];
                  // مصفوفة تربط كل حالة بنص عربي
                  ?>
                  <span class="badge bg-<?= $colors[$b['status']] ?? 'secondary' ?>">
                  <!-- شارة بلون الحالة (أو رمادي إذا لم توجد) -->
                    <?= e($labels[$b['status']] ?? $b['status']) ?>
                    <!-- نص الحالة بالعربية -->
                  </span>
                </td>
                <td>
                  <!-- ===== زر الفاتورة (يظهر لكل الحجوزات) ===== -->
                  <a href="invoice.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-info" title="عرض الفاتورة">
                  <!-- زر أزرق شفاف → رابط لصفحة الفاتورة -->
                    <i class="bi bi-receipt"></i> فاتورة
                    <!-- أيقونة إيصال + نص -->
                  </a>

                  <!-- ===== زر الإلغاء (يظهر فقط للحجوزات قيد الانتظار) ===== -->
                  <?php if ($b['status'] === 'pending'): ?>
                  <!-- فحص: إذا كانت حالة الحجز 'pending' -->
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('هل أنت متأكد من إلغاء الحجز؟');">
                    <!-- نموذج POST:
                         - d-inline: عرض النموذج في نفس السطر
                         - onsubmit: نافذة تأكيد قبل الإرسال -->
                      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                      <!-- رمز CSRF -->
                      <input type="hidden" name="cancel_id" value="<?= $b['id'] ?>">
                      <!-- معرف الحجز المراد إلغاؤه -->
                      <button class="btn btn-sm btn-outline-danger">
                      <!-- زر أحمر شفاف -->
                        <i class="bi bi-x-circle"></i> إلغاء
                        <!-- أيقونة دائرة X + نص -->
                      </button>
                    </form>
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
<?php endif; ?>
<!-- إغلاق شرط عرض الجدول/الرسالة -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
<!-- استدعاء الفوتر -->