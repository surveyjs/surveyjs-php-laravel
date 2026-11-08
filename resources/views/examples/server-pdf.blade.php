@php
    $claims = \Illuminate\Support\Facades\DB::table('responses')->where('form_id', 'claim')->orderByDesc('id')->limit(10)->get();
@endphp
@extends('layouts.example')

@section('description')
    <p>Generate PDFs on the server for archives, email attachments, or downloads. Use a form schema and optional saved answers to produce a document without opening a browser.</p>
@endsection

@section('banner')
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        PDF Generator is commercial, and the pinned SurveyJS service never calls <code>setLicenseKey()</code>, so its PDFs carry the SurveyJS alert banner even when you set a licence key.
    </p>
@endsection

@section('try')
    <li>Review a saved claim's details, then press <strong>Open PDF</strong> to view the filled form in a new tab or <strong>Download PDF</strong> to save a copy.</li>
    <li>The backend generates the PDF from the saved answers and their schema version. Open <strong>Requests</strong> to inspect the PDF request.</li>
    <li>Submit a claim in <a href="{{ route('example', 'relational-storage') }}">Use Relational Storage</a>, then reload this page to include it in the ten most recent claims.</li>
@endsection

@section('form')
    <ul class="m-0 list-none divide-y divide-gray-100 p-0">
        @forelse ($claims as $claim)
            @php($answers = json_decode($claim->data))
            <li data-claim-row class="px-4 py-4 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0"><p class="m-0 break-words font-semibold">Claim #{{ $claim->id }} · {{ is_scalar($answers->customer_email ?? null) ? $answers->customer_email : 'No email provided' }}</p><p class="m-0 mt-1 text-gray-600">Amount: {{ is_scalar($answers->amount ?? null) ? $answers->amount : 'Not provided' }} · {{ $claim->definition_version ?? 'Current schema' }} · {{ substr($claim->created_at, 0, 10) }}</p></div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-claim="{{ $claim->id }}" data-action="open" class="cursor-pointer rounded bg-sjs px-3 py-1.5 font-semibold text-white disabled:opacity-50">Open PDF</button>
                        <button type="button" data-claim="{{ $claim->id }}" data-action="download" class="cursor-pointer rounded border border-gray-300 px-3 py-1.5 font-semibold disabled:opacity-50">Download PDF</button>
                    </div>
                </div>
                <details class="mt-2"><summary class="cursor-pointer text-gray-600">Saved answers</summary><pre class="max-h-56 overflow-auto rounded bg-gray-50 p-3 text-xs">{{ json_encode($answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></details>
                <p data-pdf-status role="status" aria-live="polite" class="m-0 mt-2 text-gray-600"></p>
                <p data-pdf-links hidden class="m-0 mt-2 flex flex-wrap gap-4"><a data-pdf-link="open" target="_blank" rel="noopener" class="text-sjs-dark underline">Open generated PDF</a><a data-pdf-link="download" download="claim-{{ $claim->id }}.pdf" class="text-sjs-dark underline">Download generated PDF</a></p>
            </li>
        @empty
            <li class="px-4 py-3 text-sm text-gray-500">
                <span class="block">No claims</span>
                <a href="{{ route('example', 'relational-storage') }}" class="mt-2 inline-block text-sjs-dark underline">Submit a claim</a>
            </li>
        @endforelse
    </ul>
@endsection
