@extends('layouts.example')

@section('description')
    <p>Validate submitted answers against the form schema before saving them. The backend checks required fields, value types, and allowed choices, even for requests that bypass the form's browser validation.</p>
@endsection

@section('banner')
    @include('examples.partials.service-banner', ['switch' => 'VALIDATE_RESPONSES', 'on' => config('surveyjs.validate_responses')])
@endsection

@section('try')
    <li>Enter a claim or press <strong>Use sample answers</strong>, then <strong>Submit claim</strong>. The backend validates the answers before saving and returns the saved response ID.</li>
    <li>Send <strong>An unknown choice</strong>, <strong>A wrong type</strong>, or <strong>A missing required answer</strong> directly to the API to bypass browser validation. Inspect the submitted payload and the server's errors above the form.</li>
    <li>Open <strong>Requests</strong> to compare accepted and rejected submissions. Rejected answers are not saved; <strong>Data</strong> shows the accepted response.</li>
@endsection

@section('actions')
    @include('examples.partials.start-over')
    <button id="sample-answers" type="button" class="h-9 cursor-pointer rounded-md border border-gray-300 px-3 text-sm font-semibold disabled:opacity-50">Use sample answers</button>
    <span class="text-gray-600">Send a tampered response</span>
    <button type="button" data-tamper="unknown-choice" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">An unknown choice</button>
    <button type="button" data-tamper="wrong-type" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A wrong type</button>
    <button type="button" data-tamper="missing-required" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A missing required answer</button>
@endsection

@section('form')
    <div class="border-b border-gray-200 p-4 text-sm">
        <p data-show="validation-status" role="status" aria-live="polite" class="m-0 font-semibold">No response checked</p>
        <ul id="validation-errors" class="my-2 list-disc pl-5 text-red-700"></ul>
        <details class="mt-2"><summary class="cursor-pointer text-gray-600">Submitted payload</summary><pre data-show="validation-payload" class="max-h-56 overflow-auto rounded bg-gray-50 p-3 text-xs"></pre></details>
    </div>
    <div id="form" class="p-4 sm:p-6"></div>
@endsection
