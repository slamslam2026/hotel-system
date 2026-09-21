<?php
// فتح وسم PHP لبدء كتابة الكود

require_once 'config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات (db.php) مرة واحدة فقط، وإذا لم يوجد يتوقف التنفيذ

require_once 'includes/functions.php';
// استدعاء ملف الدوال المساعدة (functions.php) مثل isLoggedIn() و isAdmin() و e()

// الفلترة حسب النوع
$typeId = (int)($_GET['type'] ?? 0);
// جلب رقم نوع الغرفة من رابط الصفحة (GET) إن وُجد
// (int) لتحويل القيمة إلى رقم صحيح للأمان
// ?? 0 تعني: إذا لم يوجد type في الرابط، اجعله 0 (= عرض الكل)
// مثال: rooms.php?type=2 → $typeId = 2

// بناء الاستعلام
$sql = "SELECT r.*, rt.name AS type_name, rt.price_per_night, rt.capacity, rt.description
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.status = 'available'";
// بناء استعلام SQL الأساسي لجلب الغرف المتاحة فقط:
// SELECT r.* → جميع أعمدة جدول rooms
// rt.name AS type_name → اسم نوع الغرفة
// rt.price_per_night → السعر الليلي
// rt.capacity → السعة
// rt.description → وصف النوع
// JOIN room_types rt ON r.room_type_id = rt.id → ربط جدول rooms بجدول room_types
// WHERE r.status = 'available' → فقط الغرف المتاحة

$params = [];
// مصفوفة فارغة لتخزين قيم المعاملات (Parameters) التي ستُمرَّر للاستعلام
// نستخدمها لأن عدد الشروط قد يتغير حسب الفلترة

if ($typeId > 0) {
// فحص: إذا كان هناك فلترة حسب نوع معين (typeId > 0)
    $sql .= " AND rt.id = ?";
    // إضافة شرط جديد للاستعلام: تصفية حسب نوع الغرفة
    // .= تعني: أضف هذا النص إلى نهاية $sql
    // استخدام ? يحمي من SQL Injection
    $params[] = $typeId;
    // إضافة قيمة $typeId إلى مصفوفة المعاملات
}

$sql .= " ORDER BY rt.price_per_night, r.room_number";
// إضافة ترتيب النتائج:
// أولاً حسب السعر الليلي (من الأرخص للأغلى)
// ثم حسب رقم الغرفة

$stmt = $pdo->prepare($sql);
// تحضير الاستعلام النهائي (Prepared Statement) للأمان ضد SQL Injection

$stmt->execute($params);
// تنفيذ الاستعلام مع تمرير مصفوفة المعاملات
// (إذا كانت فارغة، يُنفَّذ بدون شروط إضافية)

$rooms = $stmt->fetchAll();
// جلب جميع الغرف المطابقة كمصفوفة

// جميع الأنواع للفلترة
$types = $pdo->query("SELECT * FROM room_types ORDER BY name")->fetchAll();
// جلب جميع أنواع الغرف من جدول room_types مرتبة حسب الاسم
// نستخدمها لعرض أزرار الفلترة في الأعلى

// ==========================================
// إعدادات الصور المحلية
// ==========================================
$roomImages = [
    1 => '/hotel-system/assets/images/single.jpg',
    2 => '/hotel-system/assets/images/double.jpg',
    3 => '/hotel-system/assets/images/suite.jpg',
];
// مصفوفة تربط رقم نوع الغرفة (ID) بصورة معينة
// 1 = فردية، 2 = مزدوجة، 3 = جناح

$defaultImage = '/hotel-system/assets/images/default.jpg';
// صورة افتراضية تُستخدم إذا لم يوجد نوع الغرفة في المصفوفة

$pageTitle = 'الغرف المتاحة';
// عنوان الصفحة الذي سيظهر في تبويب المتصفح

$baseUrl = '';
// متغير يُستخدم لبناء الروابط (فارغ لأننا في الصفحة الرئيسية)

include 'includes/header.php';
// استدعاء ملف الهيدر (رأس الصفحة: القوائم، CSS، إلخ)
?>

<!-- إغلاق وسم PHP والانتقال لكتابة HTML -->

<div class="d-flex justify-content-between align-items-center mb-4">
<!-- حاوية مرنة: توزيع العناصر بين البداية والنهاية، محاذاة عمودية وسطية، هامش سفلي -->
  <h2><i class="bi bi-door-open text-primary"></i> الغرف المتاحة</h2>
  <!-- عنوان بحجم 2 مع أيقونة باب مفتوح بلون أزرق -->
  <span class="badge bg-info fs-6"><?= count($rooms) ?> غرفة</span>
  <!-- شارة زرقاء تعرض عدد الغرف المعروضة باستخدام count() -->
</div>

<!-- فلترة حسب النوع -->
<div class="card mb-4">
<!-- بطاقة مع هامش سفلي -->
  <div class="card-body">
  <!-- جسم البطاقة -->
    <h6 class="mb-3"><i class="bi bi-funnel"></i> الفلترة حسب النوع:</h6>
    <!-- عنوان صغير مع أيقونة قمع (فلتر) -->
    <div>
      <a href="rooms.php"
         class="btn btn-sm <?= !$typeId ? 'btn-primary' : 'btn-outline-primary' ?> me-1 mb-1">
        <i class="bi bi-grid"></i> الكل
      </a>
      <!-- زر "الكل":
           - إذا لم يكن هناك فلترة (!$typeId): الزر أزرق معبأ (btn-primary)
           - إذا كان هناك فلترة: الزر أزرق شفاف (btn-outline-primary)
           - الرابط: rooms.php بدون أي معاملات (يعرض كل الغرف) -->

      <?php foreach ($types as $t): ?>
      <!-- حلقة تكرار على كل نوع غرفة -->
        <a href="rooms.php?type=<?= $t['id'] ?>"
           class="btn btn-sm <?= $typeId == $t['id'] ? 'btn-primary' : 'btn-outline-primary' ?> me-1 mb-1">
          <?= e($t['name']) ?>
          <!-- اسم نوع الغرفة (مع تأمينه بـ e()) -->
          <span class="badge bg-light text-dark">
            <?= number_format($t['price_per_night'], 0) ?> $
          </span>
          <!-- شارة صغيرة تعرض السعر الليلي (بدون خانات عشرية) -->
        </a>
        <!-- زر الفلترة لهذا النوع:
             - إذا كان النوع الحالي مطابقاً لـ typeId: الزر أزرق معبأ (نشط)
             - وإلا: الزر أزرق شفاف (غير نشط)
             - الرابط: rooms.php?type=ID -->
      <?php endforeach; ?>
      <!-- إغلاق حلقة التكرار -->
    </div>
  </div>
</div>

<!-- شبكة الغرف -->
<?php if (empty($rooms)): ?>
<!-- فحص: إذا لم توجد غرف مطابقة (المصفوفة فارغة) -->
  <div class="card">
    <div class="card-body text-center py-5">
    <!-- جسم بطاقة بوسط النص وحشوة رأسية كبيرة -->
      <i class="bi bi-inbox fs-1 text-muted"></i>
      <!-- أيقونة صندوق وارد فارغ بلون رمادي بحجم كبير -->
      <h4 class="mt-3">لا توجد غرف متاحة</h4>
      <!-- عنوان بحجم 4 -->
      <p class="text-muted">جرّب فلترة أخرى أو عُد لاحقاً</p>
      <!-- نص توضيحي -->
      <a href="rooms.php" class="btn btn-primary">عرض كل الغرف</a>
      <!-- زر لعرض كل الغرف بدون فلترة -->
    </div>
  </div>
<?php else: ?>
<!-- وإلا (يوجد غرف) → اعرضها في شبكة -->
  <div class="row g-4">
  <!-- صف شبكي بمسافات بين الأعمدة -->

    <?php foreach ($rooms as $r): ?>
    <!-- حلقة تكرار على كل غرفة في المصفوفة -->

      <?php $imgUrl = $roomImages[$r['room_type_id']] ?? $defaultImage; ?>
      <!-- تحديد صورة الغرفة حسب نوعها، أو الصورة الافتراضية -->

      <div class="col-md-4">
      <!-- عمود بعرض الثلث (3 غرف في الصف) -->

        <div class="card room-card h-100">
        <!-- بطاقة غرفة بارتفاع كامل -->

          <img src="<?= $imgUrl ?>"
               class="card-img-top"
               alt="غرفة <?= e($r['room_number']) ?>"
               style="height: 240px; object-fit: cover;">
          <!-- صورة الغرفة:
               - card-img-top: صورة أعلى البطاقة
               - height: 240px: ارتفاع ثابت
               - object-fit: cover: ملء الإطار دون تشويه
               - alt: نص بديل (مع تأمينه بـ e()) -->

          <div class="card-body d-flex flex-column">
          <!-- جسم البطاقة مع ترتيب عمودي مرن -->

            <div class="d-flex justify-content-between align-items-start mb-2">
            <!-- حاوية مرنة: توزيع بين البداية والنهاية، محاذاة علوية، هامش سفلي -->
              <h5 class="card-title mb-0">
              <!-- عنوان البطاقة (بدون هامش سفلي) -->
                <i class="bi bi-door-closed text-primary"></i>
                غرفة <?= e($r['room_number']) ?>
                <!-- أيقونة باب + نص "غرفة" + رقم الغرفة -->
              </h5>
              <span class="badge bg-success">
              <!-- شارة خضراء -->
                <i class="bi bi-check-circle"></i> متاحة
                <!-- أيقونة صح + نص "متاحة" -->
              </span>
            </div>

            <p class="text-muted mb-2"><?= e($r['type_name']) ?></p>
            <!-- اسم نوع الغرفة بلون رمادي -->

            <div class="mb-3 small">
            <!-- حاوية بهامش سفلي وخط صغير -->
              <i class="bi bi-layers"></i> الطابق <?= $r['floor'] ?>
              <!-- أيقونة طبقات + رقم الطابق -->
              &nbsp;•&nbsp;
              <!-- مسافة + نقطة فاصلة + مسافة -->
              <i class="bi bi-people"></i> حتى <?= $r['capacity'] ?> أشخاص
              <!-- أيقونة أشخاص + السعة القصوى -->
            </div>

            <p class="card-text text-muted small flex-grow-1">
            <!-- وصف الغرفة:
                 - text-muted: لون رمادي
                 - small: خط صغير
                 - flex-grow-1: يتمدد لملء الفراغ (لتوحيد ارتفاع البطاقات) -->
              <?= e($r['description']) ?>
              <!-- نص الوصف (مع تأمينه بـ e()) -->
            </p>

            <p class="fs-4 text-primary mb-3">
            <!-- فقرة السعر بخط كبير ولون أزرق -->
              <?= number_format($r['price_per_night'], 2) ?> $
              <!-- السعر الليلي بتنسيق رقمي بخانتين عشريتين -->
              <small class="text-muted fs-6">/ الليلة</small>
              <!-- نص صغير "/ الليلة" بلون رمادي -->
            </p>

            <div class="d-flex gap-2">
            <!-- حاوية مرنة مع فجوة بين الأزرار -->
              <a href="room-details.php?id=<?= $r['id'] ?>" class="btn btn-primary flex-fill">
              <!-- زر أزرق لعرض تفاصيل الغرفة (flex-fill = يتمدد لملء الفراغ) -->
                <i class="bi bi-eye"></i> التفاصيل
                <!-- أيقونة عين + نص "التفاصيل" -->
              </a>

              <?php if (isLoggedIn() && !isAdmin()): ?>
              <!-- فحص: إذا كان المستخدم مسجلاً وليس مديراً (عميل عادي) -->
                <a href="customer/book.php?room_id=<?= $r['id'] ?>" class="btn btn-success">
                <!-- زر أخضر للحجز المباشر مع تمرير رقم الغرفة -->
                  <i class="bi bi-calendar-check"></i> احجز
                  <!-- أيقونة تقويم + نص "احجز" -->
                </a>
              <?php endif; ?>
              <!-- إغلاق الشرط -->
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <!-- إغلاق حلقة التكرار -->
  </div>
<?php endif; ?>
<!-- إغلاق الشرط -->

<?php include 'includes/footer.php'; ?>
<!-- استدعاء ملف الفوتر (تذييل الصفحة: الحقوق، السكربتات) -->