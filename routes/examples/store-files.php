<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// #region sjs:store-files.server
// POST /api/files — store uploads, return their ids in the same order
Route::post('/api/files', function (Request $request) {
    $validation = Validator::make($request->all(), [
        'files' => 'required|array|min:1',
        'files.*' => 'required|file|max:5120|mimetypes:'
            .'image/png,image/jpeg,image/webp,image/gif,application/pdf',
    ]);
    abort_if($validation->fails(), 422, $validation->errors()->first());
    $ids = [];
    // Field "files[]": PHP keeps only the last of several "files"
    foreach ($request->file('files') as $file) {
        abort_unless($file->isValid(), 400, $file->getErrorMessage());
        $id = (string) Str::uuid();
        // The "uploads" disk: local, S3…; named by id, never by the client
        $file->storeAs('', $id, 'uploads');
        DB::table('files')->insert([
            'id' => $id, 'name' => $file->getClientOriginalName(), 'type' => $file->getMimeType(),
        ]);
        $ids[] = $id;
    }

    return response()->json($ids);
});
// #endregion

// #region sjs:store-files.download
// GET /api/files/{id} — the content, for private files only; check access first
Route::get('/api/files/{id}', function (string $id) {
    $file = DB::table('files')->find($id);
    abort_if(! $file || Gate::denies('read-file', $file), 404);   // 404, not 403: don't confirm the file exists

    return Storage::disk('uploads')->response($id, $file->name, [   // nosniff and sandbox: an uploaded HTML or SVG never runs as this site
        'Content-Type' => $file->type, 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox',
    ]);
});
// #endregion
