<?php

namespace Masterweb\Translations\Tests\Feature;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_index_page_loads(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('Translations');
    }

    public function test_index_shows_existing_translations(): void
    {
        $this->seedTranslations();

        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('nav');
        $response->assertSee('home');
    }

    public function test_store_creates_new_translation(): void
    {
        $response = $this->postJson('/admin/translations', [
            'group' => 'test',
            'key' => 'greeting',
            'value' => 'Hola',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('site_translations', [
            'group' => 'test', 'key' => 'greeting', 'lang' => 'es',
        ]);
    }

    public function test_store_creates_entries_for_all_languages(): void
    {
        $this->postJson('/admin/translations', [
            'group' => 'test',
            'key' => 'greeting',
            'value' => 'Hola',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        // Should create for both es and en
        $this->assertDatabaseHas('site_translations', ['group' => 'test', 'key' => 'greeting', 'lang' => 'es']);
        $this->assertDatabaseHas('site_translations', ['group' => 'test', 'key' => 'greeting', 'lang' => 'en']);
    }

    public function test_update_modifies_translation(): void
    {
        $t = SiteTranslation::create([
            'group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio',
        ]);

        $response = $this->putJson("/admin/translations/{$t->id}", [
            'value' => 'Página Principal',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('site_translations', [
            'id' => $t->id, 'value' => 'Página Principal',
        ]);
    }

    public function test_inline_update(): void
    {
        $t = SiteTranslation::create([
            'group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio',
        ]);

        $response = $this->postJson("/admin/translations/{$t->id}/inline-update", [
            'value' => 'Home Page',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('site_translations', [
            'id' => $t->id, 'value' => 'Home Page',
        ]);
    }

    public function test_destroy_deletes_translation(): void
    {
        $t = SiteTranslation::create([
            'group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio',
        ]);

        $response = $this->delete("/admin/translations/{$t->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_translations', ['id' => $t->id]);
    }

    public function test_bulk_delete(): void
    {
        $this->seedTranslations();
        // bulkDelete only deletes non-source language rows
        $ids = SiteTranslation::where('group', 'nav')->where('lang', 'en')->pluck('id')->toArray();

        $response = $this->postJson('/admin/translations/bulk-delete', [
            'ids' => $ids,
        ]);

        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('site_translations', ['id' => $id]);
        }
    }

    public function test_clear_value(): void
    {
        // clearValue only works on non-source language
        $t = SiteTranslation::create([
            'group' => 'nav', 'key' => 'home', 'lang' => 'en', 'value' => 'Home',
        ]);

        $response = $this->postJson("/admin/translations/{$t->id}/clear-value");

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('site_translations', [
            'id' => $t->id, 'value' => '',
        ]);
    }

    public function test_sync_texts(): void
    {
        $scanDir = sys_get_temp_dir() . '/trans_sync_test_' . uniqid();
        mkdir($scanDir, 0777, true);
        file_put_contents($scanDir . '/test.blade.php', "{{ t('sync.hello', 'Hello World') }}");
        config(['translations.scan_paths' => [$scanDir]]);

        $response = $this->postJson('/admin/translations/sync');

        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'created', 'updated']);
        $this->assertDatabaseHas('site_translations', [
            'group' => 'sync', 'key' => 'hello', 'lang' => 'es',
        ]);

        unlink($scanDir . '/test.blade.php');
        rmdir($scanDir);
    }

    public function test_add_language(): void
    {
        $response = $this->postJson('/admin/translations/add-language', [
            'lang' => 'pt',
        ]);

        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'languages']);

        $langs = $response->json('languages');
        $this->assertContains('pt', $langs);
    }

    public function test_add_language_duplicate_returns_error(): void
    {
        $response = $this->postJson('/admin/translations/add-language', [
            'lang' => 'es',
        ]);

        $response->assertJson(['success' => false]);
    }

    public function test_remove_language(): void
    {
        $this->postJson('/admin/translations/add-language', ['lang' => 'pt']);

        $response = $this->postJson('/admin/translations/remove-language', [
            'lang' => 'pt',
        ]);

        $response->assertJson(['success' => true]);
    }

    public function test_remove_source_language_fails(): void
    {
        $response = $this->postJson('/admin/translations/remove-language', [
            'lang' => 'es',
        ]);

        $response->assertJson(['success' => false]);
    }

    public function test_coverage_stats(): void
    {
        $this->seedTranslations();

        $response = $this->getJson('/admin/translations/coverage-stats');

        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'stats', 'total_keys']);
    }

    public function test_index_renders_with_standalone_layout(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        // Verify standalone layout elements
        $response->assertSee('csrf-token', false);
        $response->assertSee('tailwindcss', false);
    }

    public function test_index_has_csrf_meta_tag(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        // The standalone layout has CSRF meta tag
        $response->assertSee('name="csrf-token"', false);
    }

    public function test_index_has_inline_javascript(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        // JS should be inline, not in @push
        $response->assertSee('function fj(', false);
        $response->assertSee('function showToast(', false);
    }
}
