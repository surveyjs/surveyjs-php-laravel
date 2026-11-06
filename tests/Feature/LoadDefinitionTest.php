<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\LoadDefinitionDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoadDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_the_embedded_schema_includes_the_customers_variables_for_personalization(): void
    {
        $this->withoutVite()->get('/claim')->assertOk()
            ->assertSee('window.FORM_DEFINITION', false)
            ->assertSee('window.FORM_VARIABLES', false)
            ->assertViewHas('variables', fn ($variables) => $variables['user'] === ['name' => 'Alice Martin', 'plan' => 'premium'])
            ->assertSee('Customer');

        $this->withUnencryptedCookie('demo_user', 'bob')->get('/claim')->assertOk()
            ->assertViewHas('variables', fn ($variables) => $variables['user'] === ['name' => 'Bob Fischer', 'plan' => 'basic']);
    }

    public function test_the_api_returns_each_customers_variables_and_saved_answers(): void
    {
        foreach (['alice' => 'premium', 'bob' => 'basic'] as $customer => $plan) {
            $record = DB::table('responses')->where('data->customer_email', "{$customer}@example.com")->first();
            $response = $this->withUnencryptedCookie('demo_user', $customer)
                ->getJson("/api/forms/claim-request?record={$record->id}")->assertOk();

            $response->assertJsonPath('definition.completeText', 'Submit claim')
                ->assertJsonPath('variables.user.plan', $plan)
                ->assertJsonPath('data.customer_email', "{$customer}@example.com");
            $this->assertEquals(json_decode($record->data, true), $response->json('data'));
        }
    }

    public function test_the_record_picker_and_data_panel_only_show_the_selected_customers_responses(): void
    {
        $alice = DB::table('responses')->where('data->customer_email', 'alice@example.com')->value('id');
        $bob = DB::table('responses')->where('data->customer_email', 'bob@example.com')->value('id');
        $this->withoutVite()->withUnencryptedCookie('demo_user', 'bob')
            ->get('/examples/load-definition-variables-data')->assertOk()
            ->assertSee('value="'.$bob.'"', false)
            ->assertDontSee('value="'.$alice.'"', false);

        $rows = $this->withUnencryptedCookie('demo_user', 'bob')
            ->getJson('/demo/stored/load-definition-variables-data')->assertOk()->json('responses');
        $this->assertNotEmpty($rows);
        $this->assertSame(['bob@example.com'], array_unique(array_column(array_column($rows, 'data'), 'customer_email')));

        $this->withUnencryptedCookie('demo_user', 'bob')->getJson("/api/forms/claim-request?record={$alice}")
            ->assertOk()->assertJsonPath('data', []);
    }

    public function test_submitting_prefilled_answers_creates_a_new_response_and_keeps_the_original(): void
    {
        $original = DB::table('responses')->where('data->customer_email', 'alice@example.com')->first();
        $answers = json_decode($original->data, true);
        $answers['details'] = 'Additional information for a new submission.';
        $answers['customer_email'] = 'visitor@example.com';

        $id = $this->withUnencryptedCookie('demo_user', 'alice')
            ->postJson('/api/responses', ['formId' => 'claim', 'data' => $answers])
            ->assertCreated()->json('id');

        $this->assertNotSame($original->id, $id);
        $this->assertSame($original->data, DB::table('responses')->where('id', $original->id)->value('data'));
        $this->assertDatabaseHas('responses', ['id' => $id, 'data->details' => $answers['details']]);
        $this->getJson("/demo/stored/load-definition-variables-data?id={$id}")
            ->assertOk()->assertJsonPath('responses.0.data.customer_email', 'visitor@example.com');
    }

    public function test_the_demo_seeder_can_be_repeated_without_duplicate_responses(): void
    {
        $before = DB::table('responses')->count();
        $this->seed(LoadDefinitionDemoSeeder::class);
        $this->assertSame($before, DB::table('responses')->count());
    }
}
