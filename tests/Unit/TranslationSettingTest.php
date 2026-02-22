<?php

namespace Masterweb\Translations\Tests\Unit;

use Masterweb\Translations\Models\TranslationSetting;
use Masterweb\Translations\Tests\TestCase;

class TranslationSettingTest extends TestCase
{
    public function test_set_and_get_value(): void
    {
        TranslationSetting::set('test_key', 'test_value');

        $this->assertEquals('test_value', TranslationSetting::get('test_key'));
    }

    public function test_get_returns_default_when_not_set(): void
    {
        $this->assertEquals('default', TranslationSetting::get('nonexistent', 'default'));
    }

    public function test_get_returns_empty_string_default(): void
    {
        $this->assertEquals('', TranslationSetting::get('nonexistent'));
    }

    public function test_set_overwrites_existing_value(): void
    {
        TranslationSetting::set('key', 'value1');
        TranslationSetting::set('key', 'value2');

        $this->assertEquals('value2', TranslationSetting::get('key'));
        $this->assertDatabaseCount('translation_settings', 1);
    }

    public function test_set_allows_empty_string(): void
    {
        TranslationSetting::set('key', '');

        $this->assertEquals('', TranslationSetting::get('key'));
    }

    public function test_set_allows_json_value(): void
    {
        TranslationSetting::set('key', json_encode(['a', 'b']));

        $this->assertEquals('["a","b"]', TranslationSetting::get('key'));
    }

    public function test_multiple_settings(): void
    {
        TranslationSetting::set('key1', 'val1');
        TranslationSetting::set('key2', 'val2');
        TranslationSetting::set('key3', 'val3');

        $this->assertEquals('val1', TranslationSetting::get('key1'));
        $this->assertEquals('val2', TranslationSetting::get('key2'));
        $this->assertEquals('val3', TranslationSetting::get('key3'));
    }
}
