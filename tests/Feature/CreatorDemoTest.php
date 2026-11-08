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
}
