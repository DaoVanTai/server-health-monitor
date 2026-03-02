<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServerMonitorControllerTest extends TestCase
{
    public function test_monitor_page_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_monitor_page_contains_required_view_data(): void
    {
        $response = $this->get('/');

        $response->assertViewHasAll(['ram', 'cpu', 'disk', 'total_ram', 'warnings', 'server_ip']);
    }

    public function test_monitor_page_returns_numeric_metrics(): void
    {
        $response = $this->get('/');

        $data = $response->viewData('ram');
        $this->assertIsNumeric($data);

        $data = $response->viewData('cpu');
        $this->assertIsNumeric($data);

        $data = $response->viewData('disk');
        $this->assertIsNumeric($data);
    }

    public function test_monitor_page_returns_warnings_as_array(): void
    {
        $response = $this->get('/');

        $warnings = $response->viewData('warnings');
        $this->assertIsArray($warnings);
    }

    public function test_monitor_page_renders_monitor_view(): void
    {
        $response = $this->get('/');

        $response->assertViewIs('monitor');
    }

    public function test_monitor_page_contains_dashboard_text(): void
    {
        $response = $this->get('/');

        $response->assertSee('SERVER HEALTH MONITORING');
    }

    public function test_ram_percentage_is_within_valid_range(): void
    {
        $response = $this->get('/');

        $ram = $response->viewData('ram');
        $this->assertGreaterThanOrEqual(0, $ram);
        $this->assertLessThanOrEqual(100, $ram);
    }

    public function test_disk_percentage_is_within_valid_range(): void
    {
        $response = $this->get('/');

        $disk = $response->viewData('disk');
        $this->assertGreaterThanOrEqual(0, $disk);
        $this->assertLessThanOrEqual(100, $disk);
    }

    public function test_monitoring_thresholds_config_exists(): void
    {
        $thresholds = config('monitoring.thresholds');

        $this->assertNotNull($thresholds);
        $this->assertArrayHasKey('ram', $thresholds);
        $this->assertArrayHasKey('disk', $thresholds);
        $this->assertArrayHasKey('cpu', $thresholds);
    }
}
