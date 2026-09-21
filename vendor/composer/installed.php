<?php return array(
// فتح وسم PHP + إرجاع مصفوفة مباشرة
// الفائدة: الملف يُستدعى بـ require → القيمة المُرجَعة هي هذه المصفوفة
// (نمط شائع في ملفات Composer المُولَّدة)

    'root' => array(
    // المفتاح 'root': يحتوي بيانات المشروع الجذر (المشروع نفسه)
    // وليس مكتبة خارجية — بل مشروعك أنت

        'name' => '__root__',
        // اسم المشروع: __root__
        // - هذه القيمة الافتراضية عندما لا يُسمّى المشروع في composer.json
        // - لو أضفت "name": "mycompany/hotel-system" في composer.json → سيظهر هنا

        'pretty_version' => '1.0.0+no-version-set',
        // الإصدار "الجميل" (للعرض):
        // - 1.0.0: الإصدار الافتراضي
        // - +no-version-set: لاحقة تعني "لم يُحدَّد إصدار"
        // السبب: المشروع الجذري لا يحتاج إصداراً (ليس مكتبة منشورة)

        'version' => '1.0.0.0',
        // الإصدار الكامل (للتحقق البرمجي):
        // - 4 أرقام: Major.Minor.Patch.Build
        // - الفائدة: مقارنة دقيقة بين الإصدارات

        'reference' => null,
        // المرجع (Commit Hash في Git):
        // - null: لأن المشروع الجذري ليس له commit
        // - في المكتبات: hash مثل '1bc1716a507a65e039d4ac9d9adebbbd0d346e15'

        'type' => 'library',
        // نوع الحزمة:
        // - library: مكتبة عادية
        // - project: مشروع كامل
        // - metapackage: حزمة وصفية (بدون كود)
        // - composer-plugin: إضافة Composer

        'install_path' => __DIR__ . '/../../',
        // مسار التثبيت:
        // - __DIR__: مجلد vendor/composer
        // - '/../../': اصعد مجلدين للأعلى = جذر المشروع
        // النتيجة: /project/ (جذر المشروع)

        'aliases' => array(),
        // الأسماء البديلة (Aliases):
        // - فارغة: لا توجد أسماء بديلة
        // - تُستخدم عند تثبيت إصدارات متعددة من نفس المكتبة

        'dev' => true,
        // هل المشروع في وضع التطوير؟
        // - true: نعم (تم تثبيت dev-dependencies)
        // - false: لا (إنتاج)
        // الفائدة: composer install --no-dev يجعلها false
    ),
    // إغلاق قسم root

    'versions' => array(
    // المفتاح 'versions': يحتوي قائمة كل الحزم المُثبَّتة
    // (بما فيها المشروع الجذري)

        '__root__' => array(
        // الحزمة الأولى: المشروع الجذري نفسه
        // (يظهر هنا أيضاً لتوحيد البنية)

            'pretty_version' => '1.0.0+no-version-set',
            // نفس البيانات أعلاه

            'version' => '1.0.0.0',
            // الإصدار الكامل

            'reference' => null,
            // لا يوجد commit (مشروع محلي)

            'type' => 'library',
            // النوع: library

            'install_path' => __DIR__ . '/../../',
            // المسار: جذر المشروع

            'aliases' => array(),
            // لا أسماء بديلة

            'dev_requirement' => false,
            // هل هو مطلوب للتطوير فقط؟
            // - false: لا (مطلوب دائماً)
            // - السبب: المشروع الجذري ليس dependency
        ),
        // إغلاق __root__

        'phpmailer/phpmailer' => array(
        // الحزمة الثانية: PHPMailer (مكتبة إرسال البريد)

            'pretty_version' => 'v7.1.1',
            // الإصدار "الجميل" للعرض:
            // - v7.1.1: الإصدار 7.1.1 (مع v في البداية حسب GitHub tag)

            'version' => '7.1.1.0',
            // الإصدار الكامل:
            // - 7.1.1.0: Major.Minor.Patch.Build

            'reference' => '1bc1716a507a65e039d4ac9d9adebbbd0d346e15',
            // مرجع Git (Commit Hash):
            // - 40 حرفاً سداسياً عشرياً = SHA-1
            // - الفائدة: يضمن تثبيت نفس النسخة بالضبط
            // - يُستخدم في composer.lock للتثبيت الدقيق

            'type' => 'library',
            // نوع الحزمة: مكتبة

            'install_path' => __DIR__ . '/../phpmailer/phpmailer',
            // مسار التثبيت:
            // - __DIR__: /project/vendor/composer
            // - '/..': اصعد للأعلى → /project/vendor
            // - '/phpmailer/phpmailer': المسار داخل vendor
            // النتيجة: /project/vendor/phpmailer/phpmailer

            'aliases' => array(),
            // لا أسماء بديلة

            'dev_requirement' => false,
            // هل هي dev-dependency؟
            // - false: لا (مطلوبة في الإنتاج أيضاً)
            // - الفائدة: تُثبَّت حتى مع composer install --no-dev
        ),
        // إغلاق phpmailer/phpmailer
    ),
    // إغلاق versions
);
// إغلاق المصفوفة