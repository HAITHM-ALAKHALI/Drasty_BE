<?php

namespace App\Services;

class SystemSettings
{
    // 1. المتغير الساكن الذي سيحمل النسخة الوحيدة للكلاس
    private static ?SystemSettings $instance = null;

    // مصفوفة افتراضية لإعدادات النظام العامة
    private array $settings = [];

    // 2. دالة إنشائية خاصة لمنع الإنشاء المباشر عبر new SystemSettings()
    private function __construct()
    {
        // تحميل الإعدادات الأساسية الافتراضية لنظام Drasty
        $this->settings = [
            'academic_year'     => '2026-2027',
            'current_semester'  => 'الفصل الأول',
            'max_file_size_mb'  => 50,
            'system_status'     => 'operational',
            'allowed_file_exts' => ['pdf', 'ppt', 'pptx', 'docx', 'zip'],
        ];
    }

    // 3. منع استنساخ الكائن لمنع إنشاء نسخ إضافية
    private function __clone() {}

    // 4. منع فك التسلسل (Deserialization)
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    // 5. نقطة الوصول العامة والوحيدة للحصول على النسخة (Global Access Point)
    public static function getInstance(): SystemSettings
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // دالة لقراءة إعداد معين
    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    // دالة لتعديل أو إضافة إعداد في الذاكرة
    public function set(string $key, $value): void
    {
        $this->settings[$key] = $value;
    }

    // إرجاع كافة الإعدادات
    public function getAll(): array
    {
        return $this->settings;
    }
}