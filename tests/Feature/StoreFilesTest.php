<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreFilesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_an_uploaded_html_file_never_runs_as_this_site(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::where('email', 'alice@example.com')->firstOrFail());
        $id = 'legacy-html';
        Storage::disk('uploads')->put($id, '<!doctype html><script>alert(document.cookie)</script>');
        DB::table('files')->insert(['id' => $id, 'name' => 'page.html', 'type' => 'text/html']);

        $this->get("/api/files/{$id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', 'sandbox');
    }

    public function test_images_and_pdfs_are_stored_separately_and_can_be_downloaded(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::where('email', 'alice@example.com')->firstOrFail());
        $png = UploadedFile::fake()->createWithContent('receipt.png', file_get_contents(base_path('shared/samples/work-order-scan.png')));
        $pdf = UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n%%EOF\n");
        $ids = $this->post('/api/files', ['files' => [$png, $pdf]], ['Accept' => 'application/json'])->assertOk()->json();

        $this->assertCount(2, $ids);
        $this->assertDatabaseCount('files', 2);
        $this->get('/api/files/'.$ids[0])->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/api/files/'.$ids[1])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_the_entire_batch_is_validated_before_any_file_is_saved(): void
    {
        Storage::fake('uploads');
        $png = UploadedFile::fake()->createWithContent('receipt.png', file_get_contents(base_path('shared/samples/work-order-scan.png')));
        $html = UploadedFile::fake()->createWithContent('receipt.png', '<!doctype html><script>alert(1)</script>')->mimeType('text/html');
        $this->post('/api/files', ['files' => [$png, $html]], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonStructure(['error']);
        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_files_larger_than_five_megabytes_are_rejected(): void
    {
        Storage::fake('uploads');
        $file = UploadedFile::fake()->create('receipt.pdf', 5121, 'application/pdf');
        $this->post('/api/files', ['files' => [$file]], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonStructure(['error']);
        $this->assertDatabaseCount('files', 0);
    }
}
