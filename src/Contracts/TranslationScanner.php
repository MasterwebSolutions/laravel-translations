<?php

namespace Masterweb\Translations\Contracts;

interface TranslationScanner
{
    /**
     * Scan and return translatable strings.
     *
     * Each item should be an array with keys:
     *   - 'group' => string (e.g., 'products', 'categories')
     *   - 'key'   => string (e.g., 'product_15_name')
     *   - 'fallback' => string (the source text)
     *
     * @return array<int, array{group: string, key: string, fallback: string}>
     */
    public function scan(): array;
}
