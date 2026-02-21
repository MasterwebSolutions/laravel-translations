<?php

namespace Masterweb\Translations\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationMemory extends Model
{
    protected $table = 'translation_memories';

    protected $fillable = [
        'source_lang', 'target_lang', 'source_text', 'target_text',
        'source_hash', 'context', 'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    /**
     * Find exact match in translation memory.
     */
    public static function findExact(string $sourceText, string $sourceLang, string $targetLang): ?self
    {
        $hash = hash('sha256', mb_strtolower(trim($sourceText)));
        $match = self::where('source_hash', $hash)
            ->where('source_lang', $sourceLang)
            ->where('target_lang', $targetLang)
            ->first();

        if ($match) {
            $match->increment('usage_count');
        }

        return $match;
    }

    /**
     * Store a translation in memory for future reuse.
     */
    public static function remember(string $sourceText, string $targetText, string $sourceLang, string $targetLang, ?string $context = null): self
    {
        $hash = hash('sha256', mb_strtolower(trim($sourceText)));

        return self::updateOrCreate(
            ['source_hash' => $hash, 'source_lang' => $sourceLang, 'target_lang' => $targetLang],
            ['source_text' => $sourceText, 'target_text' => $targetText, 'context' => $context]
        );
    }

    /**
     * Get stats for the translation memory.
     */
    public static function getStats(): array
    {
        return [
            'total_entries' => self::count(),
            'total_reuses' => (int) self::sum('usage_count') - self::count(),
            'language_pairs' => self::selectRaw('source_lang, target_lang, COUNT(*) as cnt')
                ->groupBy('source_lang', 'target_lang')
                ->get()
                ->toArray(),
        ];
    }
}
