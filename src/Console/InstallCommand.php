<?php

namespace Masterweb\Translations\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'translations:install
                            {--layout= : Your app\'s Blade layout (e.g. layouts.admin)}
                            {--section=content : The @yield section name in your layout}
                            {--middleware=web,auth : Comma-separated middleware for admin routes}
                            {--prefix=admin/translations : URL prefix for admin routes}';

    protected $description = 'Install and configure the Translations package';

    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════════╗');
        $this->info('  ║   Laravel Translations - Installer   ║');
        $this->info('  ╚══════════════════════════════════════╝');
        $this->info('');

        // Step 1: Publish config
        $this->task('Publishing config', function () {
            $this->callSilently('vendor:publish', [
                '--tag' => 'translations-config',
                '--force' => false,
            ]);
            return true;
        });

        // Step 2: Run migrations
        $this->task('Running migrations', function () {
            try {
                $this->callSilently('migrate', ['--force' => false]);
                return true;
            } catch (\Throwable $e) {
                $this->warn("  Migration warning: {$e->getMessage()}");
                return false;
            }
        });

        // Step 3: Verify tables
        $this->info('');
        $this->info('  Checking database tables...');
        $tables = [
            'site_translations',
            'translation_memories',
            'translation_settings',
            'ai_usage_logs',
        ];
        $allOk = true;
        foreach ($tables as $table) {
            $exists = Schema::hasTable($table);
            $icon = $exists ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line("    {$icon} {$table}");
            if (!$exists) $allOk = false;
        }

        if (!$allOk) {
            $this->warn('');
            $this->warn('  Some tables are missing. Run: php artisan migrate');
        }

        // Step 4: Layout configuration
        $layout = $this->option('layout');
        if (!$layout && $this->input->isInteractive()) {
            $this->info('');
            $this->info('  The admin panel needs a Blade layout to render inside.');
            $this->info('  Common options: layouts.app, layouts.admin, adminlte::page');
            $this->info('  Leave empty to use the built-in standalone layout.');
            $layout = $this->ask('  Your layout', '');
        }

        if ($layout) {
            $this->updateConfig('admin_layout', $layout);
            $this->info("  Layout set to: {$layout}");

            $section = $this->option('section') ?? 'content';
            $this->updateConfig('content_section', $section);

            $this->info('');
            $this->warn('  IMPORTANT: Your layout MUST have:');
            $this->line("    1. <meta name=\"csrf-token\" content=\"{{ csrf_token() }}\"> in <head>");
            $this->line("    2. @yield('{$section}') where the content should appear");
            $this->line('    3. TailwindCSS loaded (or publish views to customize)');
        } else {
            $this->info('  Using built-in standalone layout (works out of the box).');
        }

        // Step 5: Summary
        $prefix = config('translations.admin_prefix', 'admin/translations');
        $this->info('');
        $this->info('  ┌─────────────────────────────────────────┐');
        $this->info('  │  Installation complete!                  │');
        $this->info('  └─────────────────────────────────────────┘');
        $this->info('');
        $this->line("  Admin panel: <fg=cyan>/{$prefix}</>");
        $this->line("  Memory page: <fg=cyan>/{$prefix}/memory</>");
        $this->line("  Health check: <fg=cyan>/{$prefix}/health</>");
        $this->info('');
        $this->info('  Quick start:');
        $this->line('    1. Visit the admin panel URL above');
        $this->line('    2. Use {{ t(\'group.key\', \'Fallback text\') }} in Blade templates');
        $this->line('    3. Click "Sync Templates" in admin to detect t() calls');
        $this->info('');
        $this->info('  Optional - Add to your navigation:');
        $this->line('    <x-translations-menu-link />');
        $this->info('');

        return 0;
    }

    private function task(string $title, callable $callback): void
    {
        $this->output->write("  {$title}...");
        $result = $callback();
        $this->output->writeln($result ? ' <fg=green>done</>' : ' <fg=yellow>warning</>');
    }

    private function updateConfig(string $key, string $value): void
    {
        $configPath = config_path('translations.php');
        if (!file_exists($configPath)) return;

        $content = file_get_contents($configPath);
        $pattern = "/'{$key}'\s*=>\s*'[^']*'/";
        $replacement = "'{$key}' => '{$value}'";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            file_put_contents($configPath, $content);
        }
    }
}
