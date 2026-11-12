<?php

declare(strict_types=1);

use App\Integration\SurveyJsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:server-pdf.server
// GET /api/claims/{id}/pdf — ask the SurveyJS service for the filled form and pass the PDF through
Route::get('/api/claims/{id}/pdf', function (string $id) {
    $claim = DB::table('responses')->where('form_id', 'claim')->where('id', $id)->first() ?? abort(404, 'Unknown claim');
    // Use the schema version recorded with the response
    $definition = match ($claim->definition_version) {
        'v1', 'v2' => file_get_contents(base_path("shared/definitions/relational-storage.{$claim->definition_version}.json")),
        null => DB::table('forms')->where('key', 'claim')->value('json'),
        default => abort(409, 'The schema version for this claim is unavailable'),
    };
    $pdf = SurveyJsService::post('/pdf', ['schema' => json_decode($definition), 'response' => json_decode($claim->data)], timeout: 60);
    if ($pdf->status() !== 200 || ! str_starts_with($pdf->header('Content-Type'), 'application/pdf') || ! str_starts_with($pdf->body(), '%PDF-')) {
        SurveyJsService::unexpected('/pdf', $pdf);
    }

    return response($pdf->body(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"claim-{$id}.pdf\"",
        'Cache-Control' => 'private, no-store',
    ]);
});
// #endregion
