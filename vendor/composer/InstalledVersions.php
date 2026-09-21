<?php
// فتح وسم PHP لبدء كتابة الكود

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
// تعليق متعدد الأسطر (ترخيص الملف):
// - يوضح أن الملف جزء من Composer
// - أسماء المؤلفين
// - رابط الترخيص للمزيد

namespace Composer;
// تعريف الـ Namespace: Composer
// الفائدة: تنظيم الكود ومنع تعارض الأسماء

use Composer\Autoload\ClassLoader;
// استيراد ClassLoader:
// - يُستخدم لاحقاً في getInstalled()
// - الفائدة: لا نكتب المسار الكامل في كل مرة

use Composer\Semver\VersionParser;
// استيراد VersionParser:
// - يُستخدم لمقارنة الإصدارات في satisfies()
// - الفائدة: مقارنة دقيقة وفق معايير SemVer

/**
 * This class is copied in every Composer installed project and available to all
 *
 * See also https://getcomposer.org/doc/07-runtime.md#installed-versions
 *
 * To require its presence, you can require `composer-runtime-api ^2.0`
 *
 * @final
 */
// تعليق DocBlock:
// - الكلاس يُنسخ تلقائياً لكل مشروع يستخدم Composer
// - متاح في أي مكان
// - @final: لا يمكن تمديده (منع الوراثة)

class InstalledVersions
{
// تعريف الكلاس InstalledVersions:
// - الوظيفة: واجهة برمجية للاستعلام عن الحزم المُثبَّتة
// - الفائدة: يمكن لأي مكتبة فحص وجود إصدارات معينة

    /**
     * @var string|null if set (by reflection by Composer), this should be set to the path where this class is being copied to
     * @internal
     */
    private static $selfDir = null;
    // متغير خاص وثابت:
    // - private: لا يمكن الوصول من خارج الكلاس
    // - static: مشترك بين كل الكائنات
    // - null (افتراضي): يُحسب عند الحاجة
    // - الفائدة: Cache لمسار المجلد الحالي (لتجنب الحساب المتكرر)

    /**
     * @var mixed[]|null
     * @psalm-var array{root: array{...}, versions: array{...}}|array{}|null
     */
    private static $installed;
    // متغير خاص وثابت:
    // - يخزّن بيانات installed.php بعد تحميلها
    // - null: لم تُحمَّل بعد
    // - الفائدة: Cache لتفادي قراءة الملف من القرص مرات متعددة

    /**
     * @var bool
     */
    private static $installedIsLocalDir;
    // علم: هل installed.php يأتي من المجلد الحالي؟
    // - الفائدة: منع إضافة نفس البيانات مرتين في getInstalled()

    /**
     * @var bool|null
     */
    private static $canGetVendors;
    // علم: هل يمكننا استخدام ClassLoader::getRegisteredLoaders()؟
    // - null (افتراضي): لم يُفحص بعد
    // - true: الدالة موجودة (Composer جديد)
    // - false: غير موجودة (Composer قديم)
    // - الفائدة: التحقق مرة واحدة فقط (تحسين الأداء)

    /**
     * @var array[]
     */
    private static $installedByVendor = array();
    // متغير خاص وثابت:
    // - يخزّن بيانات installed.php لكل vendorDir
    // - المفتاح: مسار vendor
    // - القيمة: بيانات installed.php
    // - الفائدة: Cache متعدد المشاريع (PHPUnit يحتاج هذا)

    /**
     * Returns a list of all package names which are present, either by being installed, replaced or provided
     *
     * @return string[]
     * @psalm-return list<string>
     */
    public static function getInstalledPackages()
    {
    // إرجاع قائمة بكل أسماء الحزم المُثبَّتة
    // - يشمل: المُثبَّتة + المُستبدَلة + المُوفَّرة

        $packages = array();
        // مصفوفة لتجميع النتائج من كل vendor

        foreach (self::getInstalled() as $installed) {
        // المرور على كل بيانات installed
        // - قد تكون واحدة (مشروع عادي)
        // - أو أكثر (PHPUnit يشغّل مشاريع متعددة)

            $packages[] = array_keys($installed['versions']);
            // array_keys: جلب أسماء الحزم (المفاتيح) من قسم versions
            // النتيجة: ['__root__', 'phpmailer/phpmailer', ...]
        }

        if (1 === \count($packages)) {
            return $packages[0];
            // إذا كانت مصفوفة واحدة فقط → أرجعها مباشرة (تحسين)
            // - \count: الشرطة \ لأن count دالة PHP عامة
        }

        return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
        // 1. call_user_func_array('array_merge', $packages):
        //    - دمج كل المصفوفات في واحدة
        // 2. array_flip:
        //    - عكس القيم للمفاتيح → يحذف التكرار تلقائياً
        //    - الفائدة: أسرع من array_unique
        // 3. array_keys:
        //    - إرجاع القيم الأصلية بدون تكرار
    }

    /**
     * Returns a list of all package names with a specific type e.g. 'library'
     *
     * @param  string   $type
     * @return string[]
     * @psalm-return list<string>
     */
    public static function getInstalledPackagesByType($type)
    {
    // إرجاع الحزم حسب النوع (مثل: library, project, metapackage)

        $packagesByType = array();
        // مصفوفة لتجميع النتائج

        foreach (self::getInstalled() as $installed) {
        // المرور على كل بيانات installed

            foreach ($installed['versions'] as $name => $package) {
            // المرور على كل حزمة:
            // - $name: اسم الحزمة
            // - $package: بيانات الحزمة

                if (isset($package['type']) && $package['type'] === $type) {
                // فحص: هل النوع مطابق؟
                // - isset: لتفادي تحذير undefined

                    $packagesByType[] = $name;
                    // إضافة اسم الحزمة للنتائج
                }
            }
        }

        return $packagesByType;
        // إرجاع النتائج
    }

    /**
     * Checks whether the given package is installed
     *
     * This also returns true if the package name is provided or replaced by another package
     *
     * @param  string $packageName
     * @param  bool   $includeDevRequirements
     * @return bool
     */
    public static function isInstalled($packageName, $includeDevRequirements = true)
    {
    // فحص: هل الحزمة مُثبَّتة؟

        foreach (self::getInstalled() as $installed) {
        // المرور على كل بيانات installed

            if (isset($installed['versions'][$packageName])) {
            // فحص: هل الحزمة موجودة في versions؟

                return $includeDevRequirements 
                    || !isset($installed['versions'][$packageName]['dev_requirement']) 
                    || $installed['versions'][$packageName]['dev_requirement'] === false;
                // إرجاع true إذا تحقق شرط واحد:
                // 1. includeDevRequirements = true → نقبل dev
                // 2. dev_requirement غير موجود → ليست dev
                // 3. dev_requirement = false → ليست dev فقط
                // - الفائدة: عند composer install --no-dev → الحزم dev موجودة في installed.php لكن dev_requirement = true
            }
        }

        return false;
        // الحزمة غير موجودة
    }

    /**
     * Checks whether the given package satisfies a version constraint
     *
     * e.g. If you want to know whether version 2.3+ of package foo/bar is installed, you would call:
     *
     *   Composer\InstalledVersions::satisfies(new VersionParser, 'foo/bar', '^2.3')
     *
     * @param  VersionParser $parser      Install composer/semver to have access to this class and functionality
     * @param  string        $packageName
     * @param  string|null   $constraint  A version constraint to check for
     * @return bool
     */
    public static function satisfies(VersionParser $parser, $packageName, $constraint)
    {
    // فحص: هل الحزمة تحقق قيد إصدار معين؟

        $constraint = $parser->parseConstraints((string) $constraint);
        // تحويل القيد النصي إلى كائن Constraint:
        // - مثال: '^2.3' → Constraint object
        // - (string): تحويل احتياطي

        $provided = $parser->parseConstraints(self::getVersionRanges($packageName));
        // جلب قيد الإصدار الفعلي للحزمة:
        // - getVersionRanges: يجلب كل الإصدارات المتاحة
        // - parseConstraints: يحوّلها لقيد

        return $provided->matches($constraint);
        // هل يتحقق القيد؟ true/false
        // - مثال: '7.1.1' يحقق '^7.0' → true
    }

    /**
     * Returns a version constraint representing all the range(s) which are installed for a given package
     *
     * @param  string $packageName
     * @return string Version constraint usable with composer/semver
     */
    public static function getVersionRanges($packageName)
    {
    // إرجاع نطاق الإصدارات المُثبَّتة للحزمة

        foreach (self::getInstalled() as $installed) {
        // المرور على كل بيانات installed

            if (!isset($installed['versions'][$packageName])) {
                continue;
                // تخطَّ الحزم غير الموجودة
            }

            $ranges = array();
            // مصفوفة لتجميع النطاقات

            if (isset($installed['versions'][$packageName]['pretty_version'])) {
                $ranges[] = $installed['versions'][$packageName]['pretty_version'];
                // إضافة الإصدار الأساسي (v7.1.1)
            }

            if (array_key_exists('aliases', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
                // دمج الأسماء البديلة (Aliases)
                // - array_key_exists: يفحص وجود المفتاح حتى لو كان null
            }

            if (array_key_exists('replaced', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
                // دمج الحزم المُستبدَلة
                // - مثال: monolog/monolog يستبدل psr/log
            }

            if (array_key_exists('provided', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
                // دمج الحزم المُوفَّرة
                // - مثال: psr/log-implementation
            }

            return implode(' || ', $ranges);
            // دمج النطاقات بفاصل ||
            // - مثال: 'v7.1.1 || 2.0.0'
            // - يُستخدم في composer/semver للمقارنة
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
        // خطأ: الحزمة غير موجودة
    }

    /**
     * @param  string      $packageName
     * @return string|null
     */
    public static function getVersion($packageName)
    {
    // إرجاع الإصدار الرقمي (7.1.1.0)

        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
                // تخطَّ الحزم غير الموجودة
            }

            if (!isset($installed['versions'][$packageName]['version'])) {
                return null;
                // لا يوجد إصدار رقمي (مثل metapackage)
            }

            return $installed['versions'][$packageName]['version'];
            // إرجاع الإصدار
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @param  string      $packageName
     * @return string|null
     */
    public static function getPrettyVersion($packageName)
    {
    // إرجاع الإصدار للعرض (v7.1.1)

        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['pretty_version'])) {
                return null;
                // لا يوجد إصدار "جميل"
            }

            return $installed['versions'][$packageName]['pretty_version'];
            // إرجاع v7.1.1 مثلاً
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @param  string      $packageName
     * @return string|null
     */
    public static function getReference($packageName)
    {
    // إرجاع Git commit hash

        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['reference'])) {
                return null;
                // لا يوجد مرجع
            }

            return $installed['versions'][$packageName]['reference'];
            // إرجاع 40 حرفاً سداسياً عشرياً
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @param  string      $packageName
     * @return string|null
     */
    public static function getInstallPath($packageName)
    {
    // إرجاع مسار التثبيت

        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            return isset($installed['versions'][$packageName]['install_path']) ? $installed['versions'][$packageName]['install_path'] : null;
            // إرجاع المسار أو null (metapackage ليس له مسار)
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @return array
     * @psalm-return array{name: string, pretty_version: string, version: string, reference: string|null, type: string, install_path: string, aliases: string[], dev: bool}
     */
    public static function getRootPackage()
    {
    // إرجاع بيانات المشروع الجذري

        $installed = self::getInstalled();
        // جلب كل بيانات installed

        return $installed[0]['root'];
        // إرجاع أول قسم 'root'
        // - [0]: أول dataset (الأقرب)
        // - ['root']: قسم المشروع الجذري
    }

    /**
     * Returns the raw installed.php data for custom implementations
     *
     * @deprecated Use getAllRawData() instead
     * @return array[]
     */
    public static function getRawData()
    {
    // ⚠️ مُهمَل: استخدم getAllRawData() بدلاً منه

        @trigger_error('getRawData only returns the first dataset loaded, which may not be what you expect. Use getAllRawData() instead which returns all datasets for all autoloaders present in the process.', E_USER_DEPRECATED);
        // إطلاق تحذير "مُهمَل":
        // - @: يتجاهل الخطأ في السياقات الحساسة
        // - E_USER_DEPRECATED: نوع التحذير

        if (null === self::$installed) {
        // فحص: إذا لم تُحمَّل البيانات بعد

            // only require the installed.php file if this file is loaded from its dumped location,
            // and not from its source location in the composer/composer package
            if (substr(__DIR__, -8, 1) !== 'C') {
            // فحص ذكي:
            // - __DIR__ في vendor/composer
            // - آخر 8 أحرف: 'composer'
            // - الحرف الأول من 'composer' هو 'c'
            // - لكن نفحص 'C' (كبير) لأن 'Composer' يبدأ بـ C كبير
            // الفائدة: التمييز بين نسخة المشروع ونسخة composer/composer

                self::$installed = include __DIR__ . '/installed.php';
                // تحميل ملف installed.php
                // - include: تحميل وإرجاع القيمة
            } else {
                self::$installed = array();
                // في Composer نفسه → مصفوفة فارغة
            }
        }

        return self::$installed;
        // إرجاع البيانات
    }

    /**
     * Returns the raw data of all installed.php which are currently loaded for custom implementations
     *
     * @return array[]
     */
    public static function getAllRawData()
    {
    // إرجاع كل بيانات installed.php المُحمَّلة

        return self::getInstalled();
        // استدعاء الدالة الداخلية التي تجمع كل المصادر
    }

    /**
     * Lets you reload the static array from another file
     *
     * @param  array[] $data A vendor/composer/installed.php data set
     * @return void
     */
    public static function reload($data)
    {
    // إعادة تحميل البيانات من ملف آخر

        self::$installed = $data;
        // استبدال البيانات الحالية

        self::$installedByVendor = array();
        // تفريغ ذاكرة vendor (لإعادة التحميل)

        // when using reload, we disable the duplicate protection to ensure that self::$installed data is
        // always returned, but we cannot know whether it comes from the installed.php in __DIR__ or not,
        // so we have to assume it does not, and that may result in duplicate data being returned when listing
        // all installed packages for example
        self::$installedIsLocalDir = false;
        // تعطيل فحص "المجلد المحلي"
        // - السبب: البيانات لا تأتي من __DIR__ بالضرورة
    }

    /**
     * @return string
     */
    private static function getSelfDir()
    {
    // إرجاع مسار المجلد الحالي (private)

        if (self::$selfDir === null) {
        // فحص: إذا لم يُحسَب بعد

            self::$selfDir = strtr(__DIR__, '\\', '/');
            // strtr: استبدال الأحرف
            // - '\' → '/'
            // - الفائدة: توحيد المسارات على Windows و Linux
        }

        return self::$selfDir;
        // إرجاع المسار (Cache)
    }

    /**
     * @return array[]
     */
    private static function getInstalled()
    {
    // ⭐ الدالة الأهم: تجميع كل بيانات installed

        if (null === self::$canGetVendors) {
        // فحص: إذا لم يُفحَص بعد

            self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
            // فحص: هل دالة getRegisteredLoaders موجودة؟
            // - true: Composer جديد (2.0+)
            // - false: Composer قديم
        }

        $installed = array();
        // مصفوفة لتجميع كل بيانات installed

        $copiedLocalDir = false;
        // علم: هل تم نسخ المجلد المحلي؟

        if (self::$canGetVendors) {
        // إذا كان Composer جديداً → استخدم getRegisteredLoaders

            $selfDir = self::getSelfDir();
            // جلب مسار المجلد الحالي

            foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
            // المرور على كل vendor مسجَّل:
            // - $vendorDir: مسار vendor
            // - $loader: كائن ClassLoader

                $vendorDir = strtr($vendorDir, '\\', '/');
                // توحيد المسار (Windows → Linux format)

                if (isset(self::$installedByVendor[$vendorDir])) {
                // فحص: هل حُمِّل سابقاً؟ (Cache)

                    $installed[] = self::$installedByVendor[$vendorDir];
                    // استخدم النسخة المحفوظة
                } elseif (is_file($vendorDir.'/composer/installed.php')) {
                // وإلا: هل الملف موجود؟

                    $required = require $vendorDir.'/composer/installed.php';
                    // تحميل الملف

                    self::$installedByVendor[$vendorDir] = $required;
                    // حفظه في الذاكرة (Cache)

                    $installed[] = $required;
                    // إضافته للقائمة

                    if (self::$installed === null && $vendorDir.'/composer' === $selfDir) {
                    // فحص: هل هذا هو vendor المشروع الحالي؟
                    // - self::$installed === null: لم تُحمَّل البيانات الأساسية بعد
                    // - $vendorDir.'/composer' === $selfDir: هذا هو مجلدنا

                        self::$installed = $required;
                        // تخزينه كبيانات أساسية

                        self::$installedIsLocalDir = true;
                        // تعيين علم "المجلد المحلي"
                    }
                }

                if (self::$installedIsLocalDir && $vendorDir.'/composer' === $selfDir) {
                // فحص: إذا كان هذا هو المجلد الحالي

                    $copiedLocalDir = true;
                    // منع إضافته مرتين
                }
            }
        }

        if (null === self::$installed) {
        // فحص: إذا لم تُحمَّل البيانات بعد
        // (مثلاً: Composer قديم أو لا يوجد registered loaders)

            // only require the installed.php file if this file is loaded from its dumped location,
            // and not from its source location in the composer/composer package
            if (substr(__DIR__, -8, 1) !== 'C') {
            // نفس الفحص الذكي السابق

                $required = require __DIR__ . '/installed.php';
                // تحميل installed.php من مجلدنا

                self::$installed = $required;
                // تخزينه
            } else {
                self::$installed = array();
                // في Composer نفسه → مصفوفة فارغة
            }
        }

        if (self::$installed !== array() && !$copiedLocalDir) {
        // فحص: إذا كانت البيانات موجودة ولم تُنسخ

            $installed[] = self::$installed;
            // إضافتها للقائمة
        }

        return $installed;
        // إرجاع القائمة النهائية
        // - قد تحتوي مصفوفة واحدة (مشروع عادي)
        // - أو أكثر (PHPUnit يشغّل مشاريع متعددة)
    }
}
// إغلاق الكلاس