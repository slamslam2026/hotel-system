<?php
// فتح وسم PHP لبدء كتابة الكود

require_once 'config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات (db.php) مرة واحدة فقط، وإذا لم يوجد يتوقف التنفيذ

require_once 'includes/functions.php';
// استدعاء ملف الدوال المساعدة (functions.php) الذي يحتوي على دوال مثل isLoggedIn() و isAdmin() و redirect() و e() و setFlash()

$id = (int)($_GET['id'] ?? 0);
// جلب رقم الغرفة (id) من رابط الصفحة (GET)
// (int) لتحويل القيمة إلى رقم صحيح للأمان
// ?? 0 تعني: إذا لم يوجد id في الرابط، اجعله 0
// مثال: room.php?id=5 → $id = 5

$stmt = $pdo->prepare(
    "SELECT r.*, rt.name AS type_name, rt.price_per_night, rt.capacity, rt.description AS type_desc
     FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE r.id = ?"
);
// تحضير استعلام SQL آمن (Prepared Statement) لجلب بيانات الغرفة مع بيانات نوعها
// SELECT r.* → جميع أعمدة جدول rooms
// rt.name AS type_name → اسم نوع الغرفة (مع إعادة تسميته لتجنب التعارض)
// rt.price_per_night → السعر الليلي
// rt.capacity → سعة الغرفة
// rt.description AS type_desc → وصف النوع (مع إعادة تسميته)
// JOIN room_types rt ON r.room_type_id = rt.id → ربط جدول rooms بجدول room_types
// WHERE r.id = ? → تصفية حسب رقم الغرفة المُمرَّر
// استخدام ? يحمي من هجمات SQL Injection

$stmt->execute([$id]);
// تنفيذ الاستعلام مع تمرير رقم الغرفة كقيمة

$room = $stmt->fetch();
// جلب صف الغرفة كنتيجة (أو false إذا لم توجد)

if (!$room) {
// فحص: إذا لم توجد الغرفة في قاعدة البيانات
    setFlash('danger', 'الغرفة غير موجودة.');
    // تخزين رسالة خطأ مؤقتة (تظهر مرة واحدة)
    redirect('rooms.php');
    // توجيه المستخدم لصفحة الغرف
}

$stmt = $pdo->prepare(
    "SELECT check_in, check_out FROM bookings
     WHERE room_id = ? AND status IN ('pending', 'confirmed')
     AND check_out >= CURDATE()
     ORDER BY check_in
     LIMIT 5"
);
// تحضير استعلام لجلب الحجوزات القادمة لهذه الغرفة
// SELECT check_in, check_out → تاريخ الوصول والمغادرة فقط
// WHERE room_id = ? → لهذه الغرفة المحددة
// AND status IN ('pending', 'confirmed') → الحجوزات المعلقة أو المؤكدة فقط (لا الملغاة)
// AND check_out >= CURDATE() → الحجوزات التي لم تنتهِ بعد (CURDATE = تاريخ اليوم)
// ORDER BY check_in → مرتبة حسب تاريخ الوصول
// LIMIT 5 → أول 5 حجوزات فقط (للعرض)

$stmt->execute([$id]);
// تنفيذ الاستعلام مع تمرير رقم الغرفة

$upcomingBookings = $stmt->fetchAll();
// جلب جميع الحجوزات القادمة كمصفوفة

// صور محلية
$roomImages = [
    1 => '/hotel-system/assets/images/single.jpg',
    2 => '/hotel-system/assets/images/double.jpg',
    3 => '/hotel-system/assets/images/suite.jpg',
];
// مصفوفة تربط رقم نوع الغرفة (ID) بصورة معينة
// 1 = فردية، 2 = مزدوجة، 3 = جناح

$defaultImage = '/hotel-system/assets/images/default.jpg';
// صورة افتراضية تُستخدم إذا لم يوجد نوع الغرفة في المصفوفة

$imgUrl = $roomImages[$room['room_type_id']] ?? $defaultImage;
// اختر الصورة حسب نوع الغرفة
// ?? تعني: إذا لم توجد الصورة، استخدم الصورة الافتراضية

$pageTitle = 'غرفة ' . $room['room_number'];
// عنوان الصفحة = "غرفة" + رقم الغرفة (يظهر في تبويب المتصفح)

$baseUrl = '';
// متغير يُستخدم لبناء الروابط (فارغ لأننا في الصفحة الرئيسية)

include 'includes/header.php';
// استدعاء ملف الهيدر (رأس الصفحة: القوائم، CSS، إلخ)
?>

<!-- إغلاق وسم PHP والانتقال لكتابة HTML -->

<div class="mb-3">
<!-- حاوية بهامش سفلي -->
  <a href="rooms.php" class="btn btn-outline-secondary btn-sm">
  <!-- زر صغير بخلفية رمادية شفافة للرجوع لصفحة الغرف -->
    <i class="bi bi-arrow-right"></i> رجوع للغرف
    <!-- أيقونة سهم يمين + نص الزر -->
  </a>
</div>

<div class="row g-4">
<!-- صف شبكي بمسافات بين الأعمدة -->

  <div class="col-md-6">
  <!-- العمود الأول: نصف العرض (صورة الغرفة) -->
    <img src="<?= $imgUrl ?>" class="img-fluid rounded shadow-sm w-100"
         alt="غرفة <?= e($room['room_number']) ?>"
         style="height: 500px; object-fit: cover;">
    <!-- صورة الغرفة:
         - img-fluid: تجعل الصورة متجاوبة
         - rounded: زوايا دائرية
         - shadow-sm: ظل خفيف
         - w-100: عرض كامل
         - height: 500px: ارتفاع ثابت
         - object-fit: cover: ملء الإطار دون تشويه
         - alt: نص بديل (مع تأمينه بـ e()) -->
  </div>

  <div class="col-md-6">
  <!-- العمود الثاني: نصف العرض (بيانات الغرفة) -->
    <div class="card h-100">
    <!-- بطاقة بارتفاع كامل -->
      <div class="card-body p-4">
      <!-- جسم البطاقة مع حشوة داخلية كبيرة -->

        <div class="d-flex justify-content-between align-items-start mb-3">
        <!-- حاوية مرنة: توزيع العناصر بين البداية والنهاية، محاذاة علوية، هامش سفلي -->
          <div>
            <h2 class="mb-1">
            <!-- عنوان بحجم 2 مع هامش سفلي صغير -->
              <i class="bi bi-door-closed text-primary"></i>
              غرفة <?= e($room['room_number']) ?>
              <!-- أيقونة باب + نص "غرفة" + رقم الغرفة -->
            </h2>
            <p class="text-muted mb-0"><?= e($room['type_name']) ?></p>
            <!-- اسم نوع الغرفة بلون رمادي (بدون هامش سفلي) -->
          </div>

          <?php if ($room['status'] === 'available'): ?>
          <!-- فحص: إذا كانت الغرفة متاحة -->
            <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> متاحة</span>
            <!-- شارة خضراء "متاحة" -->
          <?php elseif ($room['status'] === 'occupied'): ?>
          <!-- وإلا: إذا كانت مشغولة -->
            <span class="badge bg-danger fs-6"><i class="bi bi-x-circle"></i> مشغولة</span>
            <!-- شارة حمراء "مشغولة" -->
          <?php else: ?>
          <!-- وإلا (صيانة) -->
            <span class="badge bg-warning fs-6"><i class="bi bi-tools"></i> صيانة</span>
            <!-- شارة صفراء "صيانة" -->
          <?php endif; ?>
          <!-- إغلاق الشرط -->
        </div>

        <hr>
        <!-- خط فاصل أفقي -->

        <p class="text-muted"><?= e($room['type_desc']) ?></p>
        <!-- وصف نوع الغرفة بلون رمادي -->

        <ul class="list-unstyled my-4">
        <!-- قائمة بدون نقاط مع هامش رأسي كبير -->
          <li class="mb-2"><i class="bi bi-layers text-primary"></i> <strong>الطابق:</strong> <?= $room['floor'] ?></li>
          <!-- عنصر: أيقونة طبقات + "الطابق" + رقم الطابق -->
          <li class="mb-2"><i class="bi bi-people text-primary"></i> <strong>السعة:</strong> حتى <?= $room['capacity'] ?> أشخاص</li>
          <!-- عنصر: أيقونة أشخاص + "السعة" + عدد الأشخاص -->
          <li class="mb-2"><i class="bi bi-wifi text-primary"></i> <strong>واي فاي مجاني</strong></li>
          <!-- عنصر: ميزة الواي فاي (ثابتة لجميع الغرف) -->
          <li class="mb-2"><i class="bi bi-snow text-primary"></i> <strong>تكييف مركزي</strong></li>
          <!-- عنصر: ميزة التكييف (ثابتة) -->
          <li class="mb-2"><i class="bi bi-tv text-primary"></i> <strong>تلفاز بشاشة مسطحة</strong></li>
          <!-- عنصر: ميزة التلفاز (ثابتة) -->
        </ul>

        <hr>
        <!-- خط فاصل أفقي -->

        <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- حاوية مرنة: توزيع بين البداية والنهاية، محاذاة عمودية وسطية -->
          <div>
            <small class="text-muted">السعر / الليلة</small>
            <!-- نص صغير "السعر / الليلة" بلون رمادي -->
            <h3 class="text-primary mb-0"><?= number_format($room['price_per_night'], 2) ?> $</h3>
            <!-- السعر الليلي بخط كبير ولون أزرق مع تنسيق رقمي بخانتين عشريتين -->
          </div>
        </div>

        <?php if ($room['status'] !== 'available'): ?>
        <!-- فحص: إذا كانت الغرفة غير متاحة (مشغولة أو صيانة) -->
          <button class="btn btn-secondary w-100 py-2" disabled>
          <!-- زر رمادي معطّل (disabled) -->
            <i class="bi bi-x-circle"></i> غير متاحة للحجز
            <!-- أيقونة خطأ + نص "غير متاحة للحجز" -->
          </button>
        <?php elseif (isLoggedIn() && !isAdmin()): ?>
        <!-- وإلا: إذا كان المستخدم مسجلاً وليس مديراً (عميل عادي) -->
          <a href="customer/book.php?room_id=<?= $room['id'] ?>" class="btn btn-success btn-lg w-100">
          <!-- زر أخضر كبير يقود لصفحة الحجز مع تمرير رقم الغرفة -->
            <i class="bi bi-calendar-check"></i> احجز الآن
            <!-- أيقونة تقويم + نص "احجز الآن" -->
          </a>
        <?php elseif (!isLoggedIn()): ?>
        <!-- وإلا: إذا لم يكن المستخدم مسجلاً -->
          <a href="login.php" class="btn btn-warning btn-lg w-100">
          <!-- زر أصفر كبير يقود لصفحة تسجيل الدخول -->
            <i class="bi bi-box-arrow-in-right"></i> سجّل الدخول للحجز
            <!-- أيقونة دخول + نص "سجّل الدخول للحجز" -->
          </a>
        <?php else: ?>
        <!-- وإلا (المستخدم مدير) -->
          <div class="alert alert-info mb-0">
          <!-- صندوق تنبيه أزرق -->
            <i class="bi bi-info-circle"></i> المدير لا يمكنه الحجز. سجّل كعميل للحجز.
            <!-- رسالة توضيحية للمدير -->
          </div>
        <?php endif; ?>
        <!-- إغلاق الشرط -->
      </div>
    </div>
  </div>
</div>

<?php if (!empty($upcomingBookings)): ?>
<!-- فحص: إذا كانت هناك حجوزات قادمة لهذه الغرفة -->
  <div class="card mt-4">
  <!-- بطاقة مع هامش علوي كبير -->
    <div class="card-header bg-white">
    <!-- رأس البطاقة بخلفية بيضاء -->
      <h5 class="mb-0"><i class="bi bi-calendar-x text-warning"></i> تواريخ محجوزة قادمة (للتخطيط)</h5>
      <!-- عنوان بحجم 5 مع أيقونة تقويم + نص توضيحي -->
    </div>
    <div class="card-body">
    <!-- جسم البطاقة -->
      <p class="text-muted small mb-3">هذه الفترات محجوزة مسبقاً. اختر تواريخ أخرى عند الحجز.</p>
      <!-- شرح توضيحي بلون رمادي وخط صغير -->
      <div class="row g-2">
      <!-- صف شبكي بمسافات صغيرة -->
        <?php foreach ($upcomingBookings as $b): ?>
        <!-- حلقة تكرار على كل حجز قادم -->
          <div class="col-md-4">
          <!-- عمود بعرض الثلث -->
            <div class="alert alert-warning mb-0 py-2 small">
            <!-- صندوق تنبيه أصفر مع حشوة رأسية صغيرة وخط صغير -->
              <i class="bi bi-calendar-event"></i>
              من <strong><?= $b['check_in'] ?></strong>
              إلى <strong><?= $b['check_out'] ?></strong>
              <!-- عرض تاريخ الوصول والمغادرة -->
            </div>
          </div>
        <?php endforeach; ?>
        <!-- إغلاق حلقة التكرار -->
      </div>
    </div>
  </div>
<?php endif; ?>
<!-- إغلاق الشرط -->

<?php include 'includes/footer.php'; ?>
<!-- استدعاء ملف الفوتر (تذييل الصفحة: الحقوق، السكربتات) -->