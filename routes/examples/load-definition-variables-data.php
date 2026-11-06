<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:load-definition-variables-data.server
// Option A: Embed the schema and user variables in the HTML response
Route::get('/claim', function () {
    $definition = json_decode(file_get_contents(base_path('shared/definitions/load-definition-variables-data.json')));
    $user = Auth::user();
    $variables = ['user' => ['name' => $user?->name, 'plan' => $user?->plan]];

    return view('examples.load-definition-variables-data', compact('definition', 'variables'));   // Use Js::from() in the view to escape JSON for an inline script
});

// Option B: Return the definition, variables, and previous answers in one response
Route::get('/api/forms/{id}', function (Request $request, string $id) {
    $user = Auth::user();
    $definition = DB::table('forms')->where('key', $id)->value('json') ?? abort(404, 'Unknown form');
    $record = DB::table('responses')->where('id', $request->query('record'))
        ->when($id === 'claim-request', fn ($q) => $q->where('form_id', 'claim')->where('data->customer_email', $user?->email));
    $data = $request->filled('record') ? $record->value('data') : null;

    return StoredJson::response([
        'definition' => json_decode($definition),                                // Return the stored form definition
        'variables' => ['user' => ['name' => $user?->name, 'plan' => $user?->plan]],
        'data' => $data === null ? new stdClass : json_decode($data),           // Return previous answers, or {} when none are available
    ]);
});
// #endregion
