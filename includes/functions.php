<?php
// فتح وسم PHP لبدء كتابة الكود

/**
 * الملفات المساعدة العامة
 */
// تعليق متعدد الأسطر (DocBlock) يوضح أن هذا الملف يحتوي على دوال مساعدة عامة
// تُستخدم في كل صفحات المشروع

if (session_status() === PHP_SESSION_NONE) {
// فحص: هل الجلسة لم تبدأ بعد؟
// session_status() ترجع حالة الجلسة:
//   - PHP_SESSION_NONE = لم تبدأ
//   - PHP_SESSION_ACTIVE = نشطة
//   - PHP_SESSION_DISABLED = معطلة

    session_start();
    // بدء الجلسة (أو استئنافها إن كانت موجودة)
    // الفائدة: السماح بتخزين $_SESSION واسترجاعها
    // ⚠️ لماذا الفحص؟ لو استدعينا session_start() مرتين → تحذير "session already started"
}
// إغلاق الشرط

function e($str) {
// تعريف دالة اسمها e تأخذ معاملاً واحداً $str
// الهدف: تأمين النصوص ضد هجمات XSS

    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    // إرجاع النص بعد تأمينه:
    // - $str ?? '' : إذا كان $str = null → استخدم نصاً فارغاً (يمنع التحذيرات)
    // - htmlspecialchars : تحويل الأحرف الخاصة إلى كيانات HTML آمنة
    //   * < → &lt;
    //   * > → &gt;
    //   * " → &quot;
    //   * ' → &#039;
    //   * & → &amp;
    // - ENT_QUOTES : تحويل علامات الاقتباس الفردية والمزدوجة
    // - 'UTF-8' : ترميز الأحرف (يدعم العربية)
}
// إغلاق الدالة

function isLoggedIn() {
// تعريف دالة لفحص تسجيل الدخول

    return isset($_SESSION['user_id']);
    // إرجاع true إذا كان user_id موجوداً في الجلسة
    // isset() ترجع true إذا كان المتغير موجوداً وليس NULL
    // الفكرة: عند تسجيل الدخول، نخزّن $_SESSION['user_id'] — فوجوده يعني أن المستخدم مسجل
}
// إغلاق الدالة

function isAdmin() {
// تعريف دالة لفحص صلاحية المدير

    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    // شرطان معاً:
    // 1. isLoggedIn() : يجب أن يكون مسجلاً
    // 2. ($_SESSION['role'] ?? '') === 'admin' : دوره مدير
    //    - ?? '' : إذا لم يكن الدور موجوداً → نص فارغ (يمنع التحذيرات)
    // ⚠️ لماذا شرطان؟ لو المستخدم غير مسجل → $_SESSION['role'] غير موجود
    //    بدون isLoggedIn() → خطأ "Undefined index"
}
// إغلاق الدالة

function redirect($url) {
// تعريف دالة التوجيه

    header("Location: $url");
    // إرسال ترويسة HTTP لإعادة توجيه المتصفح
    // $url = العنوان الجديد

    exit;
    // إيقاف تنفيذ السكربت فوراً
    // ⚠️ ضروري: بدون exit، قد يستمر السكربت في التنفيذ
}
// إغلاق الدالة

function setFlash($type, $msg) {
// تعريف دالة لتخزين رسالة مؤقتة (تظهر مرة واحدة)

    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    // تخزين مصفوفة في الجلسة تحتوي:
    // - 'type' : نوع الرسالة (success, danger, warning, info)
    // - 'msg'  : نص الرسالة
    // الفكرة: "Flash Message" = رسالة تظهر مرة واحدة ثم تُحذف
}
// إغلاق الدالة

function showFlash() {
// تعريف دالة لعرض الرسالة المخزنة (إن وُجدت)

    if (!empty($_SESSION['flash'])) {
    // فحص: إذا كانت هناك رسالة مخزنة

        $f = $_SESSION['flash'];
        // تخزين الرسالة في متغير مؤقت (لاختصار الكتابة)

        echo "<div class='alert alert-{$f['type']} alert-dismissible fade show'>
                {$f['msg']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        // طباعة HTML للتنبيه:
        // - alert alert-{type} : كلاس Bootstrap (success/danger/warning/info)
        // - alert-dismissible : قابلة للإغلاق
        // - fade show : تأثير الظهور
        // - {$f['msg']} : نص الرسالة
        // - btn-close : زر الإغلاق (X)
        // - data-bs-dismiss="alert" : يخبر Bootstrap بإغلاق التنبيه

        unset($_SESSION['flash']);
        // حذف الرسالة من الجلسة
        // ⚠️ مهم: بدون unset، ستظهر الرسالة في كل صفحة!
    }
}
// إغلاق الدالة

function csrfToken() {
// تعريف دالة لتوليد رمز CSRF
// CSRF = Cross-Site Request Forgery (هجوم تزوير الطلبات)

    if (empty($_SESSION['csrf'])) {
    // فحص: إذا لم يكن الرمز موجوداً في الجلسة

        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        // توليد رمز عشوائي قوي:
        // - random_bytes(32) : 32 بايت عشوائية آمنة تشفيرياً
        // - bin2hex(...) : تحويلها إلى نص سادس عشري (64 حرفاً)
        // مثال: "a3f5b8c9d1e2f4..."
    }

    return $_SESSION['csrf'];
    // إرجاع الرمز
}
// إغلاق الدالة

function verifyCsrf($token) {
// تعريف دالة للتحقق من الرمز المُرسَل

    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    // شرطان:
    // 1. !empty($_SESSION['csrf']) : الرمز موجود في الجلسة
    // 2. hash_equals(...) : الرمز المُرسَل يطابق المخزَّن
    //    - hash_equals : مقارنة آمنة (تقي ضد Timing Attacks)
    //    ⚠️ لماذا hash_equals وليس ===؟
    //    لأن === قد تكشف عدد الأحرف الصحيحة عبر قياس الوقت
}
// إغلاق الدالة

// ==========================================
// 🔐 حماية تلقائية: التحقق من تسجيل الدخول
// ==========================================
// هذه الدالة تُستدعى تلقائياً في كل صفحة
// إذا لم يكن المستخدم مسجلاً، يتم توجيهه لصفحة الدخول

function requireLogin() {
// تعريف دالة الحماية التلقائية

    // الصفحات المسموح بها بدون تسجيل دخول
    $allowedPages = ['login.php', 'register.php', 'logout.php'];
    // مصفوفة بأسماء الملفات المسموح الوصول إليها بدون تسجيل دخول
    // (لأنها صفحات الدخول والتسجيل والخروج)

    $currentPage = basename($_SERVER['PHP_SELF']);
    // جلب اسم الصفحة الحالية:
    // - $_SERVER['PHP_SELF'] = المسار الكامل (مثل: /hotel-system/customer/book.php)
    // - basename(...) = استخراج اسم الملف فقط (book.php)

    if (!in_array($currentPage, $allowedPages) && !isLoggedIn()) {
    // فحص شرطين:
    // 1. الصفحة الحالية ليست في القائمة المسموحة
    // 2. المستخدم غير مسجل دخول
    // إذا تحقق الشرطان → توجيه للدخول

        setFlash('warning', 'يجب تسجيل الدخول أولاً.');
        // رسالة تحذير مؤقتة

        // تحديد المسار الصحيح لصفحة تسجيل الدخول
        $baseUrl = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
                    strpos($_SERVER['PHP_SELF'], '/customer/') !== false) ? '../' : '';
        // فحص موقع الصفحة:
        // - إذا كان في /admin/ أو /customer/ → $baseUrl = '../'
        //   (لأننا في مجلد فرعي، نحتاج نصعد للأعلى)
        // - وإلا (في الجذر) → $baseUrl = ''
        // strpos ترجع موقع النص أو false إذا لم يوجد

        redirect($baseUrl . 'login.php');
        // توجيه المستخدم لصفحة الدخول بالمسار الصحيح
        // - من admin/bookings.php → '../login.php'
        // - من index.php → 'login.php'
    }

    // إذا كان المستخدم عميلاً، لا يمكنه الوصول لصفحات المدير
    if (isLoggedIn() && !isAdmin() && strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    // فحص ثلاثي:
    // 1. isLoggedIn() : المستخدم مسجل
    // 2. !isAdmin() : لكنه ليس مديراً (عميل عادي)
    // 3. strpos(...) !== false : والصفحة داخل مجلد /admin/

        setFlash('danger', 'لا تملك صلاحية الوصول.');
        // رسالة خطأ

        redirect('../customer/dashboard.php');
        // توجيهه للوحة العميل
    }
}
// إغلاق الدالة

// استدعاء الحماية تلقائياً
requireLogin();
// تنفيذ الدالة فوراً عند استدعاء هذا الملف
// الفائدة: كل ملف يستدعي functions.php → يحصل على الحماية تلقائياً