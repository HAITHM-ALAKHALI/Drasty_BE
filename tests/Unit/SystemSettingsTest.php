<?php

namespace Tests\Unit;

use App\Services\SystemSettings;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionClass;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    /**
     * إعادة Singleton إلى حالته الأولية قبل كل اختبار.
     * نستخدم Reflection للوصول إلى الخاصية الساكنة private $instance
     * دون تعديل كود التطبيق نفسه.
     */
    protected function resetSystemSettingsSingleton(): void
    {
        $reflection = new ReflectionClass(SystemSettings::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setValue(null, null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSystemSettingsSingleton();
    }

    protected function tearDown(): void
    {
        $this->resetSystemSettingsSingleton();

        parent::tearDown();
    }

    public function test_get_instance_returns_a_system_settings_instance(): void
    {
        // Arrange
        // لا توجد بيانات خارجية مطلوبة لهذا الاختبار.

        // Act
        $settings = SystemSettings::getInstance();

        // Assert
        $this->assertInstanceOf(SystemSettings::class, $settings);
    }

    public function test_get_instance_returns_the_same_singleton_instance(): void
    {
        // Arrange
        // يتم تصفير النسخة تلقائيًا في setUp().

        // Act
        $firstInstance = SystemSettings::getInstance();
        $secondInstance = SystemSettings::getInstance();

        // Assert
        $this->assertSame($firstInstance, $secondInstance);
    }

    public function test_default_settings_are_loaded_correctly(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();

        // Act
        $allSettings = $settings->getAll();

        // Assert
        $this->assertSame('2026-2027', $allSettings['academic_year']);
        $this->assertSame('الفصل الأول', $allSettings['current_semester']);
        $this->assertSame(50, $allSettings['max_file_size_mb']);
        $this->assertSame('operational', $allSettings['system_status']);
        $this->assertSame(
            ['pdf', 'ppt', 'pptx', 'docx', 'zip'],
            $allSettings['allowed_file_exts']
        );
    }

    public function test_get_returns_the_value_of_an_existing_setting(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();

        // Act
        $academicYear = $settings->get('academic_year');

        // Assert
        $this->assertSame('2026-2027', $academicYear);
    }

    public function test_get_returns_null_for_a_missing_setting_without_default(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();

        // Act
        $value = $settings->get('unknown_setting');

        // Assert
        $this->assertNull($value);
    }

    public function test_get_returns_the_provided_default_for_a_missing_setting(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();
        $defaultValue = 'fallback-value';

        // Act
        $value = $settings->get('unknown_setting', $defaultValue);

        // Assert
        $this->assertSame($defaultValue, $value);
    }

    public function test_set_updates_an_existing_setting(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();

        // Act
        $settings->set('system_status', 'maintenance');

        // Assert
        $this->assertSame('maintenance', $settings->get('system_status'));
    }

    public function test_set_adds_a_new_setting(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();

        // Act
        $settings->set('site_name', 'Drasty');

        // Assert
        $this->assertSame('Drasty', $settings->get('site_name'));
    }

    public function test_set_preserves_different_value_types(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();

        // Act
        $settings->set('debug_mode', true);
        $settings->set('supported_languages', ['ar', 'en']);
        $settings->set('optional_value', null);

        // Assert
        $this->assertTrue($settings->get('debug_mode'));
        $this->assertSame(['ar', 'en'], $settings->get('supported_languages'));
        $this->assertNull($settings->get('optional_value'));
    }

    public function test_get_all_returns_all_current_settings(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();
        $settings->set('site_name', 'Drasty');

        // Act
        $allSettings = $settings->getAll();

        // Assert
        $this->assertIsArray($allSettings);
        $this->assertArrayHasKey('academic_year', $allSettings);
        $this->assertArrayHasKey('current_semester', $allSettings);
        $this->assertArrayHasKey('max_file_size_mb', $allSettings);
        $this->assertArrayHasKey('system_status', $allSettings);
        $this->assertArrayHasKey('allowed_file_exts', $allSettings);
        $this->assertSame('Drasty', $allSettings['site_name']);
    }

    public function test_wakeup_rejects_unserializing_the_singleton(): void
    {
        // Arrange
        $settings = SystemSettings::getInstance();
        $serializedSettings = serialize($settings);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot unserialize a singleton.');

        // Act
        unserialize($serializedSettings);
    }
}
