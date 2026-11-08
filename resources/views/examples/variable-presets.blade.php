@extends('layouts.example')

@section('description')
    <p>Test conditional logic with saved sets of sample variables in Preview. Switch between scenarios, such as basic and premium subscription plans, to check which questions appear without changing the form schema.</p>
@endsection

@section('theme', 'creator')

@section('try')
    <li><strong>basic</strong> is selected initially. In Preview, switch to <strong>premium</strong> using the variable preset selector. The values above the form show which variables control the preview, including the premium-only callback question.</li>
    <li>Open <strong>Manage presets</strong> and edit a preset's customer plan or add a scenario. The save status confirms when the backend has stored your changes.</li>
    <li>Reload the page to check that the edited presets were saved. Open <strong>Requests</strong> to inspect the preset list and save requests.</li>
    <li>Switch <strong>Author</strong> to <strong>Bob</strong> and edit a preset. The backend rejects the save, and the status makes clear that those edits remain only in the editor.</li>
@endsection

@section('demo-user-control')
    <label class="flex items-center gap-2"><span class="text-gray-600">Author</span>
        <select id="demo-user" disabled class="h-9 rounded-md border border-gray-300 bg-white px-2 disabled:opacity-50">
            <option value="alice" @selected(auth()->user()?->email === 'alice@example.com')>Alice (editor)</option>
            <option value="bob" @selected(auth()->user()?->email === 'bob@example.com')>Bob (viewer)</option>
        </select>
    </label>
@endsection

@section('form')
    <div class="space-y-2 border-b border-gray-200 p-4 text-sm">
        <p class="m-0"><strong>Active preset:</strong> <span data-show="active-preset">Loading...</span></p>
        <pre data-show="preset-variables" class="m-0 overflow-x-auto rounded bg-gray-50 p-2 text-xs"></pre>
        <p data-show="preset-status" role="status" aria-live="polite" class="m-0 text-gray-700">Loading presets...</p>
        <button id="retry-load" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100">Retry loading</button>
        <button id="retry-presets" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100 disabled:opacity-50">Try saving again</button>
    </div>
    <div id="creator" hidden inert class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
