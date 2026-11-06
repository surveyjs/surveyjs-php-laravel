<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResumeProgressTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_a_missing_draft_returns_200_with_json_null(): void
    {
        $this->withUnencryptedCookie('demo_user', 'alice')->getJson('/api/progress/onboarding')
            ->assertOk()->assertHeader('Content-Type', 'application/json')->assertContent('null');
    }

    public function test_drafts_are_separate_for_each_user_and_form(): void
    {
        $alice = ['data' => ['full_name' => 'Alice'], 'uiState' => ['activeElementName' => 'job_title']];
        $bob = ['data' => ['full_name' => 'Bob'], 'uiState' => ['currentPageName' => 'company-details']];
        $this->withUnencryptedCookie('demo_user', 'alice')->putJson('/api/progress/onboarding', $alice)->assertNoContent();
        $this->withUnencryptedCookie('demo_user', 'bob')->getJson('/api/progress/onboarding')->assertOk()->assertContent('null');
        $this->putJson('/api/progress/onboarding', $bob)->assertNoContent();
        $this->getJson('/api/progress/onboarding')->assertExactJson($bob);
        $this->getJson('/api/progress/another-form')->assertOk()->assertContent('null');
        $this->withUnencryptedCookie('demo_user', 'alice')->getJson('/api/progress/onboarding')->assertExactJson($alice);
        $this->getJson('/demo/stored/resume-progress')->assertJsonPath('progress.json', $alice);
    }

    public function test_clearing_one_users_draft_keeps_the_other_users_draft(): void
    {
        $draft = ['data' => ['full_name' => 'Saved'], 'uiState' => []];
        foreach (['alice', 'bob'] as $user) {
            $this->withUnencryptedCookie('demo_user', $user)->putJson('/api/progress/onboarding', $draft)->assertNoContent();
        }
        $this->withUnencryptedCookie('demo_user', 'alice')->deleteJson('/api/progress/onboarding')->assertNoContent();
        $this->getJson('/api/progress/onboarding')->assertOk()->assertContent('null');
        $this->deleteJson('/api/progress/onboarding')->assertNoContent();
        $this->withUnencryptedCookie('demo_user', 'bob')->getJson('/api/progress/onboarding')->assertExactJson($draft);
    }

    public function test_signed_out_requests_cannot_read_save_or_delete_a_draft(): void
    {
        $this->withUnencryptedCookie('demo_user', 'none');
        $this->getJson('/api/progress/onboarding')->assertUnauthorized();
        $this->putJson('/api/progress/onboarding', ['data' => [], 'uiState' => []])->assertUnauthorized();
        $this->deleteJson('/api/progress/onboarding')->assertUnauthorized();
    }

    public function test_draft_json_keeps_empty_objects_arrays_and_float_values(): void
    {
        $json = '{"data":{"emptyObject":{},"emptyArray":[],"amount":1.0,"text":"  naïve — 東京  "},"uiState":{}}';
        $this->withUnencryptedCookie('demo_user', 'alice')
            ->call('PUT', '/api/progress/onboarding', cookies: ['demo_user' => 'alice'], server: ['CONTENT_TYPE' => 'application/json'], content: $json)->assertNoContent();
        $this->getJson('/api/progress/onboarding')->assertContent($json);
    }

    public function test_the_legacy_draft_migration_preserves_existing_answers_without_replacing_a_newer_draft(): void
    {
        $alice = DB::table('users')->where('email', 'alice@example.com')->value('id');
        $legacy = '{"data":{"full_name":"Existing draft"},"uiState":{}}';
        DB::table('progress')->insert(['key' => 'onboarding', 'json' => $legacy]);
        $migration = require base_path('database/migrations/2026_11_06_000000_scope_existing_onboarding_progress.php');
        $migration->up();
        $this->assertDatabaseHas('progress', ['key' => $alice.':onboarding', 'json' => $legacy]);
        $this->assertDatabaseMissing('progress', ['key' => 'onboarding']);

        DB::table('progress')->insert(['key' => 'onboarding', 'json' => '{}']);
        $migration->up();
        $this->assertDatabaseHas('progress', ['key' => $alice.':onboarding', 'json' => $legacy]);
    }
}
