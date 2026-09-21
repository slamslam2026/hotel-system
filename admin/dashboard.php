<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي (مجلد admin)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة من مجلد includes

// 🔐 حماية مزدوجة — يجب تسجيل الدخول + يجب أن يكون مديراً
if (!isLoggedIn()) {
// فحص أول: إذا لم يكن المستخدم مسجلاً دخوله
    setFlash('warning', 'يجب تسجيل الدخول أولاً.');
    // تخزين رسالة تحذير مؤقتة
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول
}
if (!isAdmin()) {
// فحص ثانٍ: إذا كان المستخدم مسجلاً لكنه ليس مديراً
    setFlash('danger', 'لا تملك صلاحية الوصول لهذه الصفحة.');
    // تخزين رسالة خطأ
    redirect('../customer/dashboard.php');
    // توجيهه للوحة العميل (لأنه مستخدم عادي)
}

// ===== إحصائيات =====
$stats = [
    'users'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    // عدد جميع المستخدمين في جدول users
    'rooms'    => $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    // عدد جميع الغرف في جدول rooms
    'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    // عدد جميع الحجوزات في جدول bookings
    'pending'  => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    // عدد الحجوزات قيد الانتظار فقط
    'revenue'  => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
    // مجموع الإيرادات من الحجوزات المؤكدة فقط
    // COALESCE(..., 0) تعني: إذا كانت النتيجة NULL (لا حجوزات مؤكدة)، أرجع 0
    // SUM(total_price) تجمع كل الأسعار الإجمالية
];
// fetchColumn() ترجع قيمة واحدة (العدد أو المجموع)

// ===== آخر 5 حجوزات =====
$recent = $pdo->query(
    "SELECT b.*, u.full_name, r.room_number, rt.name AS type_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     ORDER BY b.created_at DESC
     LIMIT 5"
)->fetchAll();
// استعلام مباشر (بدون Prepared Statement) لأنه لا توجد مدخلات من المستخدم
// SELECT b.* → جميع أعمدة جدول الحجوزات
// u.full_name → اسم العميل الكامل
// r.room_number → رقم الغرفة
// rt.name AS type_name → اسم نوع الغرفة
// JOIN users → ربط بالحجوزات بالمستخدمين
// JOIN rooms → ربط بالغرف
// JOIN room_types → ربط بأنواع الغرف
// ORDER BY b.created_at DESC → ترتيب حسب تاريخ الإنشاء (الأحدث أولاً)
// LIMIT 5 → أول 5 حجوزات فقط
// fetchAll() → جلب جميع الصفوف كمصفوفة

$pageTitle = 'لوحة التحكم';
// عنوان الصفحة في تبويب المتصفح

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى لأننا في admin)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر
?>

<!-- إغلاق وسم PHP والانتقال لكتابة HTML -->

<h2 class="mb-4">
<!-- عنوان بحجم 2 مع هامش سفلي -->
  <i class="bi bi-speedometer2 text-primary"></i> لوحة التحكم
  <!-- أيقونة عداد سرعة + نص العنوان -->
</h2>

<!-- بطاقات الإحصائيات -->
<div class="row g-3 mb-4">
<!-- صف شبكي بمسافات وهامش سفلي -->

  <div class="col-md-3">
  <!-- العمود 1: ربع العرض (3/12) -->
    <div class="card stat-card bg-primary">
    <!-- بطاقة بخلفية زرقاء -->
      <div class="card-body">
      <!-- جسم البطاقة -->
        <h6><i class="bi bi-people"></i> المستخدمون</h6>
        <!-- عنوان صغير مع أيقونة أشخاص -->
        <h2 class="mb-0"><?= $stats['users'] ?></h2>
        <!-- عدد المستخدمين بخط كبير -->
      </div>
    </div>
  </div>

  <div class="col-md-3">
  <!-- العمود 2 -->
    <div class="card stat-card bg-success">
    <!-- بطاقة بخلفية خضراء -->
      <div class="card-body">
        <h6><i class="bi bi-door-open"></i> الغرف</h6>
        <!-- عنوان مع أيقونة باب مفتوح -->
        <h2 class="mb-0"><?= $stats['rooms'] ?></h2>
        <!-- عدد الغرف -->
      </div>
    </div>
  </div>

  <div class="col-md-3">
  <!-- العمود 3 -->
    <div class="card stat-card bg-warning">
    <!-- بطاقة بخلفية صفراء -->
      <div class="card-body">
        <h6><i class="bi bi-calendar-check"></i> الحجوزات</h6>
        <!-- عنوان مع أيقونة تقويم -->
        <h2 class="mb-0"><?= $stats['bookings'] ?></h2>
        <!-- العدد الإجمالي للحجوزات -->
        <small><?= $stats['pending'] ?> قيد الانتظار</small>
        <!-- نص صغير يعرض عدد الحجوزات المعلقة -->
      </div>
    </div>
  </div>

  <div class="col-md-3">
  <!-- العمود 4 -->
    <div class="card stat-card bg-danger">
    <!-- بطاقة بخلفية حمراء -->
      <div class="card-body">
        <h6><i class="bi bi-cash"></i> الإيرادات</h6>
        <!-- عنوان مع أيقونة نقود -->
        <h2 class="mb-0"><?= number_format($stats['revenue'], 0) ?> $</h2>
        <!-- مجموع الإيرادات بتنسيق رقمي (بدون خانات عشرية) -->
        <small>من الحجوزات المؤكدة</small>
        <!-- نص توضيحي -->
      </div>
    </div>
  </div>
</div>

<!-- روابط سريعة -->
<div class="mb-4">
<!-- حاوية بهامش سفلي -->
  <a href="rooms.php" class="btn btn-outline-primary">
  <!-- زر شفاف أزرق → إدارة الغرف -->
    <i class="bi bi-door-open"></i> إدارة الغرف
  </a>
  <a href="bookings.php" class="btn btn-outline-success">
  <!-- زر شفاف أخضر → إدارة الحجوزات -->
    <i class="bi bi-calendar-check"></i> إدارة الحجوزات
  </a>
  <a href="users.php" class="btn btn-outline-warning">
  <!-- زر شفاف أصفر → المستخدمون -->
    <i class="bi bi-people"></i> المستخدمون
  </a>
  <a href="room-types.php" class="btn btn-outline-info">
  <!-- زر شفاف سماوي → أنواع الغرف -->
    <i class="bi bi-tags"></i> أنواع الغرف
  </a>
</div>

<!-- أحدث الحجوزات -->
<div class="card">
<!-- بطاقة تحتوي جدول أحدث الحجوزات -->
  <div class="card-header bg-white">
  <!-- رأس البطاقة بخلفية بيضاء -->
    <h5 class="mb-0"><i class="bi bi-clock-history"></i> أحدث الحجوزات</h5>
    <!-- عنوان بحجم 5 مع أيقونة ساعة -->
  </div>
  <div class="card-body p-0">
  <!-- جسم البطاقة بدون حشوة (لأن الجدول يملأ كامل العرض) -->

    <?php if (empty($recent)): ?>
    <!-- فحص: إذا لم توجد حجوزات -->
      <p class="text-muted text-center py-4 mb-0">لا توجد حجوزات بعد.</p>
      <!-- رسالة بوسط النص -->
    <?php else: ?>
    <!-- وإلا → اعرض الجدول -->

      <div class="table-responsive">
      <!-- حاوية تجعل الجدول قابلاً للتمرير أفقياً -->
        <table class="table table-hover align-middle mb-0">
        <!-- جدول Bootstrap مع تظليل الصفوف ومحاذاة وسطية -->
          <thead class="table-dark">
          <!-- رأس الجدول بخلفية داكنة -->
            <tr>
              <th>#</th>
              <!-- عمود رقم الحجز -->
              <th>العميل</th>
              <!-- عمود اسم العميل -->
              <th>الغرفة</th>
              <!-- عمود رقم الغرفة ونوعها -->
              <th>من</th>
              <!-- عمود تاريخ الوصول -->
              <th>إلى</th>
              <!-- عمود تاريخ المغادرة -->
              <th>المبلغ</th>
              <!-- عمود المبلغ الإجمالي -->
              <th>الحالة</th>
              <!-- عمود حالة الحجز -->
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $b): ?>
            <!-- حلقة تكرار على كل حجز من الحجوزات الخمسة -->
              <tr>
                <td><?= $b['id'] ?></td>
                <!-- رقم الحجز -->
                <td><?= e($b['full_name']) ?></td>
                <!-- اسم العميل (مع تأمينه بـ e()) -->
                <td>
                  <strong><?= e($b['room_number']) ?></strong>
                  <!-- رقم الغرفة بخط عريض -->
                  <small class="text-muted d-block"><?= e($b['type_name']) ?></small>
                  <!-- اسم النوع في سطر منفصل بلون رمادي -->
                </td>
                <td><?= $b['check_in'] ?></td>
                <!-- تاريخ الوصول -->
                <td><?= $b['check_out'] ?></td>
                <!-- تاريخ المغادرة -->
                <td><?= number_format($b['total_price'], 2) ?> $</td>
                <!-- المبلغ الإجمالي بتنسيق رقمي -->
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
                  <!-- شارة بلون الحالة:
                       - إذا لم توجد الحالة → 'secondary' (رمادي) -->
                    <?= e($labels[$b['status']] ?? $b['status']) ?>
                    <!-- نص الحالة (أو الحالة الأصلية إذا لم توجد في المصفوفة) -->
                  </span>
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