<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي (مجلد customer)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة (isLoggedIn، isAdmin، setFlash، redirect، e)

// ==========================================
// 🔐 حماية الصفحة — يجب تسجيل الدخول
// ==========================================
if (!isLoggedIn()) {
// فحص: إذا لم يكن المستخدم مسجلاً دخوله
    setFlash('warning', 'يجب تسجيل الدخول أولاً.');
    // تخزين رسالة تحذير مؤقتة
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول
}

// ==========================================
// إذا كان المدير → وجّهه للوحة الإدارة
// ==========================================
if (isAdmin()) {
// فحص: إذا كان المستخدم مديراً
    redirect('../admin/dashboard.php');
    // توجيهه للوحة المدير (لأن هذه الصفحة للعملاء فقط)
}

$uid = $_SESSION['user_id'];
// تخزين معرف المستخدم الحالي من الجلسة
// نستخدمه في جميع الاستعلامات التالية

// ==========================================
// جلب إحصائيات المستخدم
// ==========================================
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
// تحضير استعلام لعدّ جميع حجوزات المستخدم
// WHERE user_id = ? = حجوزات هذا المستخدم فقط
$stmt->execute([$uid]);
// تنفيذ الاستعلام مع تمرير معرف المستخدم
$totalBookings = $stmt->fetchColumn();
// جلب العدد الإجمالي للحجوزات

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'confirmed'");
// تحضير استعلام لعدّ الحجوزات المؤكدة فقط
// AND status = 'confirmed' = الحالة مؤكدة
$stmt->execute([$uid]);
// تنفيذ الاستعلام
$confirmedBookings = $stmt->fetchColumn();
// جلب عدد الحجوزات المؤكدة

// ==========================================
// جلب آخر 5 حجوزات
// ==========================================
$stmt = $pdo->prepare(
    "SELECT b.*, r.room_number, rt.name AS type_name
     FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC
     LIMIT 5"
);
// استعلام لجلب آخر 5 حجوزات للمستخدم
// b.* = جميع أعمدة جدول bookings
// r.room_number = رقم الغرفة
// rt.name AS type_name = اسم نوع الغرفة
// JOIN rooms = ربط الحجوزات بالغرف
// JOIN room_types = ربط الغرف بأنواعها
// WHERE b.user_id = ? = حجوزات هذا المستخدم فقط
// ORDER BY b.created_at DESC = الأحدث أولاً
// LIMIT 5 = أول 5 حجوزات فقط

$stmt->execute([$uid]);
// تنفيذ الاستعلام
$recentBookings = $stmt->fetchAll();
// جلب جميع الحجوزات كمصفوفة

$pageTitle = 'لوحتي';
// عنوان الصفحة

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر
?>

<!-- إغلاق وسم PHP والانتقال لـ HTML -->

<h2 class="mb-4">
<!-- العنوان الرئيسي مع هامش سفلي -->
  <i class="bi bi-person-circle text-primary"></i>
  <!-- أيقونة شخص بلون أزرق -->
  مرحباً، <?= e($_SESSION['name']) ?> 👋
  <!-- نص ترحيبي مع اسم المستخدم من الجلسة + إيموجي -->
</h2>

<!-- ==========================================
     بطاقات الإحصائيات
     ========================================== -->
<div class="row g-3 mb-4">
<!-- صف شبكي بمسافات وهامش سفلي -->

  <div class="col-md-4">
  <!-- العمود 1: ثلث العرض -->
    <div class="card stat-card bg-primary">
    <!-- بطاقة إحصائية بخلفية زرقاء -->
      <div class="card-body">
      <!-- جسم البطاقة -->
        <h6><i class="bi bi-calendar-check"></i> إجمالي الحجوزات</h6>
        <!-- عنوان صغير مع أيقونة تقويم -->
        <h2 class="mb-0"><?= $totalBookings ?></h2>
        <!-- العدد الإجمالي للحجوزات (من الاستعلام الأول) -->
      </div>
    </div>
  </div>

  <div class="col-md-4">
  <!-- العمود 2 -->
    <div class="card stat-card bg-success">
    <!-- بطاقة إحصائية بخلفية خضراء -->
      <div class="card-body">
        <h6><i class="bi bi-check-circle"></i> حجوزات مؤكدة</h6>
        <!-- عنوان مع أيقونة علامة صح -->
        <h2 class="mb-0"><?= $confirmedBookings ?></h2>
        <!-- عدد الحجوزات المؤكدة (من الاستعلام الثاني) -->
      </div>
    </div>
  </div>

  <div class="col-md-4">
  <!-- العمود 3 -->
    <div class="card stat-card bg-warning">
    <!-- بطاقة إحصائية بخلفية صفراء -->
      <div class="card-body">
        <h6><i class="bi bi-lightning"></i> روابط سريعة</h6>
        <!-- عنوان مع أيقونة برق -->
        <a href="book.php" class="btn btn-sm btn-light mt-2">
        <!-- زر أبيض صغير → حجز جديد -->
          <i class="bi bi-plus-circle"></i> حجز جديد
          <!-- أيقونة + -->
        </a>
        <a href="my-bookings.php" class="btn btn-sm btn-light mt-2">
        <!-- زر أبيض صغير → كل الحجوزات -->
          <i class="bi bi-list"></i> كل حجوزاتي
          <!-- أيقونة قائمة -->
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     آخر الحجوزات
     ========================================== -->
<div class="card">
<!-- بطاقة تحتوي الجدول -->
  <div class="card-header bg-white">
  <!-- رأس البطاقة بخلفية بيضاء -->
    <h5 class="mb-0"><i class="bi bi-clock-history"></i> آخر الحجوزات</h5>
    <!-- عنوان مع أيقونة ساعة -->
  </div>
  <div class="card-body">
  <!-- جسم البطاقة -->

    <?php if (empty($recentBookings)): ?>
    <!-- فحص: إذا لم توجد حجوزات -->
      <p class="text-muted text-center py-4 mb-0">
      <!-- فقرة بوسط النص -->
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        <!-- أيقونة صندوق فارغ كبيرة في سطر منفصل -->
        لا توجد حجوزات بعد.
        <!-- نص توضيحي -->
        <a href="book.php" class="fw-bold">احجز غرفتك الأولى الآن!</a>
        <!-- رابط تشجيعي للحجز -->
      </p>
    <?php else: ?>
    <!-- وإلا (يوجد حجوزات) → اعرض الجدول -->

      <div class="table-responsive">
      <!-- حاوية للتمرير الأفقي -->
        <table class="table table-hover align-middle">
        <!-- جدول Bootstrap مع تظليل الصفوف ومحاذاة وسطية -->
          <thead>
          <!-- رأس الجدول (بدون خلفية داكنة هنا) -->
            <tr>
              <th>#</th>
              <!-- معرف الحجز -->
              <th>الغرفة</th>
              <!-- رقم الغرفة ونوعها -->
              <th>من</th>
              <!-- تاريخ الوصول -->
              <th>إلى</th>
              <!-- تاريخ المغادرة -->
              <th>السعر</th>
              <!-- المبلغ الإجمالي -->
              <th>الحالة</th>
              <!-- حالة الحجز -->
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings as $b): ?>
            <!-- حلقة تكرار على كل حجز -->
              <tr>
                <td><?= $b['id'] ?></td>
                <!-- رقم الحجز -->
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
                <!-- المبلغ بتنسيق رقمي -->
                <td>
                  <?php
                  $statusColors = [
                      'pending'   => 'warning',
                      'confirmed' => 'success',
                      'cancelled' => 'danger',
                      'completed' => 'secondary',
                  ];
                  // مصفوفة تربط كل حالة بلون Bootstrap

                  $statusLabels = [
                      'pending'   => 'قيد الانتظار',
                      'confirmed' => 'مؤكد',
                      'cancelled' => 'ملغى',
                      'completed' => 'مكتمل',
                  ];
                  // مصفوفة تربط كل حالة بنص عربي

                  $color = $statusColors[$b['status']] ?? 'secondary';
                  // جلب اللون (أو رمادي إذا لم توجد الحالة)

                  $label = $statusLabels[$b['status']] ?? $b['status'];
                  // جلب النص (أو الحالة الأصلية)
                  ?>
                  <span class="badge bg-<?= $color ?>"><?= e($label) ?></span>
                  <!-- شارة بلون الحالة مع النص -->
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
<!-- استدعاء الفوتر -->