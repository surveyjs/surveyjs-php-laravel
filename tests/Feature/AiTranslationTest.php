<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_provider_results_are_checked_for_string_values_and_order(): void
    {
        config(['surveyjs.ai.api_key' => 'test-key', 'surveyjs.ai.base_url' => 'https://translator.test']);
        $strings = [];
        Http::fake(['translator.test/*' => function () use (&$strings) {
            return Http::response(['choices' => [['message' => ['content' => json_encode($strings)]]]]);
        }]);
        foreach ([['Bonjour', 'Merci'], ['Bonjour', 123]] as $strings) {
            $response = $this->postJson('/api/translate', ['strings' => ['Hello', 'Thanks'], 'from' => 'en', 'to' => 'fr']);
            if (is_string($strings[1])) {
                $response->assertOk()->assertExactJson($strings);
            } else {
                $response->assertStatus(502)->assertJsonPath('error', 'The AI provider returned an unexpected answer');
            }
        }
    }

    public function test_provider_failures_return_a_retryable_error(): void
    {
        config(['surveyjs.ai.api_key' => 'test-key', 'surveyjs.ai.base_url' => 'https://translator.test']);
        Http::fake(['translator.test/*' => Http::response(['error' => 'Unavailable'], 503)]);
        $this->postJson('/api/translate', ['strings' => ['Hello'], 'from' => 'en', 'to' => 'fr'])
            ->assertStatus(502)->assertJsonPath('error', 'The translation provider is unavailable. Please try again.');
    }
}
