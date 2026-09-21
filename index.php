<?php
// استدعاء ملف الاتصال بقاعدة البيانات لتفعيل كائن الاتصال $pdo
require_once 'config/db.php';

// استدعاء ملف الدوال العامة المساعدة مثل التحقق من تسجيل الدخول ودالة التعقيم e()
require_once 'includes/functions.php';

// جلب العدد الإجمالي للغرف من جدول الغرف في قاعدة البيانات
$totalRooms     = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

// جلب عدد الغرف المتاحة فقط التي حالتها 'available' من قاعدة البيانات
$availableRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();

// جلب كافة أنواع الغرف من جدول أنواع الغرف مرتبة تصاعدياً حسب السعر لكل ليلة
$roomTypes      = $pdo->query("SELECT * FROM room_types ORDER BY price_per_night")->fetchAll();

// مصفوفة لتحديد مسارات الصور المحلية لكل نوع غرفة بناءً على معرف النوع (ID)
$typeImages = [
    1 => '/hotel-system/assets/images/single.jpg', // صورة الغرفة الفردية
    2 => '/hotel-system/assets/images/double.jpg', // صورة الغرفة المزدوجة
    3 => '/hotel-system/assets/images/suite.jpg',  // صورة الجناح
];

// مسار الصورة الافتراضية في حال لم يتم العثور على صورة مطابقة لمعرف الغرفة
$defaultImage = '/hotel-system/assets/images/default.jpg';

// تعيين عنوان الصفحة ليتم استخدامه في الهيدر
$pageTitle = 'الرئيسية';

// تعريف المسار الأساسي للموقع (فارغ هنا لاعتباره في الجذر)
$baseUrl = '';

// تضمين ملف الهيدر (القائمة العلوية والتصميم الثابت للبداية)
include 'includes/header.php';
?>

<!-- بداية قسم الواجهة الرئيسية الترحيبية (Hero Section) -->
<div class="hero mb-5">
  <!-- عنوان ترحيبي مع أيقونة مبنى -->
  <h1><i class="bi bi-building"></i> مرحباً بك في فندقنا</h1>
  
  <!-- فقرة تعريفية قصيرة تحت العنوان -->
  <p class="lead">استمتع بإقامة لا تُنسى في قلب المدينة</p>
  
  <!-- حاوية تحتوي على أزرار التفاعل السريع -->
  <div class="mt-4">
    <!-- زر للانتقال إلى صفحة تصفح الغرف -->
    <a href="rooms.php" class="btn btn-warning btn-lg me-2">
      <i class="bi bi-door-open"></i> تصفح الغرف
    </a>
    
    <?php if (!isLoggedIn()): ?>
      <!-- زر إنشاء حساب جديد يظهر فقط إذا لم يكن المستخدم مسجلاً للدخول -->
      <a href="register.php" class="btn btn-outline-light btn-lg">
        <i class="bi bi-person-plus"></i> احجز الآن
      </a>
    <?php else: ?>
      <!-- زر لوحة التحكم يظهر إذا كان المستخدم مسجلاً، ويتغير رابطه حسب صلاحياته (أدمن أو زبون) -->
      <a href="<?= isAdmin() ? 'admin/dashboard.php' : 'customer/dashboard.php' ?>" class="btn btn-outline-light btn-lg">
        <i class="bi bi-speedometer2"></i> لوحتي
      </a>
    <?php endif; ?>
  </div>
</div>
<!-- نهاية قسم الواجهة الرئيسية -->

<!-- بداية صف الإحصائيات (عرض أعداد الغرف وأنواعها) -->
<div class="row g-3 mb-5">
  <!-- بطاقة إحصائية لعرض عدد أنواع الغرف -->
  <div class="col-md-4">
    <div class="card stat-card bg-warning text-center">
      <div class="card-body">
        <i class="bi bi-tags fs-1"></i>
        <!-- طباعة عدد أنواع الغرف باستخدام دالة count -->
        <h2 class="mb-0"><?= count($roomTypes) ?></h2>
        <p class="mb-0">نوع غرفة</p>
      </div>
    </div>
  </div>

  <!-- بطاقة إحصائية لعرض عدد الغرف المتاحة -->
  <div class="col-md-4">
    <div class="card stat-card bg-success text-center">
      <div class="card-body">
        <i class="bi bi-check-circle fs-1"></i>
        <!-- طباعة متغير الغرف المتاحة -->
        <h2 class="mb-0"><?= $availableRooms ?></h2>
        <p class="mb-0">غرفة متاحة الآن</p>
      </div>
    </div>
  </div>

  <!-- بطاقة إحصائية لعرض إجمالي عدد الغرف -->
  <div class="col-md-4">
    <div class="card stat-card bg-primary text-center">
      <div class="card-body">
        <i class="bi bi-door-open fs-1"></i>
        <!-- طباعة متغير إجمالي الغرف -->
        <h2 class="mb-0"><?= $totalRooms ?></h2>
        <p class="mb-0">غرفة إجمالية</p>
      </div>
    </div>
  </div>
</div>
<!-- نهاية صف الإحصائيات -->

<!-- عنوان قسم أنواع الغرف المتوفرة -->
<h2 class="text-center mb-4">
  <i class="bi bi-stars text-warning"></i> أنواع الغرف المتوفرة
</h2>

<!-- بداية شبكة عرض أنواع الغرف الديناميكية -->
<div class="row g-4 mb-5">
  <?php foreach ($roomTypes as $t): ?>
     
  
    <?php 
    // تحديد رابط الصورة للنوع الحالي، وإذا لمשت توجد صورة مخصصة يتم استخدام الصورة الافتراضية
    $imgUrl = $typeImages[$t['id']] ?? $defaultImage; 
    ?>
    
    <!-- عمود لكل نوع غرفة (يعرض 3 أعمدة في الشاشات المتوسطة والكبيرة) -->
    <div class="col-md-4">
      <div class="card room-card h-100">
        <!-- عرض صورة الغرفة مع تعقيم اسم الغرفة في خاصية الـ alt للحماية -->
        <img src="<?= $imgUrl ?>" class="card-img-top" alt="<?= e($t['name']) ?>" style="height: 240px; object-fit: cover;">
        
        <div class="card-body d-flex flex-column">
          <!-- عنوان اسم نوع الغرفة مع أيقونة -->
          <h5 class="card-title"><i class="bi bi-door-closed text-primary"></i> <?= e($t['name']) ?></h5>
          
          <!-- وصف نوع الغرفة مع جعل النص يأخذ المساحة المرنة -->
          <p class="card-text text-muted flex-grow-1"><?= e($t['description']) ?></p>
          
          <!-- شارة توضح الحد الأقصى لعدد الأشخاص الذين تستوعبهم الغرفة -->
          <div class="mb-2">
            <span class="badge bg-info"><i class="bi bi-people"></i> حتى <?= $t['capacity'] ?> أشخاص</span>
          </div>
          
          <!-- عرض السعر لكل ليلة منسقاً برقم عشري ومقترناً بعلامة الدولار -->
          <p class="fs-4 text-primary mb-3">
            <?= number_format($t['price_per_night'], 2) ?> $
            <small class="text-muted fs-6">/ الليلة</small>
          </p>
          
          <!-- زر للانتقال إلى صفحة الغرف وتصفية النتائج بناءً على معرف نوع الغرفة المحدد -->
          <a href="rooms.php?type=<?= $t['id'] ?>" class="btn btn-primary">
            <i class="bi bi-eye"></i> عرض الغرف
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<!-- نهاية شبكة عرض أنواع الغرف -->

<!-- قسم مميزات الفندق (لماذا تختار فندقنا؟) -->
<div class="card bg-light border-0 mb-4">
  <div class="card-body text-center py-5">
    <h3 class="mb-4">لماذا تختار فندقنا؟</h3>
    <div class="row g-4">
      <!-- ميزة الخدمة الممتازة -->
      <div class="col-md-4">
        <i class="bi bi-star-fill text-warning fs-1"></i>
        <h5 class="mt-2">خدمة ممتازة</h5>
        <p class="text-muted">فريق محترف في خدمتك 24/7</p>
      </div>
      
      <!-- ميزة الحجز الآمن -->
      <div class="col-md-4">
        <i class="bi bi-shield-check text-success fs-1"></i>
        <h5 class="mt-2">حجز آمن</h5>
        <p class="text-muted">بياناتك محمية بأعلى المعايير</p>
      </div>
      
      <!-- ميزة الأسعار المناسبة -->
      <div class="col-md-4">
        <i class="bi bi-cash-coin text-primary fs-1"></i>
        <h5 class="mt-2">أسعار مناسبة</h5>
        <p class="text-muted">أفضل الأسعار في المنطقة</p>
      </div>
    </div>
  </div>
</div>
<!-- نهاية قسم مميزات الفندق -->

<?php include 'includes/footer.php'; ?>
// تضمين ملف الفوتر (التذييل وإغلاق الوسوم العامة والاسكريبتات)
include 'includes/footer.php'; ?>
