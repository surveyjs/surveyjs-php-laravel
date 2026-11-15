<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * shared/ is published from this repository: `surveyjs:check --write-sums` rewrites shared/SHA256SUMS here
 * (UPSTREAM "source: …", or "draft: …" before publishing) and refuses in a repository that copied shared/.
 */
class SurveyJsCheckTest extends TestCase
{
    private string $upstream;

    private string $sums;

    protected function setUp(): void
    {
        parent::setUp();
        $this->upstream = (string) file_get_contents(base_path('shared/UPSTREAM'));
        $this->sums = (string) file_get_contents(base_path('shared/SHA256SUMS'));
    }

    protected function tearDown(): void
    {
        file_put_contents(base_path('shared/UPSTREAM'), $this->upstream);
        file_put_contents(base_path('shared/SHA256SUMS'), $this->sums);
        parent::tearDown();
    }

    public function test_shared_is_published_from_this_repository(): void
    {
        $this->assertSame("source: surveyjs/surveyjs-php-laravel\n", $this->upstream);
    }

    public static function sourceUpstreams(): array
    {
        return ['published' => ['source: surveyjs/surveyjs-php-laravel'], 'draft' => ['draft: created in surveyjs-php']];
    }

    #[DataProvider('sourceUpstreams')]
    public function test_write_sums_rewrites_the_sums_in_the_source_repository(string $upstream): void
    {
        file_put_contents(base_path('shared/UPSTREAM'), $upstream."\n");
        file_put_contents(base_path('shared/SHA256SUMS'), '');

        $this->artisan('surveyjs:check', ['--write-sums' => true])->expectsOutputToContain('Wrote shared/SHA256SUMS')->assertSuccessful();
        $this->assertSame($this->sums, file_get_contents(base_path('shared/SHA256SUMS')));
    }

    public function test_write_sums_refuses_in_a_repository_that_copied_shared(): void
    {
        file_put_contents(base_path('shared/UPSTREAM'), "surveyjs/surveyjs-php-laravel@0123abc\n");

        $this->artisan('surveyjs:check', ['--write-sums' => true])
            ->expectsOutputToContain('shared/ is a copy of surveyjs/surveyjs-php-laravel@0123abc: change it in the source repository, not here')
            ->assertFailed();
        $this->assertSame($this->sums, file_get_contents(base_path('shared/SHA256SUMS')));
    }
}
