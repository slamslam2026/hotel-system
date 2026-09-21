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
    // تخزين رسالة تحذير مؤقتة
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول
}

if (isAdmin()) {
// فحص: إذا كان المستخدم مديراً
    redirect('../admin/dashboard.php');
    // توجيهه للوحة المدير (لأن المدير لا يحجز كعميل)
}

$uid = $_SESSION['user_id'];
// تخزين معرف المستخدم الحالي من الجلسة

$errors = [];
// مصفوفة فارغة لتخزين رسائل الخطأ

$room_id = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
// جلب رقم الغرفة من:
// 1. الرابط GET (room_id) — عند اختيار غرفة
// 2. أو من POST (room_id) — عند إرسال النموذج
// 3. أو 0 إذا لم يوجد أي منهما
// (int) لتحويل القيمة إلى رقم صحيح للأمان

// ==========================================
// إذا لم يُحدد room_id → عرض كل الغرف المتاحة للاختيار
// ==========================================
if (!$room_id) {
// فحص: إذا لم يُحدَّد room_id (القيمة 0 = false)
    $availableRooms = $pdo->query(
        "SELECT r.*, rt.name AS type_name, rt.price_per_night, rt.capacity
         FROM rooms r
         JOIN room_types rt ON r.room_type_id = rt.id
         WHERE r.status = 'available'
         ORDER BY rt.price_per_night"
    )->fetchAll();
    // استعلام مباشر لجلب جميع الغرف المتاحة فقط
    // JOIN room_types = ربط الغرف بأنواعها
    // WHERE r.status = 'available' = الغرف المتاحة فقط
    // ORDER BY rt.price_per_night = ترتيب حسب السعر
    // fetchAll() = جلب جميع الصفوف كمصفوفة

    $pageTitle = 'حجز غرفة';
    // عنوان الصفحة

    $baseUrl = '../';
    // المسار الأساسي (نصعد مجلداً للأعلى)

    include __DIR__ . '/../includes/header.php';
    // استدعاء الهيدر
    ?>
    <!-- إغلاق وسم PHP للانتقال لـ HTML -->

    <h2 class="mb-4"><i class="bi bi-calendar-plus text-primary"></i> اختر غرفة للحجز</h2>
    <!-- العنوان الرئيسي مع أيقونة تقويم + -->

    <div class="row g-4">
    <!-- صف شبكي بمسافات -->

      <?php foreach ($availableRooms as $r): ?>
      <!-- حلقة تكرار على كل غرفة متاحة -->

        <div class="col-md-4">
        <!-- عمود بعرض الثلث -->

          <div class="card room-card h-100">
          <!-- بطاقة غرفة بارتفاع كامل -->

            <img src="https://picsum.photos/seed/room<?= $r['id'] ?>/400/250" class="card-img-top" alt="room">
            <!-- صورة عشوائية من picsum.photos:
                 - seed/room{id}: يجعل كل غرفة لها صورة ثابتة مختلفة
                 - 400/250: العرض والارتفاع -->

            <div class="card-body">
            <!-- جسم البطاقة -->

              <h5>غرفة <?= e($r['room_number']) ?></h5>
              <!-- رقم الغرفة -->

              <p class="text-muted mb-2"><?= e($r['type_name']) ?></p>
              <!-- اسم نوع الغرفة -->

              <p class="mb-2">
              <!-- فقرة المعلومات -->
                <i class="bi bi-layers"></i> الطابق <?= $r['floor'] ?>
                <!-- أيقونة طبقات + رقم الطابق -->
                &nbsp;•&nbsp;
                <!-- نقطة فاصلة -->
                <i class="bi bi-people"></i> حتى <?= $r['capacity'] ?> أشخاص
                <!-- أيقونة أشخاص + السعة -->
              </p>

              <p class="fs-5 text-primary mb-3">
              <!-- فقرة السعر -->
                <?= number_format($r['price_per_night'], 2) ?> $ / الليلة
                <!-- السعر الليلي بتنسيق رقمي -->
              </p>

              <a href="book.php?room_id=<?= $r['id'] ?>" class="btn btn-primary w-100">
              <!-- زر لاختيار هذه الغرفة (يمرر room_id في الرابط) -->
                <i class="bi bi-calendar-check"></i> احجز هذه الغرفة
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (!$availableRooms): ?>
      <!-- فحص: إذا لم توجد غرف متاحة -->
        <div class="col-12">
        <!-- عمود بعرض كامل -->
          <div class="alert alert-info text-center">
          <!-- تنبيه أزرق بوسط النص -->
            <i class="bi bi-info-circle"></i> لا توجد غرف متاحة حالياً.
            <!-- رسالة توضيحية -->
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php
    include __DIR__ . '/../includes/footer.php';
    // استدعاء الفوتر

    exit;
    // إيقاف تنفيذ السكربت (لأننا عرضنا قائمة الغرف وانتهينا)
}
// إغلاق شرط عرض قائمة الغرف

// ==========================================
// جلب بيانات الغرفة المحددة
// ==========================================
$stmt = $pdo->prepare(
    "SELECT r.*, rt.name AS type_name, rt.price_per_night, rt.capacity
     FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE r.id = ? AND r.status = 'available'"
);
// تحضير استعلام لجلب بيانات الغرفة المحددة
// WHERE r.id = ? AND r.status = 'available':
//   - الغرفة المطلوبة
//   - AND متاحة فقط (لا يمكن حجز غرفة مشغولة)
// Prepared Statement يحمي من SQL Injection

$stmt->execute([$room_id]);
// تنفيذ الاستعلام مع تمرير رقم الغرفة

$room = $stmt->fetch();
// جلب بيانات الغرفة (أو false إذا لم توجد)

if (!$room) {
// فحص: إذا لم توجد الغرفة أو غير متاحة
    setFlash('danger', 'الغرفة غير متاحة.');
    // رسالة خطأ
    redirect('book.php');
    // إعادة التوجيه لصفحة اختيار الغرف
}

// ==========================================
// معالجة النموذج
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// فحص: إذا تم إرسال النموذج بطريقة POST

    if (!verifyCsrf($_POST['csrf'] ?? '')) {
    // فحص رمز CSRF
        $errors[] = 'طلب غير صالح.';
        // إضافة خطأ
    }

    $check_in  = $_POST['check_in']  ?? '';
    // تاريخ الوصول
    $check_out = $_POST['check_out'] ?? '';
    // تاريخ المغادرة
    $guests    = (int)($_POST['guests'] ?? 1);
    // عدد الضيوف (الافتراضي 1)

    // بيانات الهوية الجديدة
    $id_number   = trim($_POST['id_number'] ?? '');
    // رقم الهوية (مع إزالة المسافات)
    $nationality = trim($_POST['nationality'] ?? '');
    // الجنسية
    $address     = trim($_POST['address'] ?? '');
    // العنوان (اختياري)
    $notes       = trim($_POST['notes'] ?? '');
    // ملاحظات (اختياري)

    // تحويل التواريخ إلى طابع زمني للمقارنة
    $check_in_ts  = strtotime($check_in);
    // timestamp تاريخ الوصول
    $check_out_ts = strtotime($check_out);
    // timestamp تاريخ المغادرة
    $today_ts     = strtotime(date('Y-m-d'));
    // timestamp تاريخ اليوم

    // التحقق من التواريخ
    if (!$check_in_ts || !$check_out_ts) {
    // فحص: إذا كان أحد التاريخين غير صالح (strtotime ترجع false)
        $errors[] = 'صيغة التاريخ غير صحيحة.';
    } else {
    // وإلا (التواريخ صالحة)
        if ($check_in_ts < $today_ts) {
        // فحص: إذا كان تاريخ الوصول في الماضي
            $errors[] = 'تاريخ الوصول لا يمكن أن يكون في الماضي.';
        }
        if ($check_out_ts <= $check_in_ts) {
        // فحص: إذا كان تاريخ المغادرة قبل أو يساوي تاريخ الوصول
            $errors[] = 'تاريخ المغادرة يجب أن يكون بعد تاريخ الوصول.';
        }
    }

    // التحقق من عدد الضيوف
    if ($guests < 1 || $guests > $room['capacity']) {
    // فحص: إذا كان عدد الضيوف أقل من 1 أو أكبر من السعة
        $errors[] = 'عدد الضيوف غير مناسب (الحد الأقصى ' . $room['capacity'] . ').';
    }

    // التحقق من بيانات الهوية
    if (empty($id_number)) {
    // فحص: إذا كان رقم الهوية فارغاً
        $errors[] = 'رقم الهوية / البطاقة الشخصية مطلوب.';
    }
    if (empty($nationality)) {
    // فحص: إذا كانت الجنسية فارغة
        $errors[] = 'الجنسية مطلوبة.';
    }

    // التحقق من عدم وجود حجز متعارض
    if (empty($errors)) {
    // فحص: إذا لم توجد أخطاء حتى الآن
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM bookings
             WHERE room_id = ? AND status IN ('pending', 'confirmed')
             AND (check_in < ? AND check_out > ?)"
        );
        // استعلام للبحث عن حجوزات متعارضة في نفس الفترة
        // WHERE room_id = ? → نفس الغرفة
        // AND status IN ('pending', 'confirmed') → الحجوزات النشطة فقط
        // AND (check_in < ? AND check_out > ?):
        //   → شرط التعارض الكلاسيكي:
        //   التاريخ الجديد يبدأ قبل نهاية حجز موجود
        //   والتاريخ الجديد ينتهي بعد بداية حجز موجود

        $stmt->execute([$room_id, $check_out, $check_in]);
        // تنفيذ الاستعلام مع تمرير:
        // - رقم الغرفة
        // - تاريخ المغادرة الجديد (للمقارنة مع check_in الموجود)
        // - تاريخ الوصول الجديد (للمقارنة مع check_out الموجود)

        if ($stmt->fetchColumn() > 0) {
        // فحص: إذا وُجد حجز متعارض
            $errors[] = 'الغرفة محجوزة في هذه الفترة. اختر تواريخ أخرى.';
        }
    }

    // إتمام الحجز
    if (empty($errors)) {
    // إذا لم توجد أخطاء → نحفظ الحجز
        $nights = (new DateTime($check_in))->diff(new DateTime($check_out))->days;
        // حساب عدد الليالي:
        // DateTime($check_in) = كائن تاريخ الوصول
        // ->diff(new DateTime($check_out)) = الفرق بين التاريخين
        // ->days = عدد الأيام

        $total  = $nights * $room['price_per_night'];
        // حساب المبلغ الإجمالي = عدد الليالي × السعر الليلي

        $stmt = $pdo->prepare(
            "INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, id_number, nationality, address, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // تحضير استعلام لإدخال حجز جديد
        // ملاحظة: الحالة الافتراضية 'pending' (من قاعدة البيانات)

        $stmt->execute([$uid, $room_id, $check_in, $check_out, $guests, $total, $id_number, $nationality, $address, $notes]);
        // تنفيذ الإدخال مع تمرير جميع القيم

        setFlash('success', "تم إرسال حجزك بنجاح! المبلغ الإجمالي: " . number_format($total, 2) . " $ — في انتظار التأكيد.");
        // رسالة نجاح مع المبلغ الإجمالي

        redirect('invoice.php?id=' . $pdo->lastInsertId());
        // توجيه لصفحة الفاتورة مع تمرير معرف الحجز الجديد
    }
}

$pageTitle = 'حجز غرفة ' . $room['room_number'];
// عنوان الصفحة

$baseUrl = '../';
// المسار الأساسي

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر
?>

<!-- إغلاق وسم PHP والانتقال لـ HTML -->

<div class="row justify-content-center">
<!-- صف شبكي مع توسيط المحتوى -->

  <div class="col-md-8">
  <!-- عمود بعرض 8/12 (نموذج متوسط) -->

    <div class="card shadow-sm">
    <!-- بطاقة مع ظل خفيف -->

      <div class="card-body p-4">
      <!-- جسم البطاقة بحشوة كبيرة -->

        <h3 class="mb-3">
        <!-- العنوان -->
          <i class="bi bi-calendar-check text-primary"></i>
          حجز غرفة <?= e($room['room_number']) ?>
          <!-- أيقونة تقويم + نص -->
        </h3>

        <p class="text-muted">
        <!-- فقرة المعلومات -->
          <?= e($room['type_name']) ?>
          <!-- اسم النوع -->
          &nbsp;•&nbsp;
          <!-- نقطة فاصلة -->
          الطابق <?= $room['floor'] ?>
          <!-- الطابق -->
          &nbsp;•&nbsp;
          <!-- نقطة فاصلة -->
          حتى <?= $room['capacity'] ?> أشخاص
          <!-- السعة -->
        </p>

        <p class="fs-4 text-primary mb-4">
        <!-- السعر الليلي بخط كبير -->
          <?= number_format($room['price_per_night'], 2) ?> $ / الليلة
        </p>

        <?php if ($errors): ?>
        <!-- فحص: إذا كانت هناك أخطاء -->
          <div class="alert alert-danger">
          <!-- تنبيه أحمر -->
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
              <!-- حلقة تكرار على كل خطأ -->
                <li><?= e($err) ?></li>
                <!-- عرض الخطأ -->
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
        <!-- نموذج POST بدون تحقق المتصفح -->

          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <!-- رمز CSRF -->

          <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
          <!-- معرف الغرفة (لإرساله مع النموذج) -->

          <div class="row">
          <!-- صف للتواريخ -->

            <div class="col-md-6 mb-3">
            <!-- العمود 1 -->
              <label class="form-label">تاريخ الوصول</label>
              <input type="date" name="check_in" class="form-control"
                     min="<?= date('Y-m-d') ?>" required
                     value="<?= e($_POST['check_in'] ?? '') ?>">
              <!-- حقل تاريخ الوصول:
                   - min: الحد الأدنى هو تاريخ اليوم (لا يمكن اختيار الماضي)
                   - value: إعادة ملء القيمة عند الخطأ -->
            </div>

            <div class="col-md-6 mb-3">
            <!-- العمود 2 -->
              <label class="form-label">تاريخ المغادرة</label>
              <input type="date" name="check_out" class="form-control"
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required
                     value="<?= e($_POST['check_out'] ?? '') ?>">
              <!-- حقل تاريخ المغادرة:
                   - min: غداً كحد أدنى -->
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">
              عدد الضيوف (الحد الأقصى <?= $room['capacity'] ?>)
            </label>
            <input type="number" name="guests" class="form-control"
                   min="1" max="<?= $room['capacity'] ?>" value="1" required>
            <!-- حقل عدد الضيوف (1 إلى السعة القصوى) -->
          </div>

          <hr class="my-4">
          <!-- خط فاصل -->

          <h5 class="mb-3 text-primary"><i class="bi bi-person-badge"></i> بيانات الهوية</h5>
          <!-- عنوان قسم بيانات الهوية -->

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">رقم الهوية / البطاقة الشخصية <span class="text-danger">*</span></label>
              <!-- تسمية مع نجمة حمراء (مطلوب) -->
              <input type="text" name="id_number" class="form-control"
                     placeholder="مثال: 1234567890" required
                     value="<?= e($_POST['id_number'] ?? '') ?>">
              <!-- حقل رقم الهوية -->
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">الجنسية <span class="text-danger">*</span></label>
              <input type="text" name="nationality" class="form-control"
                     placeholder="مثال: سعودي / يمني / مصري" required
                     value="<?= e($_POST['nationality'] ?? '') ?>">
              <!-- حقل الجنسية -->
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">العنوان (اختياري)</label>
            <input type="text" name="address" class="form-control"
                   placeholder="المدينة، الحي، الشارع"
                   value="<?= e($_POST['address'] ?? '') ?>">
            <!-- حقل العنوان (اختياري) -->
          </div>

          <div class="mb-3">
            <label class="form-label">ملاحظات إضافية (اختياري)</label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="أي ملاحظات تريد إضافتها..."
                      maxlength="500"><?= e($_POST['notes'] ?? '') ?></textarea>
            <!-- حقل ملاحظات (بحد أقصى 500 حرف) -->
          </div>

          <div class="d-flex gap-2">
          <!-- حاوية مرنة مع فجوة -->
            <button type="submit" class="btn btn-success flex-fill">
            <!-- زر أخضر (يتمدد) -->
              <i class="bi bi-check-circle"></i> تأكيد الحجز
            </button>
            <a href="book.php" class="btn btn-secondary">
            <!-- زر رمادي للرجوع -->
              <i class="bi bi-arrow-right"></i> رجوع
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<!-- استدعاء الفوتر -->