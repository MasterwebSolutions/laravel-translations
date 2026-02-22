<?php

namespace Masterweb\Translations\Tests;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Models\TranslationSetting;
use Masterweb\Translations\TranslationsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->runMigrations();

        // Clear static caches to prevent state leaks between tests
        TranslationSetting::clearCache();
        SiteTranslation::clearCache();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TranslationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('translations.source_language', 'es');
        $app['config']->set('translations.available_languages', ['es', 'en']);
        $app['config']->set('translations.admin_layout', 'translations::layouts.standalone');
        $app['config']->set('translations.content_section', 'content');
        $app['config']->set('translations.route_name_prefix', 'translations');
        $app['config']->set('translations.admin_prefix', 'admin/translations');
        $app['config']->set('translations.admin_middleware', ['web']);
        $app['config']->set('translations.ai_enabled', false);
        $app['config']->set('translations.cache_ttl', 0);
        $app['config']->set('translations.health_check', true);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function runMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function seedTranslations(): void
    {
        \Masterweb\Translations\Models\SiteTranslation::insert([
            ['group' => 'nav', 'key' => 'home', 'lang' => 'es', 'value' => 'Inicio', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'nav', 'key' => 'home', 'lang' => 'en', 'value' => 'Home', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'nav', 'key' => 'about', 'lang' => 'es', 'value' => 'Acerca de', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'nav', 'key' => 'about', 'lang' => 'en', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'footer', 'key' => 'copyright', 'lang' => 'es', 'value' => '© 2026 Mi Empresa', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'footer', 'key' => 'copyright', 'lang' => 'en', 'value' => '© 2026 My Company', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function createAuthUser()
    {
        // For routes that require auth, we simulate by removing auth middleware
        // Orchestra Testbench handles this via withoutMiddleware or actingAs
    }
}
