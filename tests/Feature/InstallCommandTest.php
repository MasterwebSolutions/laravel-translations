<?php

namespace Masterweb\Translations\Tests\Feature;

use Masterweb\Translations\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    public function test_install_command_exists(): void
    {
        $this->artisan('translations:install', ['--no-interaction' => true])
            ->assertExitCode(0);
    }

    public function test_install_command_runs_without_errors(): void
    {
        // Just verify the command runs to completion without exceptions
        $this->artisan('translations:install', ['--no-interaction' => true])
            ->assertExitCode(0);
    }

    public function test_install_command_accepts_layout_option(): void
    {
        $this->artisan('translations:install', [
            '--layout' => 'layouts.admin',
            '--no-interaction' => true,
        ])->assertExitCode(0);
    }

    public function test_memory_sync_command_exists(): void
    {
        $this->artisan('translations:memory-sync', ['--force' => true])
            ->assertExitCode(0);
    }
}
