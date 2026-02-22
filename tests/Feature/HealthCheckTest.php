<?php

namespace Masterweb\Translations\Tests\Feature;

use Masterweb\Translations\Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_ok_when_all_good(): void
    {
        $response = $this->getJson('/admin/translations/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'package',
            'checks' => [
                'table_site_translations',
                'table_translation_memories',
                'table_translation_settings',
                'table_ai_usage_logs',
                'config_loaded',
                'layout_configured',
                'layout_exists',
                'ai_enabled',
                'ai_key_set',
                't_function_available',
            ],
            'admin_url',
        ]);
        $response->assertJson([
            'status' => 'ok',
            'package' => 'masterweb/laravel-translations',
            'checks' => [
                'table_site_translations' => true,
                'table_translation_settings' => true,
                'config_loaded' => true,
                'layout_exists' => true,
                't_function_available' => true,
            ],
        ]);
    }

    public function test_health_check_reports_missing_tables(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('site_translations');

        $response = $this->getJson('/admin/translations/health');

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'issues_found',
            'checks' => [
                'table_site_translations' => false,
            ],
        ]);
    }

    public function test_health_check_can_be_disabled(): void
    {
        config(['translations.health_check' => false]);

        $response = $this->getJson('/admin/translations/health');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Health check disabled']);
    }

    public function test_health_check_does_not_require_auth(): void
    {
        // Should work without authentication
        $response = $this->getJson('/admin/translations/health');

        $response->assertStatus(200);
    }
}
