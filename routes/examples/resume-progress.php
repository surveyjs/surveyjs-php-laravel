<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:resume-progress.server
// Return the current user's saved answers and UI state
Route::get('/api/progress/{formId}', function (string $formId) {
    $key = (Auth::id() ?? abort(401, 'Sign in to load progress')).':'.$formId;
    $json = DB::table('progress')->where('key', $key)->value('json');

    return response($json ?? 'null')->header('Content-Type', 'application/json');
});

// Store progress under a key containing both the user ID and form ID
Route::put('/api/progress/{formId}', function (Request $request, string $formId) {
    $key = (Auth::id() ?? abort(401, 'Sign in to save progress')).':'.$formId;
    // Read the raw request body to prevent PHP arrays from turning {} into [] in stored JSON
    $progress = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    StoredJson::put('progress', $key, $progress);   // Preserve float values such as 1.0

    return response()->noContent();
});
// #endregion

// #region sjs:resume-progress.delete
// Remove only the current user's draft
Route::delete('/api/progress/{formId}', function (string $formId) {
    $key = (Auth::id() ?? abort(401, 'Sign in to clear progress')).':'.$formId;
    DB::table('progress')->where('key', $key)->delete();

    return response()->noContent();
});
// #endregion
