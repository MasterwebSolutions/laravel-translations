<?php

namespace Masterweb\Translations\Tests\Unit;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_t_returns_fallback_when_no_translations_exist(): void
    {
        $this->assertEquals('Home', t('nav.home', 'Home'));
    }

    public function test_t_returns_value_from_database(): void
    {
        $this->seedTranslations();
        app()->setLocale('es');

        SiteTranslation::clearCache();

        $this->assertEquals('Inicio', t('nav.home', 'Home'));
    }

    public function test_t_returns_value_for_different_locale(): void
    {
        $this->seedTranslations();
        app()->setLocale('en');

        SiteTranslation::clearCache();

        $this->assertEquals('Home', t('nav.home', 'Inicio'));
    }

    public function test_t_returns_fallback_when_value_is_empty(): void
    {
        $this->seedTranslations();
        app()->setLocale('en');

        SiteTranslation::clearCache();

        // nav.about in 'en' has empty value
        $this->assertEquals('About', t('nav.about', 'About'));
    }

    public function test_t_handles_single_segment_key(): void
    {
        SiteTranslation::create([
            'group' => '_root', 'key' => 'hello', 'lang' => 'es', 'value' => 'Hola',
        ]);

        app()->setLocale('es');
        SiteTranslation::clearCache();

        $this->assertEquals('Hola', t('hello', 'Hello'));
    }

    public function test_t_returns_fallback_for_nonexistent_key(): void
    {
        $this->seedTranslations();
        app()->setLocale('es');

        $this->assertEquals('Fallback', t('nonexistent.key', 'Fallback'));
    }

    public function test_t_raw_returns_string_value(): void
    {
        $this->seedTranslations();
        app()->setLocale('es');
        SiteTranslation::clearCache();

        $this->assertEquals('Inicio', t_raw('nav.home', 'Home'));
    }

    public function test_t_raw_decodes_json_array(): void
    {
        SiteTranslation::create([
            'group' => 'config', 'key' => 'items', 'lang' => 'es',
            'value' => '["item1","item2","item3"]',
        ]);
        app()->setLocale('es');
        SiteTranslation::clearCache();

        $result = t_raw('config.items');
        $this->assertIsArray($result);
        $this->assertEquals(['item1', 'item2', 'item3'], $result);
    }

    public function test_t_raw_returns_default_when_empty(): void
    {
        $this->assertEquals('default', t_raw('missing.key', 'default'));
    }

    public function test_available_languages_returns_configured_languages(): void
    {
        $langs = available_languages();

        $this->assertIsArray($langs);
        $this->assertNotEmpty($langs);

        $codes = array_column($langs, 'code');
        $this->assertContains('es', $codes);
        $this->assertContains('en', $codes);

        // Each language should have code, flag, name
        foreach ($langs as $lang) {
            $this->assertArrayHasKey('code', $lang);
            $this->assertArrayHasKey('flag', $lang);
            $this->assertArrayHasKey('name', $lang);
        }
    }

    public function test_t_function_exists(): void
    {
        $this->assertTrue(function_exists('t'));
    }

    public function test_t_raw_function_exists(): void
    {
        $this->assertTrue(function_exists('t_raw'));
    }

    public function test_available_languages_function_exists(): void
    {
        $this->assertTrue(function_exists('available_languages'));
    }
}
