<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/../config/db.php';
// استدعاء ملف الاتصال بقاعدة البيانات
// __DIR__ = المسار الحالي (مجلد admin)
// '/../' = اصعد مجلداً للأعلى ثم ادخل مجلد config

require_once __DIR__ . '/../includes/functions.php';
// استدعاء ملف الدوال المساعدة من مجلد includes

if (!isAdmin()) {
// فحص: إذا لم يكن المستخدم مديراً
    setFlash('danger', 'الرجاء تسجيل الدخول');
    // تخزين رسالة خطأ مؤقتة
    redirect('../login.php');
    // توجيهه لصفحة تسجيل الدخول
}

$errors = [];
// مصفوفة فارغة لتخزين رسائل الخطأ

// ================== جلب جميع الغرف (بدون فلترة) ==================
$rooms = $pdo->query(
    "SELECT r.id, r.room_number, r.floor, r.status,
            rt.name AS type_name, rt.price_per_night, rt.capacity
     FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id
     ORDER BY r.room_number ASC"
)->fetchAll(PDO::FETCH_ASSOC);
// استعلام مباشر لجلب جميع الغرف (بدون فلترة)
// r.id, r.room_number, r.floor, r.status → من جدول rooms
// rt.name AS type_name → اسم نوع الغرفة
// rt.price_per_night → السعر الليلي
// rt.capacity → السعة
// JOIN room_types → ربط الغرف بأنواعها
// ORDER BY r.room_number ASC → ترتيب تصاعدي حسب رقم الغرفة
// fetchAll(PDO::FETCH_ASSOC) → جلب جميع الصفوف كمصفوفة ترابطية
// نستخدمها لملء قائمة الاختيار (select) في النموذج

// ================== معالجة الطلب ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// فحص: إذا تم إرسال النموذج بطريقة POST

    if (!verifyCsrf($_POST['csrf'] ?? '')) $errors[] = 'خطأ في التحقق';
    // فحص رمز CSRF، وإذا فشل → إضافة خطأ للمصفوفة

    // ========== جلب بيانات العميل ==========
    $customer_name  = trim($_POST['customer_name'] ?? '');
    // اسم العميل (مع إزالة المسافات)
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    // رقم الهاتف
    $id_number      = trim($_POST['id_number'] ?? '');
    // رقم الهوية
    $nationality    = trim($_POST['nationality'] ?? '');
    // الجنسية
    $address        = trim($_POST['address'] ?? '');
    // العنوان (اختياري)
    $notes          = trim($_POST['notes'] ?? '');
    // ملاحظات (اختياري)

    // ========== جلب تفاصيل الحجز ==========
    $room_id        = (int)($_POST['room_id'] ?? 0);
    // رقم الغرفة (تحويل لرقم صحيح)
    $check_in       = trim($_POST['check_in'] ?? '');
    // تاريخ الوصول
    $check_in_time  = trim($_POST['check_in_time'] ?? '');
    // وقت الوصول
    $check_in_ampm  = $_POST['check_in_ampm'] ?? 'AM';
    // فترة الوصول (صباحاً/مساءً) — الافتراضي AM
    $check_out      = trim($_POST['check_out'] ?? '');
    // تاريخ المغادرة
    $check_out_time = trim($_POST['check_out_time'] ?? '');
    // وقت المغادرة
    $check_out_ampm = $_POST['check_out_ampm'] ?? 'AM';
    // فترة المغادرة
    $guests         = (int)($_POST['guests'] ?? 1);
    // عدد الضيوف (الافتراضي 1)

    // ========== التحقق من المدخلات ==========
    if (empty($customer_name)) $errors[] = 'الاسم مطلوب';
    // فحص: إذا كان الاسم فارغاً
    if (empty($customer_phone)) $errors[] = 'رقم الهاتف مطلوب';
    if (empty($id_number)) $errors[] = 'رقم الهوية مطلوب';
    if (empty($nationality)) $errors[] = 'الجنسية مطلوبة';
    if ($room_id <= 0) $errors[] = 'الغرفة مطلوبة';
    // فحص: إذا لم تُختَر غرفة (القيمة 0 أو أقل)
    if (empty($check_in)) $errors[] = 'تاريخ الوصول مطلوب';
    if (empty($check_out)) $errors[] = 'تاريخ المغادرة مطلوب';
    if (empty($check_in_time)) $errors[] = 'وقت الوصول مطلوب';
    if (empty($check_out_time)) $errors[] = 'وقت المغادرة مطلوب';

    // ========== التحقق من التواريخ ==========
    if (empty($errors)) {
    // فحص: إذا لم توجد أخطاء حتى الآن
        $check_in_ts  = strtotime($check_in);
        // تحويل تاريخ الوصول إلى timestamp (طابع زمني)
        $check_out_ts = strtotime($check_out);
        // تحويل تاريخ المغادرة إلى timestamp
        $today_ts     = strtotime(date('Y-m-d'));
        // تحويل تاريخ اليوم إلى timestamp

        if ($check_in_ts < $today_ts) {
        // فحص: إذا كان تاريخ الوصول في الماضي
            $errors[] = 'تاريخ الوصول لا يمكن أن يكون في الماضي';
        } else if ($check_out_ts <= $check_in_ts) {
        // وإلا: إذا كان تاريخ المغادرة قبل أو يساوي تاريخ الوصول
            $errors[] = 'تاريخ المغادرة يجب أن يكون بعد تاريخ الوصول';
        }
    }

    // ========== حفظ الحجز ==========
    if (empty($errors)) {
    // فحص: إذا لم توجد أخطاء → نحفظ الحجز
        try {
        // بداية كتلة try/catch للتعامل مع الاستثناءات
            $pdo->beginTransaction();
            // بدء معاملة (Transaction) — إما أن تنجح كل العمليات أو تفشل كلها

            $nights = (new DateTime($check_in))->diff(new DateTime($check_out))->days;
            // حساب عدد الليالي:
            // DateTime($check_in) → كائن تاريخ الوصول
            // ->diff(new DateTime($check_out)) → الفرق بين التاريخين
            // ->days → عدد الأيام

            $stmt = $pdo->prepare(
                "SELECT rt.price_per_night 
                 FROM rooms r 
                 JOIN room_types rt ON r.room_type_id = rt.id 
                 WHERE r.id = ?"
            );
            // تحضير استعلام لجلب سعر الليلة للغرفة المختارة
            $stmt->execute([$room_id]);
            // تنفيذ الاستعلام مع تمرير رقم الغرفة
            $price = $stmt->fetchColumn();
            // جلب سعر الليلة (قيمة واحدة)

            if (!$price) throw new Exception('لم يتم العثور على سعر الغرفة');
            // فحص: إذا لم يوجد سعر → إطلاق استثناء (يوقف التنفيذ)

            $total_price = $nights * $price;
            // حساب المبلغ الإجمالي = عدد الليالي × سعر الليلة

            $stmt = $pdo->prepare(
                "INSERT INTO bookings 
                 (user_id, customer_name, customer_phone, id_number, nationality, address, notes, room_id, 
                  check_in, check_out, guests, total_price, status, created_at)
                 VALUES 
                 (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())"
            );
            // تحضير استعلام لإدخال حجز جديد في جدول bookings
            // ملاحظة: الحالة 'confirmed' مباشرة (لأن المدير هو من يحجز)
            // NOW() → التاريخ والوقت الحالي

            $stmt->execute([
                $_SESSION['user_id'],
                // معرف المستخدم (المدير) من الجلسة
                $customer_name,
                $customer_phone,
                $id_number,
                $nationality,
                $address,
                $notes,
                $room_id,
                $check_in,
                $check_out,
                $guests,
                $total_price
            ]);
            // تنفيذ الإدخال مع تمرير جميع القيم

            $booking_id = $pdo->lastInsertId();
            // جلب معرف الحجز الجديد (المُولَّد تلقائياً)

            $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            // تحضير استعلام لتحديث حالة الغرفة إلى "مشغولة"
            $stmt->execute([$room_id]);
            // تنفيذ التحديث

            $pdo->commit();
            // تأكيد المعاملة (حفظ كل التغييرات نهائياً)

            setFlash('success', "تم حفظ الحجز بنجاح! رقم الحجز: #$booking_id — المبلغ الإجمالي: " . number_format($total_price, 2) . " $");
            // رسالة نجاح مع رقم الحجز والمبلغ
            redirect('bookings.php');
            // توجيه لصفحة الحجوزات
            exit;
            // إيقاف التنفيذ

        } catch (Exception $e) {
        // في حالة حدوث خطأ
            $pdo->rollBack();
            // التراجع عن كل التغييرات في المعاملة
            $errors[] = 'خطأ في حفظ الحجز: ' . $e->getMessage();
            // إضافة رسالة الخطأ
        }
    }
}
?>

<!-- ============================================ -->
<!-- بداية HTML -->
<!-- ============================================ -->

<!DOCTYPE html>
<!-- تعريف نوع المستند HTML5 -->
<html lang="ar" dir="rtl">
<!-- وسم html باللغة العربية واتجاه من اليمين لليسار -->

<head>
<!-- بداية رأس الصفحة -->
    <meta charset="UTF-8">
    <!-- ترميز الأحرف UTF-8 (يدعم العربية) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- إعدادات العرض للجوال -->
    <title>حجز جديد - نظام الفندق</title>
    <!-- عنوان الصفحة -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- استدعاء Bootstrap RTL (يدعم العربية) من CDN -->

    <style>
    /* بداية أنماط CSS المخصصة */
        body { font-family: 'Tajawal', sans-serif; background-color: #f4f6f9; }
        /* الخط Tajawal + خلفية فاتحة */
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        /* بطاقات بدون حدود + زوايا دائرية + ظل خفيف */
        .card-header { background: linear-gradient(135deg, #198754, #20c997); color: white; border-radius: 15px 15px 0 0 !important; }
        /* رأس البطاقة بتدرج أخضر + نص أبيض + زوايا علوية دائرية */
        .form-label { font-weight: bold; color: #333; }
        /* تسميات الحقول بخط عريض ولون داكن */
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; }
        /* حقول الإدخال والقوائم بزوايا دائرية وحشوة مريحة */
        .form-control:focus, .form-select:focus { border-color: #198754; box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25); }
        /* تأثير التركيز: حدود خضراء + ظل أخضر خفيف */
        .btn-success { background: linear-gradient(135deg, #198754, #20c997); border: none; border-radius: 10px; padding: 12px; font-weight: bold; }
        /* زر النجاح بتدرج أخضر */
        .btn-success:hover { background: linear-gradient(135deg, #146c43, #1aa179); }
        /* تأثير المرور على الزر */
        .total-box { background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 10px; padding: 15px; color: #1b5e20; font-weight: bold; }
        /* صندوق المبلغ الإجمالي بخلفية خضراء فاتحة */

        .ampm-select {
        /* قائمة AM/PM المخصصة */
            background-color: #198754;
            /* خلفية خضراء */
            color: white;
            /* نص أبيض */
            font-weight: bold;
            /* خط عريض */
            border: none;
            /* بدون حدود */
            border-radius: 10px;
            /* زوايا دائرية */
            text-align: center;
            /* وسط النص */
            cursor: pointer;
            /* مؤشر يد عند المرور */
        }
        .ampm-select:focus {
        /* تأثير التركيز على قائمة AM/PM */
            background-color: #146c43;
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        .ampm-select option {
        /* خيارات قائمة AM/PM */
            background-color: white;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body>
<!-- بداية جسم الصفحة -->

<div class="container py-5">
<!-- حاوية Bootstrap مع حشوة رأسية كبيرة -->
    <div class="row justify-content-center">
    <!-- صف شبكي مع توسيط المحتوى -->
        <div class="col-lg-10">
        <!-- عمود بعرض 10/12 من الشاشات الكبيرة -->

            <?php if (!empty($errors)): ?>
            <!-- فحص: إذا كانت هناك أخطاء -->
                <div class="alert alert-danger">
                <!-- صندوق تنبيه أحمر -->
                    <ul class="mb-0">
                    <!-- قائمة غير مرتبة -->
                        <?php foreach ($errors as $error): ?>
                        <!-- حلقة تكرار على كل خطأ -->
                            <li><?= htmlspecialchars($error) ?></li>
                            <!-- عرض الخطأ مع تأمينه ضد XSS -->
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
            <!-- نموذج يُرسل بطريقة POST لنفس الصفحة -->
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <!-- حقل مخفي لرمز CSRF -->

                <!-- ============ بيانات العميل ============ -->
                <div class="card mb-4">
                <!-- بطاقة بهامش سفلي -->
                    <div class="card-header py-3">
                    <!-- رأس البطاقة -->
                        <h5 class="mb-0">📋 بيانات العميل</h5>
                        <!-- عنوان مع أيقونة -->
                    </div>
                    <div class="card-body p-4">
                    <!-- جسم البطاقة بحشوة كبيرة -->
                        <div class="row g-3">
                        <!-- صف شبكي بمسافات -->

                            <div class="col-md-6">
                            <!-- العمود 1: نصف العرض -->
                                <label class="form-label">اسم العميل *</label>
                                <!-- تسمية الحقل (النجمة = مطلوب) -->
                                <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>" required>
                                <!-- حقل الاسم (مع إعادة ملء القيمة السابقة) -->
                            </div>

                            <div class="col-md-6">
                            <!-- العمود 2 -->
                                <label class="form-label">رقم الهاتف *</label>
                                <input type="text" name="customer_phone" class="form-control" value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>" required>
                                <!-- حقل رقم الهاتف -->
                            </div>

                            <div class="col-md-6">
                            <!-- العمود 3 -->
                                <label class="form-label">رقم الهوية *</label>
                                <input type="text" name="id_number" class="form-control" value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>" required>
                                <!-- حقل رقم الهوية -->
                            </div>

                            <div class="col-md-6">
                            <!-- العمود 4 -->
                                <label class="form-label">الجنسية *</label>
                                <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($_POST['nationality'] ?? '') ?>" required>
                                <!-- حقل الجنسية -->
                            </div>

                            <div class="col-12">
                            <!-- العمود الكامل -->
                                <label class="form-label">العنوان</label>
                                <!-- العنوان (اختياري) -->
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                                <!-- حقل العنوان -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ تفاصيل الحجز ============ -->
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0">📅 تفاصيل الحجز</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            
                            <div class="col-md-4">
                            <!-- عمود بثلث العرض -->
                                <label class="form-label">الغرفة *</label>
                                <select name="room_id" id="room_id" class="form-select" required>
                                <!-- قائمة اختيار الغرفة -->
                                    <option value="">اختر الغرفة</option>
                                    <!-- خيار افتراضي فارغ -->
                                    <?php if (!empty($rooms)): ?>
                                    <!-- فحص: إذا كانت هناك غرف -->
                                        <?php foreach ($rooms as $room): ?>
                                        <!-- حلقة تكرار على كل غرفة -->
                                            <option value="<?= $room['id'] ?>" data-price="<?= $room['price_per_night'] ?>">
                                            <!-- خيار الغرفة:
                                                 - value: معرف الغرفة
                                                 - data-price: السعر (للحساب بـ JavaScript) -->
                                                غرفة <?= htmlspecialchars($room['room_number']) ?> 
                                                - <?= htmlspecialchars($room['type_name']) ?> 
                                                - <?= number_format($room['price_per_night'], 2) ?> $/الليلة
                                                <!-- نص الخيار: رقم الغرفة - النوع - السعر -->
                                                <?= $room['status'] === 'occupied' ? ' (مشغولة)' : '' ?>
                                                <!-- إضافة "(مشغولة)" إذا كانت الغرفة مشغولة -->
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <!-- وإلا (لا توجد غرف) -->
                                        <option value="" disabled>لا توجد غرف في قاعدة البيانات</option>
                                        <!-- خيار معطّل -->
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                            <!-- عمود بثلث العرض -->
                                <label class="form-label">عدد الضيوف</label>
                                <input type="number" name="guests" class="form-control" value="<?= htmlspecialchars($_POST['guests'] ?? 1) ?>" min="1">
                                <!-- حقل عدد الضيوف (الحد الأدنى 1) -->
                            </div>

                            <div class="col-md-4">
                            <!-- عمود بثلث العرض -->
                                <label class="form-label">عدد الليالي</label>
                                <input type="text" id="nights" class="form-control" readonly placeholder="يتم الحساب تلقائياً">
                                <!-- حقل للعرض فقط (readonly) يُحسب بـ JavaScript -->
                            </div>

                            <!-- ===== تاريخ ووقت الوصول ===== -->
                            <div class="col-md-4">
                            <!-- عمود بثلث العرض -->
                                <label class="form-label">📆 تاريخ الوصول *</label>
                                <input type="date" id="checkin" name="check_in" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>" required>
                                <!-- حقل تاريخ الوصول -->
                            </div>

                            <div class="col-md-2">
                            <!-- عمود بسدس العرض -->
                                <label class="form-label">🕐 وقت الوصول *</label>
                                <input type="time" id="checkin_time" name="check_in_time" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['check_in_time'] ?? '') ?>" required>
                                <!-- حقل وقت الوصول -->
                            </div>

                            <div class="col-md-2">
                            <!-- عمود بسدس العرض -->
                                <label class="form-label">🌅 الفترة *</label>
                                <select name="check_in_ampm" class="form-select ampm-select" required>
                                <!-- قائمة AM/PM -->
                                    <option value="AM" <?= (($_POST['check_in_ampm'] ?? 'AM') === 'AM') ? 'selected' : '' ?>>صباحاً AM</option>
                                    <!-- خيار AM (محدد افتراضياً) -->
                                    <option value="PM" <?= (($_POST['check_in_ampm'] ?? '') === 'PM') ? 'selected' : '' ?>>مساءً PM</option>
                                    <!-- خيار PM -->
                                </select>
                            </div>

                            <!-- ===== تاريخ ووقت المغادرة ===== -->
                            <div class="col-md-4">
                                <label class="form-label">📆 تاريخ المغادرة *</label>
                                <input type="date" id="checkout" name="check_out" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>" required>
                                <!-- حقل تاريخ المغادرة -->
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">🕐 وقت المغادرة *</label>
                                <input type="time" id="checkout_time" name="check_out_time" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['check_out_time'] ?? '') ?>" required>
                                <!-- حقل وقت المغادرة -->
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">🌅 الفترة *</label>
                                <select name="check_out_ampm" class="form-select ampm-select" required>
                                    <option value="AM" <?= (($_POST['check_out_ampm'] ?? 'AM') === 'AM') ? 'selected' : '' ?>>صباحاً AM</option>
                                    <option value="PM" <?= (($_POST['check_out_ampm'] ?? '') === 'PM') ? 'selected' : '' ?>>مساءً PM</option>
                                </select>
                            </div>
                        </div>

                        <div class="total-box mt-4 d-flex justify-content-between align-items-center">
                        <!-- صندوق المبلغ الإجمالي -->
                            <span>💰 المبلغ الإجمالي:</span>
                            <!-- نص "المبلغ الإجمالي" -->
                            <span id="total-price">0.00 $</span>
                            <!-- القيمة (تُحدَّث بـ JavaScript) -->
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                <!-- بطاقة الملاحظات -->
                    <div class="card-body p-4">
                        <label class="form-label">ملاحظات (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        <!-- حقل نصي متعدد الأسطر للملاحظات -->
                    </div>
                </div>

                <div class="d-flex gap-3">
                <!-- حاوية مرنة مع فجوة بين الأزرار -->
                    <button type="submit" class="btn btn-success flex-grow-1">
                    <!-- زر الإرسال (يتمدد لملء المساحة) -->
                        ✅ تأكيد الحجز وإصدار الفاتورة
                    </button>
                    <a href="bookings.php" class="btn btn-secondary px-4">إلغاء</a>
                    <!-- زر الإلغاء (يعود لصفحة الحجوزات) -->
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// بداية سكربت JavaScript
document.addEventListener('DOMContentLoaded', function() {
// انتظر حتى يتم تحميل الصفحة بالكامل

    const checkinInput   = document.getElementById('checkin');
    // حقل تاريخ الوصول
    const checkoutInput  = document.getElementById('checkout');
    // حقل تاريخ المغادرة
    const nightsInput    = document.getElementById('nights');
    // حقل عدد الليالي
    const totalPriceSpan = document.getElementById('total-price');
    // عنصر عرض المبلغ الإجمالي
    const roomSelect     = document.getElementById('room_id');
    // قائمة اختيار الغرفة

    let pricePerNight = 0;
    // متغير لتخزين سعر الليلة

    roomSelect.addEventListener('change', function() {
    // عند تغيير الغرفة المختارة
        const selectedOption = this.options[this.selectedIndex];
        // الخيار المحدد حالياً
        const price = selectedOption.getAttribute('data-price');
        // قراءة سعر الليلة من خاصية data-price
        if (price) {
            pricePerNight = parseFloat(price);
            // تحويل السعر لرقم عشري
            calculateNights();
            // إعادة حساب عدد الليالي والمبلغ
        }
    });

    checkinInput.addEventListener('change', calculateNights);
    // عند تغيير تاريخ الوصول → أعد الحساب
    checkoutInput.addEventListener('change', calculateNights);
    // عند تغيير تاريخ المغادرة → أعد الحساب

    function calculateNights() {
    // دالة حساب عدد الليالي والمبلغ الإجمالي
        const checkinVal  = checkinInput.value;
        // قيمة تاريخ الوصول
        const checkoutVal = checkoutInput.value;
        // قيمة تاريخ المغادرة

        if (checkinVal && checkoutVal) {
        // إذا كان كلا التاريخين مُدخلين
            const date1 = new Date(checkinVal);
            // كائن تاريخ الوصول
            const date2 = new Date(checkoutVal);
            // كائن تاريخ المغادرة

            if (!isNaN(date1) && !isNaN(date2)) {
            // فحص: إذا كان كلا التاريخين صالحين
                const diffDays = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
                // حساب الفرق بالأيام:
                // (date2 - date1) = الفرق بالميلي ثانية
                // / (1000 * 60 * 60 * 24) = التحويل لأيام
                // Math.ceil = التقريب للأعلى

                if (diffDays > 0) {
                // إذا كان عدد الأيام موجباً
                    nightsInput.value = diffDays;
                    // عرض عدد الليالي
                    if (pricePerNight > 0) {
                    // إذا كان السعر معروفاً
                        totalPriceSpan.textContent = (diffDays * pricePerNight).toFixed(2) + ' $';
                        // عرض المبلغ الإجمالي (بتنسيق خانتين عشريتين)
                    }
                } else {
                // وإلا (تاريخ غير صالح)
                    nightsInput.value = 0;
                    totalPriceSpan.textContent = '0.00 $';
                }
            }
        } else {
        // إذا لم يُدخل أحد التاريخين
            nightsInput.value = '';
            totalPriceSpan.textContent = '0.00 $';
        }
    }
});
</script>

</body>
</html>