<?php

namespace Masterweb\Translations\Tests\Unit;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Tests\TestCase;

class SiteTranslationTest extends TestCase
{
    public function test_create_translation(): void
    {
        $t = SiteTranslation::create([
            'group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio',
        ]);

        $this->assertDatabaseHas('site_translations', [
            'group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio',
        ]);
    }

    public function test_unique_constraint_group_key_lang(): void
    {
        SiteTranslation::create(['group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        SiteTranslation::create(['group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Otro']);
    }

    public function test_same_key_different_lang_allowed(): void
    {
        SiteTranslation::create(['group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio']);
        SiteTranslation::create(['group' => 'nav', 'key' => 'home', 'lang' => 'en', 'value' => 'Home']);

        $this->assertDatabaseCount('site_translations', 2);
    }

    public function test_get_value_returns_correct_translation(): void
    {
        $this->seedTranslations();
        SiteTranslation::clearCache();

        $this->assertEquals('Inicio', SiteTranslation::getValue('nav', 'home', 'es'));
        $this->assertEquals('Home', SiteTranslation::getValue('nav', 'home', 'en'));
    }

    public function test_get_value_returns_default_when_not_found(): void
    {
        $this->assertEquals('Fallback', SiteTranslation::getValue('nav', 'missing', 'es', 'Fallback'));
    }

    public function test_get_all_for_lang_returns_grouped_array(): void
    {
        $this->seedTranslations();
        SiteTranslation::clearCache();

        $all = SiteTranslation::getAllForLang('es');

        $this->assertIsArray($all);
        $this->assertArrayHasKey('nav', $all);
        $this->assertArrayHasKey('footer', $all);
        $this->assertEquals('Inicio', $all['nav']['home']);
        $this->assertEquals('Acerca de', $all['nav']['about']);
    }

    public function test_get_all_for_lang_returns_empty_array_on_error(): void
    {
        // Drop the table to simulate error
        \Illuminate\Support\Facades\Schema::dropIfExists('site_translations');
        SiteTranslation::clearCache();

        $result = SiteTranslation::getAllForLang('es');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_group_returns_keys_for_group(): void
    {
        $this->seedTranslations();
        SiteTranslation::clearCache();

        $nav = SiteTranslation::getGroup('nav', 'es');

        $this->assertArrayHasKey('home', $nav);
        $this->assertArrayHasKey('about', $nav);
        $this->assertEquals('Inicio', $nav['home']);
    }

    public function test_clear_cache_does_not_throw(): void
    {
        $this->seedTranslations();

        // Should not throw even if called multiple times
        SiteTranslation::clearCache();
        SiteTranslation::clearCache();

        $this->assertTrue(true);
    }

    public function test_clear_cache_does_not_throw_when_table_missing(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('site_translations');

        // Should not throw
        SiteTranslation::clearCache();
        $this->assertTrue(true);
    }

    public function test_scope_by_group(): void
    {
        $this->seedTranslations();

        $nav = SiteTranslation::byGroup('nav')->get();
        $this->assertTrue($nav->every(fn($t) => $t->group === 'nav'));
    }

    public function test_scope_by_lang(): void
    {
        $this->seedTranslations();

        $es = SiteTranslation::byLang('es')->get();
        $this->assertTrue($es->every(fn($t) => $t->lang === 'es'));
    }

    public function test_fillable_fields(): void
    {
        $t = new SiteTranslation();
        $this->assertEquals(['group', 'key', 'lang', 'value'], $t->getFillable());
    }
}
