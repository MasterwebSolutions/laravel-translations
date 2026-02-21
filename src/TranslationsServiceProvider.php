<?php

namespace Masterweb\Translations;

use Illuminate\Support\ServiceProvider;
use Masterweb\Translations\Console\MemorySyncCommand;

class TranslationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translations.php', 'translations');

        $this->app->singleton(TranslationManager::class, fn() => new TranslationManager());
        $this->app->singleton(Services\AiTranslator::class, fn() => new Services\AiTranslator());
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load views with namespace 'translations::'
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'translations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MemorySyncCommand::class,
            ]);

            // Publishable assets
            $this->publishes([
                __DIR__ . '/../config/translations.php' => config_path('translations.php'),
            ], 'translations-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'translations-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/translations'),
            ], 'translations-views');
        }
    }
}
