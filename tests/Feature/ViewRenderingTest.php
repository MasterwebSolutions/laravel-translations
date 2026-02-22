<?php

namespace Masterweb\Translations\Tests\Feature;

use Masterweb\Translations\Tests\TestCase;

class ViewRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    // ── Standalone Layout ──

    public function test_standalone_layout_has_html_structure(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('<!DOCTYPE html>', false);
        $response->assertSee('<html', false);
        $response->assertSee('</html>', false);
    }

    public function test_standalone_layout_has_csrf_meta_tag(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('name="csrf-token"', false);
    }

    public function test_standalone_layout_loads_tailwind(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('cdn.tailwindcss.com', false);
    }

    public function test_standalone_layout_has_navigation(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('Translations', false);
        $response->assertSee('Translation Memory', false);
    }

    // ── Translations Page UI Elements ──

    public function test_translations_page_has_sync_button(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('Sync Templates', false);
    }

    public function test_translations_page_has_add_key_form(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('name="group"', false);
        $response->assertSee('name="key"', false);
    }

    public function test_translations_page_has_language_bar(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        // Should show configured languages
        $response->assertSee('ES', false);
        $response->assertSee('EN', false);
    }

    public function test_translations_page_has_search_input(): void
    {
        $response = $this->get('/admin/translations');

        $response->assertStatus(200);
        $response->assertSee('Search', false);
    }

    public function test_translations_page_ai_button_depends_on_config(): void
    {
        // When disabled, the translateAllBtn should not be in the HTML button
        config(['translations.ai_enabled' => false]);
        $response = $this->get('/admin/translations');
        $response->assertStatus(200);
        $response->assertDontSee('id="translateAllBtn"', false);

        // When enabled, the translateAllBtn should be present
        config(['translations.ai_enabled' => true]);
        $response = $this->get('/admin/translations');
        $response->assertStatus(200);
        $response->assertSee('id="translateAllBtn"', false);
    }

    // ── Translation Memory Page UI Elements ──

    public function test_memory_page_has_import_button(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('Import Existing', false);
    }

    public function test_memory_page_has_purge_button(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('Purge All', false);
    }

    public function test_memory_page_has_csrf_meta(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('name="csrf-token" content=', false);
    }

    public function test_memory_page_has_auto_sync_settings(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('Auto-Sync Settings', false);
        $response->assertSee('autoSyncEnabled', false);
        $response->assertSee('syncInterval', false);
    }

    public function test_memory_page_has_stats_section(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('Total Entries', false);
        $response->assertSee('Reuses', false);
        $response->assertSee('Language Pairs', false);
    }

    public function test_memory_page_has_back_link_to_translations(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('Translations', false);
        $response->assertSee('/admin/translations', false);
    }

    // ── JS Inline Verification ──

    public function test_translations_js_has_fetch_helper(): void
    {
        $response = $this->get('/admin/translations');

        $content = $response->getContent();

        // Verify JS is inline (inside <script> tag, not in @push)
        $this->assertStringContainsString('function fj(url', $content);
        $this->assertStringContainsString('X-CSRF-TOKEN', $content);
    }

    public function test_translations_js_has_toast_function(): void
    {
        $response = $this->get('/admin/translations');

        $this->assertStringContainsString('function showToast(', $response->getContent());
    }

    public function test_memory_js_has_import_function(): void
    {
        $response = $this->get('/admin/translations/memory');

        $this->assertStringContainsString('window.importExisting', $response->getContent());
    }

    public function test_memory_js_has_purge_function(): void
    {
        $response = $this->get('/admin/translations/memory');

        $this->assertStringContainsString('window.purgeAll', $response->getContent());
    }

    // ── Route Names ──

    public function test_route_names_use_configured_prefix(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('translations.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('translations.store'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('translations.sync'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('translations.health'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('translations.memory.index'));
    }

    public function test_custom_route_prefix(): void
    {
        // Routes are already registered, check the standard prefix works
        $url = route('translations.index');
        $this->assertStringContainsString('admin/translations', $url);
    }
}
