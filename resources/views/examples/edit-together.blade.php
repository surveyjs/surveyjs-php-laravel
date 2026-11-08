@extends('layouts.example')

@section('description')
    <p>Let multiple authors work on the same form in real time. Participants see each other's edits and cursors, with elements locked during editing to prevent conflicting changes.</p>
@endsection

@section('theme', 'creator')

@section('try')
    <li>Press <strong>Open a second window</strong>. Change a question title in one window and select a different question in the other to see shared edits and each author's selection.</li>
    <li>Open a third window to see the current form reconstructed from the saved schema and the session's edit log.</li>
    <li>Open the <strong>Collaboration</strong> menu and choose <strong>Show Version History</strong> to inspect the session's changes.</li>
    <li>Press <strong>Save</strong> in either window to store the schema. Shared edits are temporary until saved; after all windows close and the room expires, a new session starts from the saved version.</li>
@endsection

@section('actions')
    <span class="flex min-w-0 flex-1 items-center gap-2 basis-72">
        <label for="invite-link" class="shrink-0 text-gray-600">Invite link</label>
        <input id="invite-link" data-invite-link readonly class="box-border h-9 min-w-0 flex-1 rounded-md border border-gray-300 bg-gray-50 px-2 font-mono text-xs" onclick="this.select()">
        <button type="button" data-copy-invite class="box-border inline-flex h-9 shrink-0 cursor-pointer items-center rounded-md border border-sjs-dark bg-white px-3 text-sm font-semibold text-sjs-dark [font-family:inherit] data-[copied=true]:bg-sjs-dark data-[copied=true]:text-white">Copy</button>
    </span>
    <a data-invite-link target="_blank" href="#" class="box-border inline-flex h-9 items-center gap-1.5 rounded-md border border-sjs-dark bg-sjs-dark px-3 text-sm font-semibold text-white no-underline hover:bg-sjs-dark/90">
        Open a second window
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
    </a>
@endsection

@section('about')
    @if (config('surveyjs.demo_mode'))
        <p>The invite link carries your sandbox ID: whoever opens it works in your copy of the demo data, in the same rooms.</p>
    @endif
@endsection

@section('form')
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 p-4 text-sm">
        <div class="min-w-0 flex-1"><p data-show="connection-status" role="status" aria-live="polite" class="m-0 text-gray-700">Connecting to the shared form...</p><p data-show="schema-status" role="status" class="m-0 mt-1 text-gray-600"></p></div>
        <button id="reconnect" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 px-3 font-semibold">Reconnect</button>
        <button id="retry-save" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 px-3 font-semibold disabled:opacity-50">Try saving again</button>
        <button id="download-local" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 px-3 font-semibold">Download local copy</button>
    </div>
    <div id="creator" hidden inert class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
