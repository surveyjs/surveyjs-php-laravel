<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// #region sjs:save-response.server
// POST /api/responses: Store submitted answers as one JSON document
// The middleware in bootstrap/app.php disables TrimStrings and ConvertEmptyStringsToNull to preserve spaces and empty strings
Route::post('/api/responses', function (Request $request) {
    // Read the raw request body to prevent PHP arrays from turning {} into [] in stored JSON
    $body = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    abort_unless(isset($body->formId, $body->data), 400, 'Expected { formId, data }');

    $id = StoredJson::insertResponse($body->formId, $body->data);   // Preserve float values such as 1.0

    return response()->json(['id' => $id], 201);
});
// #endregion
