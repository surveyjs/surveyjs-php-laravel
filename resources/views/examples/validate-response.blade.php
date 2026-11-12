@extends('layouts.example')

@section('description')
    <p>Validate submitted answers against the form schema before saving them. The backend checks required fields, value types, and allowed choices, even for requests that bypass the form's browser validation.</p>
@endsection

@section('banner')
    @include('examples.partials.service-banner', ['switch' => 'VALIDATE_RESPONSES', 'on' => config('surveyjs.validate_responses')])
@endsection

@section('try')
    <li>Enter a claim or press <strong>Use sample answers</strong>, then <strong>Submit claim</strong>. The backend validates the answers before saving and returns the saved response ID.</li>
    <li>Send <strong>An unknown choice</strong>, <strong>A wrong type</strong>, or <strong>A missing required answer</strong> directly to the API to bypass browser validation. Inspect the submitted payload and the server's errors in <strong>Requests</strong>.</li>
    <li>Open <strong>Requests</strong> to compare accepted and rejected submissions. Rejected answers are not saved; <strong>Data</strong> shows the accepted response.</li>
@endsection

@section('actions')
    <div class="flex w-full flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2">
            @include('examples.partials.start-over')
            <button id="sample-answers" type="button" class="h-9 cursor-pointer rounded-md border border-gray-300 px-3 text-sm font-semibold disabled:opacity-50">Use sample answers</button>
        </div>
        <div role="group" aria-labelledby="tampered-response-label" class="flex flex-col gap-2 border-t border-gray-200 pt-4">
            <span id="tampered-response-label" class="font-semibold text-gray-600">Send a tampered response</span>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-tamper="unknown-choice" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">An unknown choice</button>
                <button type="button" data-tamper="wrong-type" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A wrong type</button>
                <button type="button" data-tamper="missing-required" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A missing required answer</button>
            </div>
        </div>
    </div>
@endsection
