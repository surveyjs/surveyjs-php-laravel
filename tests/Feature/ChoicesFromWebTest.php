<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChoicesFromWebTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_code_tab_keeps_the_completion_handler_hidden(): void
    {
        $html = $this->withoutVite()->get('/examples/choices-from-web')->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $this->assertStringContainsString('sjs:choices-from-web.client', $document->getElementById('panel-code')->textContent);
        $this->assertStringNotContainsString('onComplete', $document->getElementById('panel-code')->textContent);
    }

    public function test_completion_saves_choice_values_and_data_shows_the_selected_response(): void
    {
        $office = DB::table('offices')->where('region', 'europe')->first();
        $answers = ['region' => 'europe', 'office' => $office->id, 'country' => 'DE'];
        $this->postJson('/api/responses', ['formId' => 'contact', 'data' => ['email' => 'other@example.com']])->assertCreated();
        $saved = $this->postJson('/api/responses', ['formId' => 'visit', 'data' => $answers])->assertCreated();
        $id = $saved->json('id');

        $this->assertSame($answers, json_decode(DB::table('responses')->where('id', $id)->value('data'), true));
        $this->getJson('/demo/stored/choices-from-web?id='.$id)->assertOk()->assertJsonCount(1, 'responses')
            ->assertJsonPath('responses.0.id', $id)
            ->assertJsonPath('responses.0.form_id', 'visit')
            ->assertJsonPath('responses.0.data', $answers);
    }
}
