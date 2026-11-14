<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VariablePresetsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_try_this_names_the_permission_check_that_the_save_route_runs(): void
    {
        $route = (string) file_get_contents(base_path('routes/examples/variable-presets.php'));
        $this->assertStringContainsString("Gate::authorize('edit-forms')", $route);

        $html = (string) $this->withoutVite()->get('/examples/variable-presets')->assertOk()->getContent();
        $try = substr($html, (int) strpos($html, 'id="try"'), 4000);
        $this->assertStringContainsString("<code>Gate::authorize('edit-forms')</code>", html_entity_decode($try, ENT_QUOTES));
        $this->assertStringContainsString('Load and Save Form JSON Schemas', $try);
    }

    public function test_an_unsaved_preset_list_returns_200_and_an_empty_array(): void
    {
        $this->getJson('/api/variable-presets/unsaved')->assertOk()->assertContent('[]');
    }

    public function test_preset_lists_round_trip_without_changing_empty_objects(): void
    {
        $this->actingAs(User::where('email', 'alice@example.com')->firstOrFail());
        $json = '[{"name":"premium","variables":{"customerTier":"premium","extra":{}}}]';
        $this->call('PUT', '/api/variable-presets/test-presets', server: ['CONTENT_TYPE' => 'application/json'], content: $json)->assertNoContent();
        $this->getJson('/api/variable-presets/test-presets')->assertOk()->assertContent($json);
    }

    public function test_viewers_cannot_change_saved_presets(): void
    {
        $before = DB::table('variable_presets')->where('key', 'support')->value('json');
        $this->actingAs(User::where('email', 'bob@example.com')->firstOrFail());
        $this->putJson('/api/variable-presets/support', [])->assertForbidden();
        $this->assertSame($before, DB::table('variable_presets')->where('key', 'support')->value('json'));
    }
}
