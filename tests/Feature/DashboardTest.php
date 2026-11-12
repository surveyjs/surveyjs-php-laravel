<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_date_ranges_include_the_entire_end_date(): void
    {
        foreach (['2020-01-01T00:00:00Z', '2020-01-31T23:59:59Z', '2020-02-01T00:00:00Z'] as $index => $date) {
            DB::table('responses')->insert(['form_id' => 'feedback', 'data' => json_encode(['satisfaction' => $index + 1]), 'created_at' => $date]);
        }
        $query = 'from=2020-01-01&to=2020-01-31';
        $this->getJson('/api/responses?formId=feedback&'.$query)->assertOk()->assertExactJson([
            ['satisfaction' => 1], ['satisfaction' => 2],
        ]);
    }

    public function test_dashboard_can_request_submission_dates_without_changing_the_stored_answers(): void
    {
        $data = '{"satisfaction":4,"createdAt":"an answer","empty":{},"score":1.0}';
        $date = '2020-01-31T23:59:59Z';
        DB::table('responses')->insert(['form_id' => 'dashboard-metadata', 'data' => $data, 'created_at' => $date]);

        $this->getJson('/api/responses?formId=dashboard-metadata&includeCreatedAt=1')
            ->assertOk()->assertExactJson([['data' => json_decode($data, true), 'createdAt' => $date]]);
        $this->getJson('/api/responses?formId=dashboard-metadata')->assertOk()->assertContent('['.$data.']');
        $this->assertDatabaseHas('responses', ['form_id' => 'dashboard-metadata', 'data' => $data]);
    }
}
