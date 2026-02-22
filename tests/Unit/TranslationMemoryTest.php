<?php

namespace Masterweb\Translations\Tests\Unit;

use Masterweb\Translations\Models\TranslationMemory;
use Masterweb\Translations\Tests\TestCase;

class TranslationMemoryTest extends TestCase
{
    public function test_remember_creates_memory_entry(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en', 'greeting');

        $this->assertDatabaseHas('translation_memories', [
            'source_lang' => 'es',
            'target_lang' => 'en',
            'source_text' => 'Hola',
            'target_text' => 'Hello',
            'context' => 'greeting',
        ]);
    }

    public function test_remember_stores_source_hash(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');

        $mem = TranslationMemory::first();
        $this->assertEquals(hash('sha256', mb_strtolower(trim('Hola'))), $mem->source_hash);
    }

    public function test_find_exact_returns_match(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');

        $result = TranslationMemory::findExact('Hola', 'es', 'en');

        $this->assertNotNull($result);
        $this->assertEquals('Hello', $result->target_text);
    }

    public function test_find_exact_returns_null_when_no_match(): void
    {
        $result = TranslationMemory::findExact('Nonexistent', 'es', 'en');

        $this->assertNull($result);
    }

    public function test_find_exact_increments_usage_count(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');

        $this->assertEquals(1, TranslationMemory::first()->usage_count);

        TranslationMemory::findExact('Hola', 'es', 'en');

        $this->assertEquals(2, TranslationMemory::first()->usage_count);
    }

    public function test_find_exact_respects_language_pair(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');

        // Same source text but different target language
        $result = TranslationMemory::findExact('Hola', 'es', 'fr');
        $this->assertNull($result);
    }

    public function test_get_stats_returns_correct_structure(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');
        TranslationMemory::remember('Adiós', 'Goodbye', 'es', 'en');
        TranslationMemory::remember('Hola', 'Olá', 'es', 'pt');

        $stats = TranslationMemory::getStats();

        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('total_reuses', $stats);
        $this->assertArrayHasKey('language_pairs', $stats);

        $this->assertEquals(3, $stats['total_entries']);
    }

    public function test_remember_updates_existing_entry(): void
    {
        TranslationMemory::remember('Hola', 'Hello', 'es', 'en');
        TranslationMemory::remember('Hola', 'Hi there', 'es', 'en');

        // Should update, not create duplicate
        $count = TranslationMemory::where('source_text', 'Hola')
            ->where('source_lang', 'es')
            ->where('target_lang', 'en')
            ->count();

        $this->assertEquals(1, $count);
    }
}
