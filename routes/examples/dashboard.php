<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:dashboard.server
// GET /api/responses?formId=&includeCreatedAt=&from=&to=&limit=&offset=
Route::get('/api/responses', function (Request $request) {
    $documents = DB::table('responses')
        ->where('form_id', $request->query('formId'))
        ->where('created_at', '>=', $request->query('from', '1970-01-01'))   // ISO-8601 UTC strings compare as dates
        ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->query('to')))
        ->orderBy('created_at')
        ->limit(min(max((int) $request->query('limit', 500), 1), 5000))       // filter or page on the server for large volumes
        ->offset(max((int) $request->query('offset', 0), 0))
        ->get(['data', 'created_at']);

    // Include submission dates separately when requested by Dashboard
    if ($request->boolean('includeCreatedAt')) {
        return response()->json($documents->map(fn ($row) => [
            'data' => json_decode($row->data), 'createdAt' => $row->created_at,
        ]));
    }

    return response('['.$documents->pluck('data')->implode(',').']')         // documents as stored, not re-encoded
        ->header('Content-Type', 'application/json');
});
// #endregion
