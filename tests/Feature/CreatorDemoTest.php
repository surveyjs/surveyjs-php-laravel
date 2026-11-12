<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CreatorDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreatorDemoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_upload_sample_is_independent_and_reseeding_preserves_edits(): void
    {
        $support = DB::table('forms')->where('key', 'support')->value('json');
        $edited = '{"title":"Edited upload sample","elements":[]}';
        DB::table('forms')->where('key', 'support-assets')->update(['json' => $edited]);
        $this->seed(CreatorDemoSeeder::class);
        $this->assertSame($edited, DB::table('forms')->where('key', 'support-assets')->value('json'));
        $this->assertSame($support, DB::table('forms')->where('key', 'support')->value('json'));
        $this->getJson('/demo/stored/creator-uploads')->assertOk()->assertJsonPath('forms.key', 'support-assets');
        $this->get('/shared/samples/support-help.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_collaborative_editing_has_an_independent_saved_schema(): void
    {
        $this->assertNotNull(DB::table('forms')->where('key', 'support-team')->value('json'));
        DB::table('forms')->where('key', 'support-team')->update(['json' => '{"title":"Team edits"}']);
        $this->seed(CreatorDemoSeeder::class);
        $this->getJson('/demo/stored/edit-together')->assertOk()->assertJsonPath('forms.json.title', 'Team edits');
        $this->assertNotSame('{"title":"Team edits"}', DB::table('forms')->where('key', 'support')->value('json'));
    }

    public function test_collaborative_form_choices_and_stored_data_follow_the_selected_form(): void
    {
        $this->assertNotNull(DB::table('forms')->where('key', 'contact-team')->value('json'));
        DB::table('forms')->where('key', 'contact-team')->update(['json' => '{"title":"Edited contact form"}']);
        DB::table('forms')->insert(['key' => 'team-created', 'json' => '{"title":{"default":"A new team form"}}']);
        $this->seed(CreatorDemoSeeder::class);

        $this->withoutVite()->get('/examples/edit-together?form=contact-team')->assertOk()
            ->assertSee('Edited contact form')->assertSee('A new team form')
            ->assertDontSee('value="support-assets"', false)
            ->assertDontSee('data-show="connection-status"', false)
            ->assertDontSee('data-show="schema-status"', false);
        $this->getJson('/demo/stored/edit-together?key=contact-team')->assertOk()
            ->assertJsonPath('forms.key', 'contact-team')->assertJsonPath('forms.json.title', 'Edited contact form');
        $this->getJson('/demo/stored/edit-together?key=team-created')->assertOk()
            ->assertJsonPath('forms.json.title.default', 'A new team form');
    }

    public function test_invalid_collaborative_form_ids_are_rejected(): void
    {
        $this->withoutVite()->get('/examples/edit-together?form[]=support-team')->assertBadRequest();
        $this->get('/examples/edit-together?form=invalid/path')->assertBadRequest();
    }
}
