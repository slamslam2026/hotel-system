<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي (مجلد customer)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة (isLoggedIn، isAdmin، setFlash، redirect، e)

if (!isLoggedIn()) {
// فحص: إذا لم يكن المستخدم مسجلاً دخوله
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول
}
// ملاحظة: لا يوجد setFlash هنا — توجيه صامت

$uid = $_SESSION['user_id'];
// تخزين معرف المستخدم الحالي من الجلسة

$booking_id = (int)($_GET['id'] ?? 0);
// جلب رقم الحجز من الرابط (GET)
// (int) لتحويله لرقم صحيح للأمان
// ?? 0 = إذا لم يوجد، اجعله 0

// ==========================================
// جلب بيانات الحجز مع بيانات العميل من جدول bookings + اسم المدير من users
// ==========================================
$stmt = $pdo->prepare(
    "SELECT b.*, 
            COALESCE(b.customer_name, u.full_name) AS customer_full_name,
            COALESCE(b.customer_phone, u.phone) AS customer_phone,
            u.full_name AS admin_name,
            r.room_number, r.floor, 
            rt.name AS type_name, rt.price_per_night
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.id = ?"
);
// تحضير استعلام لجلب كل بيانات الفاتورة:

// b.* = جميع أعمدة جدول bookings
// COALESCE(b.customer_name, u.full_name) AS customer_full_name:
//   → إذا كان اسم العميل مخزناً في الحجز استخدمه، وإلا استخدم اسم المستخدم
//   COALESCE ترجع أول قيمة غير NULL
// COALESCE(b.customer_phone, u.phone) AS customer_phone → نفس المنطق للهاتف
// u.full_name AS admin_name → اسم المدير (المستخدم صاحب الحجز)
// r.room_number, r.floor → رقم الغرفة والطابق
// rt.name AS type_name → اسم نوع الغرفة
// rt.price_per_night → السعر الليلي

// FROM bookings b → من جدول الحجوزات
// LEFT JOIN users u ON b.user_id = u.id:
//   → ربط بجدول المستخدمين (LEFT لأنه قد يكون الحجز بدون مستخدم)
// JOIN rooms r ON b.room_id = r.id → ربط بجدول الغرف
// JOIN room_types rt ON r.room_type_id = rt.id → ربط بجدول أنواع الغرف
// WHERE b.id = ? → الحجز المطلوب فقط

$stmt->execute([$booking_id]);
// تنفيذ الاستعلام مع تمرير رقم الحجز

$booking = $stmt->fetch();
// جلب بيانات الحجز (أو false إذا لم يوجد)

if (!$booking || (!isAdmin() && $booking['user_id'] != $uid)) {
// فحص مزدوج:
// 1. إذا لم يوجد الحجز
// 2. أو إذا كان المستخدم ليس مديراً AND ليس صاحب الحجز
//    (حماية: لا يمكن رؤية فاتورة شخص آخر)
    setFlash('danger', 'الفاتورة غير موجودة.');
    // رسالة خطأ (عامة لأسباب أمنية)
    redirect('my-bookings.php');
    // إعادة التوجيه لصفحة الحجوزات
}

$nights = (new DateTime($booking['check_in']))->diff(new DateTime($booking['check_out']))->days;
// حساب عدد الليالي:
// DateTime($booking['check_in']) = كائن تاريخ الوصول
// ->diff(new DateTime($booking['check_out'])) = الفرق بين التاريخين
// ->days = عدد الأيام

$pageTitle = 'فاتورة الحجز #' . $booking['id'];
// عنوان الصفحة مع رقم الحجز

$baseUrl = '../';
// المسار الأساسي (نصعد مجلداً للأعلى)

include __DIR__ . '/../includes/header.php';
// استدعاء الهيدر
?>

<!-- إغلاق وسم PHP والانتقال لـ HTML -->

<div class="container my-5">
<!-- حاوية Bootstrap مع هامش رأسي كبير -->

  <div class="card shadow">
  <!-- بطاقة مع ظل -->

    <div class="card-header bg-primary text-white text-center">
    <!-- رأس البطاقة: أزرق + نص أبيض + وسط -->
      <h2><i class="bi bi-receipt"></i> فاتورة حجز</h2>
      <!-- العنوان الرئيسي مع أيقونة إيصال -->
      <p class="mb-0">رقم الفاتورة: #<?= $booking['id'] ?></p>
      <!-- رقم الفاتورة (معرف الحجز) -->
    </div>

    <div class="card-body p-4">
    <!-- جسم البطاقة بحشوة كبيرة -->

      <!-- ==========================================
           بيانات العميل + مسؤول الفاتورة
           ========================================== -->
      <div class="row mb-4">
      <!-- صف شبكي بهامش سفلي -->

        <div class="col-md-6">
        <!-- العمود 1: نصف العرض (بيانات العميل) -->
          <h5 class="text-primary">👤 بيانات العميل:</h5>
          <!-- عنوان مع إيموجي -->

          <p><strong>الاسم:</strong> <?= e($booking['customer_full_name'] ?? 'غير محدد') ?></p>
          <!-- الاسم (مع تأمينه، أو "غير محدد" إذا كان NULL) -->

          <p><strong>الهاتف:</strong> <?= e($booking['customer_phone'] ?? '—') ?></p>
          <!-- الهاتف (أو شرطة إذا لم يوجد) -->

          <p><strong>رقم الهوية:</strong> <?= e($booking['id_number'] ?? '—') ?></p>
          <!-- رقم الهوية -->

          <p><strong>الجنسية:</strong> <?= e($booking['nationality'] ?? '—') ?></p>
          <!-- الجنسية -->

          <p><strong>العنوان:</strong> <?= e($booking['address'] ?? '—') ?></p>
          <!-- العنوان -->
        </div>

        <div class="col-md-6 text-end">
        <!-- العمود 2: نصف العرض (تفاصيل الحجز) مع محاذاة لليمين -->
          <h5 class="text-primary">📋 تفاصيل الحجز:</h5>
          <!-- عنوان -->

          <p><strong>تاريخ الحجز:</strong> <?= $booking['created_at'] ?></p>
          <!-- تاريخ إنشاء الحجز -->

          <p><strong>الحالة:</strong> 
          <!-- تسمية الحالة -->
            <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : 'warning' ?>">
            <!-- شارة: خضراء إذا مؤكد، وإلا صفراء -->
              <?= $booking['status'] === 'confirmed' ? 'مؤكد' : ($booking['status'] === 'completed' ? 'مكتمل' : ($booking['status'] === 'cancelled' ? 'ملغى' : 'قيد الانتظار')) ?>
              <!-- نص الحالة (شروط متداخلة ثلاثية) -->
            </span>
          </p>

          <hr>
          <!-- خط فاصل -->

          <p class="mb-0"><strong>مسؤول الفاتورة:</strong></p>
          <!-- تسمية -->
          <p class="text-muted"><?= e($booking['admin_name'] ?? 'النظام') ?></p>
          <!-- اسم المدير (أو "النظام" إذا لم يوجد) -->
        </div>
      </div>

      <hr>
      <!-- خط فاصل -->

      <!-- ==========================================
           تفاصيل الغرفة
           ========================================== -->
      <h5 class="mb-3">🏨 تفاصيل الغرفة:</h5>
      <!-- عنوان القسم -->

      <table class="table table-bordered">
      <!-- جدول بحدود -->
        <thead class="table-light">
        <!-- رأس الجدول بخلفية فاتحة -->
          <tr>
            <th>الغرفة</th>
            <!-- رقم الغرفة -->
            <th>النوع</th>
            <!-- نوع الغرفة -->
            <th>الطابق</th>
            <!-- الطابق -->
            <th>السعر/الليلة</th>
            <!-- السعر الليلي -->
            <th>عدد الليالي</th>
            <!-- عدد الليالي -->
            <th>الإجمالي</th>
            <!-- المبلغ الإجمالي -->
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= e($booking['room_number']) ?></td>
            <!-- رقم الغرفة -->
            <td><?= e($booking['type_name']) ?></td>
            <!-- اسم النوع -->
            <td><?= $booking['floor'] ?></td>
            <!-- الطابق -->
            <td><?= number_format($booking['price_per_night'], 2) ?> $</td>
            <!-- السعر الليلي -->
            <td><?= $nights ?></td>
            <!-- عدد الليالي (محسوب مسبقاً) -->
            <td><?= number_format($booking['total_price'], 2) ?> $</td>
            <!-- الإجمالي -->
          </tr>
        </tbody>
        <tfoot>
        <!-- تذييل الجدول (للإجمالي النهائي) -->
          <tr>
            <th colspan="5" class="text-end">الإجمالي النهائي:</th>
            <!-- colspan=5 = يمتد على 5 أعمدة، محاذاة يمين -->
            <th class="text-primary"><?= number_format($booking['total_price'], 2) ?> $</th>
            <!-- المبلغ الإجمالي بلون أزرق -->
          </tr>
        </tfoot>
      </table>

      <!-- ==========================================
           تواريخ الوصول والمغادرة + الأزرار
           ========================================== -->
      <div class="row mt-4">
      <!-- صف بهامش علوي -->

        <div class="col-md-6">
        <!-- العمود 1 -->
          <p><strong>تاريخ الوصول:</strong> <?= $booking['check_in'] ?></p>
          <!-- تاريخ الوصول -->
          <p><strong>تاريخ المغادرة:</strong> <?= $booking['check_out'] ?></p>
          <!-- تاريخ المغادرة -->
          <p><strong>عدد الضيوف:</strong> <?= $booking['guests'] ?></p>
          <!-- عدد الضيوف -->
        </div>

        <div class="col-md-6 text-end">
        <!-- العمود 2 مع محاذاة يمين -->
          <button onclick="window.print()" class="btn btn-primary">
          <!-- زر الطباعة:
               - onclick="window.print()" → يفتح نافذة الطباعة -->
            <i class="bi bi-printer"></i> طباعة الفاتورة
            <!-- أيقونة طابعة + نص -->
          </button>

          <a href="my-bookings.php" class="btn btn-secondary">
          <!-- زر رمادي للرجوع -->
            <i class="bi bi-arrow-right"></i> رجوع لحجوزاتي
          </a>
        </div>
      </div>
    </div>

    <div class="card-footer text-center text-muted">
    <!-- تذييل البطاقة -->
      <small>شكراً لاختيارك فندقنا. نتمنى لك إقامة سعيدة!</small>
      <!-- رسالة شكر (خط صغير) -->
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<!-- استدعاء الفوتر -->