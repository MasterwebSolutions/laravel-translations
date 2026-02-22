<?php

namespace Masterweb\Translations\Tests\Unit;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Models\TranslationSetting;
use Masterweb\Translations\TranslationManager;
use Masterweb\Translations\Tests\TestCase;

class TranslationManagerTest extends TestCase
{
    public function test_get_source_lang_returns_config_default(): void
    {
        $this->assertEquals('es', TranslationManager::getSourceLang());
    }

    public function test_get_source_lang_returns_db_value_when_set(): void
    {
        TranslationSetting::set('source_language', 'en');

        $this->assertEquals('en', TranslationManager::getSourceLang());
    }

    public function test_set_source_lang(): void
    {
        TranslationManager::setSourceLang('pt');

        $this->assertEquals('pt', TranslationManager::getSourceLang());
    }

    public function test_get_languages_returns_config_defaults(): void
    {
        $langs = TranslationManager::getLanguages();

        $this->assertContains('es', $langs);
        $this->assertContains('en', $langs);
    }

    public function test_get_languages_source_always_first(): void
    {
        // saveLanguages puts source first internally
        TranslationManager::saveLanguages(['en', 'pt']);

        $langs = TranslationManager::getLanguages();
        // Source lang 'es' should be prepended
        $this->assertEquals('es', $langs[0]);
    }

    public function test_add_language(): void
    {
        TranslationManager::addLanguage('pt');

        $langs = TranslationManager::getLanguages();
        $this->assertContains('pt', $langs);
    }

    public function test_add_language_no_duplicates(): void
    {
        TranslationManager::addLanguage('en');
        TranslationManager::addLanguage('en');

        $langs = TranslationManager::getLanguages();
        $count = array_count_values($langs);
        $this->assertEquals(1, $count['en']);
    }

    public function test_remove_language(): void
    {
        TranslationManager::addLanguage('pt');
        $this->assertContains('pt', TranslationManager::getLanguages());

        TranslationManager::removeLanguage('pt');
        $this->assertNotContains('pt', TranslationManager::getLanguages());
    }

    public function test_cannot_remove_source_language(): void
    {
        // Save initial languages so they're in DB
        TranslationManager::saveLanguages(['es', 'en']);

        TranslationManager::removeLanguage('es');

        // Source should still be present
        $this->assertContains('es', TranslationManager::getLanguages());
    }

    public function test_remove_language_deletes_translations(): void
    {
        SiteTranslation::create(['group' => 'nav', 'key' => 'home', 'lang' => 'pt', 'value' => 'Início']);
        TranslationManager::addLanguage('pt');

        $this->assertDatabaseHas('site_translations', ['lang' => 'pt']);

        TranslationManager::removeLanguage('pt');

        $this->assertDatabaseMissing('site_translations', ['lang' => 'pt']);
    }

    public function test_save_languages_deduplicates(): void
    {
        TranslationManager::saveLanguages(['es', 'en', 'es', 'pt', 'en']);

        $langs = TranslationManager::getLanguages();
        $this->assertEquals(count($langs), count(array_unique($langs)));
    }

    public function test_sync_texts_creates_entries_for_all_languages(): void
    {
        // Create a temp blade file with t() call
        $scanDir = sys_get_temp_dir() . '/trans_test_views_' . uniqid();
        mkdir($scanDir, 0777, true);
        file_put_contents($scanDir . '/test.blade.php', "{{ t('test.greeting', 'Hola mundo') }}");

        config(['translations.scan_paths' => [$scanDir]]);

        $result = TranslationManager::syncTexts();

        $this->assertGreaterThan(0, $result['created']);
        $this->assertDatabaseHas('site_translations', [
            'group' => 'test', 'key' => 'greeting', 'lang' => 'es', 'value' => 'Hola mundo',
        ]);
        $this->assertDatabaseHas('site_translations', [
            'group' => 'test', 'key' => 'greeting', 'lang' => 'en', 'value' => '',
        ]);

        // Cleanup
        unlink($scanDir . '/test.blade.php');
        rmdir($scanDir);
    }

    public function test_sync_texts_does_not_overwrite_existing_values(): void
    {
        SiteTranslation::create(['group' => 'test', 'key' => 'greeting', 'lang' => 'es', 'value' => 'Custom']);
        SiteTranslation::create(['group' => 'test', 'key' => 'greeting', 'lang' => 'en', 'value' => 'Hello']);

        $scanDir = sys_get_temp_dir() . '/trans_test_views_' . uniqid();
        mkdir($scanDir, 0777, true);
        file_put_contents($scanDir . '/test.blade.php', "{{ t('test.greeting', 'Hola') }}");

        config(['translations.scan_paths' => [$scanDir]]);

        TranslationManager::syncTexts();

        // Should NOT overwrite existing values
        $this->assertDatabaseHas('site_translations', [
            'group' => 'test', 'key' => 'greeting', 'lang' => 'es', 'value' => 'Custom',
        ]);
        $this->assertDatabaseHas('site_translations', [
            'group' => 'test', 'key' => 'greeting', 'lang' => 'en', 'value' => 'Hello',
        ]);

        unlink($scanDir . '/test.blade.php');
        rmdir($scanDir);
    }

    public function test_scan_template_t_calls(): void
    {
        $scanDir = sys_get_temp_dir() . '/trans_test_scan_' . uniqid();
        mkdir($scanDir, 0777, true);
        file_put_contents($scanDir . '/page.blade.php', "
            {{ t('nav.home', 'Home') }}
            {{ t('nav.about', 'About') }}
            {{ t('footer.copy', 'Copyright') }}
        ");

        config(['translations.scan_paths' => [$scanDir]]);

        $results = TranslationManager::scanTemplateTCalls();

        $this->assertCount(3, $results);
        $groups = array_column($results, 'group');
        $this->assertContains('nav', $groups);
        $this->assertContains('footer', $groups);

        unlink($scanDir . '/page.blade.php');
        rmdir($scanDir);
    }

    public function test_coverage_stats(): void
    {
        $this->seedTranslations();
        SiteTranslation::clearCache();

        $stats = TranslationManager::coverageStats();

        $this->assertIsArray($stats);
        $this->assertNotEmpty($stats);

        foreach ($stats as $group) {
            $this->assertArrayHasKey('group', $group);
            $this->assertArrayHasKey('total_keys', $group);
            $this->assertArrayHasKey('missing_by_lang', $group);
            $this->assertArrayHasKey('total_missing', $group);
        }
    }
}
