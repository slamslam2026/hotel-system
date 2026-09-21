<?php
// فتح وسم PHP لبدء كتابة الكود

require_once __DIR__ . '/functions.php';
// استدعاء ملف الدوال المساعدة
// __DIR__ = المسار الحالي (مجلد includes)
// '/functions.php' = الملف في نفس المجلد
// ملاحظة: functions.php تحتوي على session_start() و requireLogin()
// أي أن الحماية تُفعَّل تلقائياً هنا
?>
<!-- إغلاق وسم PHP للانتقال لـ HTML -->

<!DOCTYPE html>
<!-- تعريف نوع المستند HTML5 -->

<html lang="ar" dir="rtl">
<!-- وسم html:
     - lang="ar" : اللغة عربية (مهم لمحركات البحث وقارئات الشاشة)
     - dir="rtl" : الاتجاه من اليمين لليسار -->

<head>
<!-- بداية رأس الصفحة (المعلومات الوصفية) -->

  <meta charset="UTF-8">
  <!-- ترميز الأحرف UTF-8 (يدعم العربية والإيموجي) -->

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- إعدادات العرض للجوال:
       - width=device-width : العرض = عرض الجهاز
       - initial-scale=1 : مقياس التكبير الابتدائي
       الفائدة: تصميم متجاوب (Responsive) -->

  <title><?= $pageTitle ?? 'فندقنا' ?> — فندقنا</title>
  <!-- عنوان الصفحة:
       - $pageTitle ?? 'فندقنا' : إذا لم يكن $pageTitle معرَّفاً → "فندقنا"
       - — فندقنا : إضافة اسم الموقع دائماً
       مثال: "الغرف المتاحة — فندقنا" -->

  <!-- Bootstrap 5 RTL -->
  <!-- تعليق: Bootstrap 5 بنسخة RTL (يمين لليسار) -->

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <!-- استدعاء Bootstrap 5.3.2 RTL من CDN:
       - bootstrap.rtl.min.css : نسخة RTL مضغوطة
       - rtl تعني: right-to-left (مناسب للعربية) -->

  <!-- Bootstrap Icons -->
  <!-- تعليق: أيقونات Bootstrap -->

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- استدعاء أيقونات Bootstrap من CDN
       الفائدة: استخدام <i class="bi bi-building"></i> في كل الصفحات -->

  <!-- خط Tajawal -->
  <!-- تعليق: خط عربي عصري -->

  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- استدعاء خط Tajawal من Google Fonts:
       - wght@400;500;700 : 3 أوزان (عادي، متوسط، عريض)
       - display=swap : يعرض نصاً بديلاً حتى يُحمَّل الخط -->

  <!-- تصميمنا -->
  <!-- تعليق: ملف CSS الخاص بالمشروع -->

  <link href="<?= $baseUrl ?? '' ?>assets/css/style.css" rel="stylesheet">
  <!-- استدعاء ملف CSS الخاص:
       - $baseUrl ?? '' : المسار الديناميكي
       - من الجذر: assets/css/style.css
       - من admin/customer: ../assets/css/style.css -->

</head>
<!-- إغلاق رأس الصفحة -->

<body>
<!-- بداية جسم الصفحة (المحتوى المرئي) -->

<!-- ==========================================
     شريط التنقل (Navbar)
     ========================================== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
<!-- شريط التنقل:
     - navbar-expand-lg : يتوسع على الشاشات الكبيرة، ويصبح قائمة على الصغيرة
     - navbar-dark : نص فاتح على خلفية داكنة
     - bg-dark : خلفية داكنة
     - shadow-sm : ظل خفيف -->

  <div class="container">
  <!-- حاوية Bootstrap: توسيط المحتوى -->

    <a class="navbar-brand fw-bold" href="<?= $baseUrl ?? '' ?>index.php">
    <!-- شعار الموقع (رابط للرئيسية):
         - navbar-brand : كلاس شعار Bootstrap
         - fw-bold : خط عريض -->
      <i class="bi bi-building"></i> فندقنا
      <!-- أيقونة مبنى + اسم الموقع -->
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
    <!-- زر القائمة (يظهر على الجوال):
         - navbar-toggler : زر الطي
         - data-bs-toggle="collapse" : يفعّل الطي
         - data-bs-target="#mainNav" : يستهدف العنصر بالمعرف mainNav -->
      <span class="navbar-toggler-icon"></span>
      <!-- أيقونة القائمة (3 خطوط) -->
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
    <!-- حاوية القائمة القابلة للطي:
         - collapse : قابلة للطي
         - navbar-collapse : تحويلها لقائمة على الجوال
         - id="mainNav" : المعرف المستهدف -->

      <ul class="navbar-nav ms-auto">
      <!-- قائمة الروابط:
           - navbar-nav : قائمة تنقل
           - ms-auto : هامش يمين تلقائي (يدفع القائمة لليمين في RTL = لليسار بصرياً) -->

        <li class="nav-item">
        <!-- عنصر قائمة -->
          <a class="nav-link" href="<?= $baseUrl ?? '' ?>index.php">الرئيسية</a>
          <!-- رابط الرئيسية -->
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= $baseUrl ?? '' ?>rooms.php">الغرف</a>
          <!-- رابط صفحة الغرف -->
        </li>

        <?php if (isLoggedIn()): ?>
        <!-- فحص: إذا كان المستخدم مسجلاً دخوله -->

          <?php if (isAdmin()): ?>
          <!-- فحص: إذا كان المستخدم مديراً -->

            <!-- لوحة التحكم -->
            <!-- تعليق توضيحي -->
            <li class="nav-item">
              <a class="nav-link" href="<?= $baseUrl ?? '' ?>admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> لوحة التحكم
                <!-- أيقونة عداد + نص -->
              </a>
            </li>

            <!-- إدارة الغرف -->
            <li class="nav-item">
              <a class="nav-link" href="<?= $baseUrl ?? '' ?>admin/rooms.php">
                <i class="bi bi-door-open"></i> إدارة الغرف
                <!-- أيقونة باب مفتوح + نص -->
              </a>
            </li>

            <!-- الحجوزات -->
            <li class="nav-item">
              <a class="nav-link" href="<?= $baseUrl ?? '' ?>admin/bookings.php">
                <i class="bi bi-calendar-check"></i> الحجوزات
                <!-- أيقونة تقويم + نص -->
              </a>
            </li>

            <!-- المستخدمون -->
            <li class="nav-item">
              <a class="nav-link" href="<?= $baseUrl ?? '' ?>admin/users.php">
                <i class="bi bi-people"></i> المستخدمون
                <!-- أيقونة أشخاص + نص -->
              </a>
            </li>

            <!-- الأنواع -->
            <li class="nav-item">
              <a class="nav-link" href="<?= $baseUrl ?? '' ?>admin/room-types.php">
                <i class="bi bi-tags"></i> الأنواع
                <!-- أيقونة وسوم + نص -->
              </a>
            </li>

          <?php else: ?>
          <!-- وإلا (عميل عادي) → اعرض رابط لوحة العميل -->

            <li class="nav-item">
              <a class="nav-link" href="<?= $baseUrl ?? '' ?>customer/dashboard.php">
                <i class="bi bi-person"></i> حسابي
                <!-- أيقونة شخص + نص -->
              </a>
            </li>

          <?php endif; ?>
          <!-- إغلاق شرط المدير/العميل -->

          <li class="nav-item">
            <a class="nav-link text-warning" href="<?= $baseUrl ?? '' ?>logout.php">
            <!-- رابط تسجيل الخروج:
                 - text-warning : لون أصفر (تمييز) -->
              <i class="bi bi-box-arrow-right"></i> خروج
              <!-- أيقونة خروج + نص -->
            </a>
          </li>

        <?php else: ?>
        <!-- وإلا (المستخدم غير مسجل) → اعرض روابط الدخول والتسجيل -->

          <li class="nav-item">
            <a class="nav-link" href="<?= $baseUrl ?? '' ?>login.php">دخول</a>
            <!-- رابط تسجيل الدخول -->
          </li>

          <li class="nav-item">
            <a class="btn btn-warning ms-2" href="<?= $baseUrl ?? '' ?>register.php">
            <!-- زر أصفر لتسجيل جديد:
                 - btn-warning : لون أصفر
                 - ms-2 : هامش يمين -->
              تسجيل جديد
            </a>
          </li>

        <?php endif; ?>
        <!-- إغلاق شرط تسجيل الدخول -->

      </ul>
      <!-- إغلاق القائمة -->
    </div>
    <!-- إغلاق الحاوية القابلة للطي -->
  </div>
  <!-- إغلاق الحاوية -->
</nav>
<!-- إغلاق شريط التنقل -->

<!-- ==========================================
     بداية المحتوى الرئيسي
     ========================================== -->
<main class="container my-4">
<!-- وسم <main>:
     - container : حاوية Bootstrap
     - my-4 : هامش رأسي بمقدار 4
     الفائدة: تحسين البنية الدلالية -->

  <?php showFlash(); ?>
  <!-- عرض الرسائل المؤقتة (نجاح/خطأ/تحذير/معلومات)
       - تُستدعى دالة showFlash() من functions.php
       - تعرض التنبيه ثم تحذفه من الجلسة -->