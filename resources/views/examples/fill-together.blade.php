@php
    $records = \App\Support\Demo::records('job-sheet', 'customer');
    $record = request()->query('record', array_key_first($records) ?? 'new');
@endphp
@extends('layouts.example')

@section('description')
    <p>Let multiple respondents fill out the same record together in real time. Answers, focus, and cursors are shared between participants, so teams can coordinate on a job sheet or similar form.</p>
@endsection

@section('head')
    <script>window.SURVEYJS_PAGE.record = {{ Js::from((string) $record) }};</script>
@endsection

@section('try')
    <li>Choose a saved job sheet or press <strong>New job sheet</strong>. Wait for the connection status, then enter a customer and the dispatcher's notes.</li>
    <li>Press <strong>Open a second window</strong> to join as the <strong>Technician</strong>. The shared answers appear automatically. Add findings or hours in that window and watch them appear in the first.</li>
    <li>Focus the same question in both windows to see the participant indicators. When both participants edit the same answer, the last change wins.</li>
    <li>Press <strong>Finish job sheet</strong> in either window. The relay saves one response for the session, and both windows show the same saved response ID. Open <strong>Data</strong> to inspect it.</li>
    <li>If the connection drops, use <strong>Reconnect</strong> to rejoin the shared session. Press <strong>New job sheet</strong> to start another session.</li>
@endsection

@section('actions')
    <form method="get" class="flex items-center gap-2">
        <label for="record" class="text-gray-600">Record</label>
        <select id="record" name="record" disabled onchange="this.form.submit()" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm disabled:opacity-50">
            @foreach ($records as $id => $label)
                <option value="{{ $id }}" @selected((string) $id === (string) $record)>{{ $label }}</option>
            @endforeach
            <option value="new" @selected($record === 'new')>A new, blank job sheet</option>
            @if ($record !== 'new' && !array_key_exists($record, $records))
                <option value="{{ $record }}" selected>New job sheet</option>
            @endif
        </select>
    </form>
    <label class="flex items-center gap-2"><span class="text-gray-600">Role</span><select id="participant-role" disabled class="h-9 rounded-md border border-gray-300 bg-white px-2 disabled:opacity-50"><option value="dispatcher">Dispatcher</option><option value="technician">Technician</option></select></label>
    <button id="new-job-sheet" type="button" disabled class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100 disabled:opacity-50">New job sheet</button>
    <span data-show="room" class="text-gray-500"></span>
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

@section('form')
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 p-4 text-sm sm:px-6">
        <p data-show="connection-status" role="status" aria-live="polite" class="m-0 flex-1 text-gray-700">Connecting to the shared job sheet...</p>
        <button id="reconnect" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100 disabled:opacity-50">Reconnect</button>
    </div>
    <p id="collaboration-loading" class="m-0 p-6 text-sm text-gray-600">The form will appear when the shared answers have loaded.</p>
    <div id="form" hidden inert aria-busy="true" class="p-4 sm:p-6"></div>
@endsection

@section('about')
    @if (config('surveyjs.demo_mode'))
        <p>The invite link carries your sandbox ID: whoever opens it works in your copy of the demo data, in the same rooms.</p>
    @endif
@endsection
