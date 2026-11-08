@extends('layouts.example')

@section('description')
    <p>Catch broken references and logic errors in form schemas before saving. Findings are returned to Survey Creator so authors can fix issues, such as conditions referencing missing questions, before respondents encounter them.</p>
@endsection

@section('theme', 'creator')

@section('banner')
    @include('examples.partials.service-banner', ['switch' => 'LINT_DEFINITIONS', 'on' => config('surveyjs.lint_definitions')])
@endsection

@section('try')
    <li>Send a sample with <strong>An unknown variable in an expression</strong> or <strong>A question without a name</strong>. Errors block the save; the report shows what to fix and where.</li>
    <li>Send a sample with <strong>An unknown property</strong>. Warnings allow the save and remain visible for review.</li>
    <li>Edit the form and press <strong>Save</strong> or <strong>A clean save</strong> to check the current editor schema. Open <strong>Submitted schema</strong> or <strong>Requests</strong> to inspect exactly what was sent.</li>
    <li>The first three buttons send fixed sample schemas. An accepted sample replaces this demo's saved schema; <strong>A clean save</strong> saves the current editor contents.</li>
@endsection

@section('actions')
    <span class="text-gray-600">Check and save</span>
    <button type="button" data-lint="unknown-variable" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">An unknown variable in an expression</button>
    <button type="button" data-lint="no-name" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A question without a name</button>
    <button type="button" data-lint="unknown-property" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">An unknown property</button>
    <button type="button" data-lint="clean" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A clean save</button>
@endsection

@section('form')
    <div class="border-b border-gray-200 p-4 text-sm">
        <div class="flex flex-wrap items-center gap-3"><p data-show="lint-status" role="status" aria-live="polite" class="m-0 flex-1">Loading the saved schema...</p><button id="retry-load" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 px-3 font-semibold">Retry loading</button></div>
        <div id="lint-report" hidden class="mt-3 overflow-x-auto"><table class="w-full border-collapse text-left text-xs"><thead><tr><th class="px-3 py-2">Severity</th><th class="px-3 py-2">Rule</th><th class="px-3 py-2">Location</th><th class="px-3 py-2">Message</th></tr></thead><tbody id="lint-findings"></tbody></table></div>
        <details class="mt-2"><summary class="cursor-pointer text-gray-600">Submitted schema</summary><pre data-show="lint-payload" class="max-h-56 overflow-auto rounded bg-gray-50 p-3 text-xs"></pre></details>
    </div>
    <div id="creator" hidden inert class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
