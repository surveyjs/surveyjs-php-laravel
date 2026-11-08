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

    public function test_date_ranges_include_the_entire_end_date_and_match_the_data_panel(): void
    {
        foreach (['2020-01-01T00:00:00Z', '2020-01-31T23:59:59Z', '2020-02-01T00:00:00Z'] as $index => $date) {
            DB::table('responses')->insert(['form_id' => 'feedback', 'data' => json_encode(['satisfaction' => $index + 1]), 'created_at' => $date]);
        }
        $query = 'from=2020-01-01&to=2020-01-31';
        $this->getJson('/api/responses?formId=feedback&'.$query)->assertOk()->assertExactJson([
            ['satisfaction' => 1], ['satisfaction' => 2],
        ]);
        $this->getJson('/demo/stored/dashboard?'.$query)->assertOk()->assertJsonPath('in date range', 2);
    }
}
