<?php

namespace Masterweb\Translations\Tests\Feature;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Models\TranslationMemory;
use Masterweb\Translations\Tests\TestCase;

class TranslationMemoryControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_memory_index_page_loads(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('Translation Memory');
    }

    public function test_memory_index_shows_stats(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');

        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('Total Entries');
    }

    public function test_store_memory(): void
    {
        $response = $this->postJson('/admin/translations/memory', [
            'source_lang' => 'es',
            'target_lang' => 'en',
            'source_text' => 'Hola',
            'target_text' => 'Hello',
            'context' => 'greeting',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('translation_memories', [
            'source_text' => 'Hola', 'target_text' => 'Hello',
        ]);
    }

    public function test_update_memory(): void
    {
        $mem = TranslationMemory::create([
            'source_lang' => 'es', 'target_lang' => 'en',
            'source_text' => 'Hola', 'target_text' => 'Hello',
            'source_hash' => hash('sha256', 'Hola'), 'usage_count' => 1,
        ]);

        $response = $this->putJson("/admin/translations/memory/{$mem->id}", [
            'target_text' => 'Hi there',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('translation_memories', [
            'id' => $mem->id, 'target_text' => 'Hi there',
        ]);
    }

    public function test_destroy_memory(): void
    {
        $mem = TranslationMemory::create([
            'source_lang' => 'es', 'target_lang' => 'en',
            'source_text' => 'Hola', 'target_text' => 'Hello',
            'source_hash' => hash('sha256', 'Hola'), 'usage_count' => 1,
        ]);

        $response = $this->deleteJson("/admin/translations/memory/{$mem->id}");

        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('translation_memories', ['id' => $mem->id]);
    }

    public function test_bulk_delete_memories(): void
    {
        $mem1 = TranslationMemory::create([
            'source_lang' => 'es', 'target_lang' => 'en',
            'source_text' => 'Hola', 'target_text' => 'Hello',
            'source_hash' => hash('sha256', 'Hola'), 'usage_count' => 1,
        ]);
        $mem2 = TranslationMemory::create([
            'source_lang' => 'es', 'target_lang' => 'en',
            'source_text' => 'Adiós', 'target_text' => 'Goodbye',
            'source_hash' => hash('sha256', 'Adiós'), 'usage_count' => 1,
        ]);

        $response = $this->postJson('/admin/translations/memory/bulk-delete', [
            'ids' => [$mem1->id, $mem2->id],
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseCount('translation_memories', 0);
    }

    public function test_purge_all_memories(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');
        TranslationMemory::remember('Adiós', 'Goodbye', 'es', 'en');

        $response = $this->postJson('/admin/translations/memory/purge');

        $response->assertJson(['success' => true]);
        $this->assertDatabaseCount('translation_memories', 0);
    }

    public function test_import_existing_translations(): void
    {
        // Create some source + target translations
        SiteTranslation::create(['group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio']);
        SiteTranslation::create(['group' => 'nav', 'key' => 'home', 'lang' => 'en', 'value' => 'Home']);

        $response = $this->postJson('/admin/translations/memory/import');

        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'imported', 'skipped']);
    }

    public function test_search_memories(): void
    {
        TranslationMemory::remember('Buenos días', 'Good morning', 'es', 'en');
        TranslationMemory::remember('Buenas noches', 'Good night', 'es', 'en');

        $response = $this->getJson('/admin/translations/memory/search?q=días');

        $response->assertStatus(200);
    }

    public function test_get_config(): void
    {
        $response = $this->getJson('/admin/translations/memory/config');

        $response->assertStatus(200);
        $response->assertJsonStructure(['auto_sync_enabled', 'sync_interval_hours']);
    }

    public function test_set_config(): void
    {
        $response = $this->postJson('/admin/translations/memory/config', [
            'auto_sync_enabled' => true,
            'sync_interval_hours' => 12,
        ]);

        $response->assertJson(['success' => true]);
    }

    public function test_memory_page_has_inline_javascript(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('function fj(', false);
        $response->assertSee('window.importExisting', false);
    }

    public function test_memory_page_has_csrf_meta(): void
    {
        $response = $this->get('/admin/translations/memory');

        $response->assertStatus(200);
        $response->assertSee('name="csrf-token"', false);
    }

    public function test_stats_endpoint(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');

        $response = $this->getJson('/admin/translations/memory/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure(['total_entries', 'total_reuses', 'language_pairs']);
    }
}
