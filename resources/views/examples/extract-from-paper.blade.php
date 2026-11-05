@extends('layouts.example')

@section('description')
    <p>Extract answers from scans and PDFs into draft forms for review. The extractor uses your form schema to return structured answers and confidence information, highlighting values that need checking before submission.</p>
@endsection

@section('banner')
    @unless (config('surveyjs.extraction.provider') || config('surveyjs.extraction.openai_api_key') || config('surveyjs.extraction.anthropic_api_key'))
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            <strong>Document extraction is unavailable.</strong> Configure <code>SURVEYJS_AI_PROVIDER</code> (<code>openai</code>, <code>anthropic</code> or <code>ollama</code>) and its key or
            <code>SURVEYJS_OLLAMA_BASE_URL</code> in <code>.env</code>, then run <code>docker compose up -d surveyjs</code>.
        </p>
    @endunless
@endsection

@section('try')
    <li>Press <strong>Use the sample scan</strong> (<a href="{{ url('/shared/samples/work-order-scan.png') }}" target="_blank">work-order-scan.png</a>) or pick <strong>Your own file</strong>: one <code>POST /api/work-orders/extract</code> fills the form as a draft.</li>
    <li>Answers read with low confidence are marked "Review this answer". The draft details show the source file, document ID (when detected), and number of answers to review.</li>
    <li><strong>Data</strong> shows the same <code>row counts</code> before and after: extraction saves nothing.</li>
    <li>Press <strong>Start over</strong> to empty the form before the next scan.</li>
@endsection

@section('form')
    <div id="extract-details" hidden class="border-b border-gray-200 p-4 sm:p-6">
        <dl class="m-0 grid gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-gray-600">Source file</dt><dd data-show="extract-source" class="m-0 mt-1 break-words font-semibold"></dd></div>
            <div><dt class="text-gray-600">Document ID</dt><dd data-show="extract-id" class="m-0 mt-1 break-words font-semibold"></dd></div>
            <div><dt class="text-gray-600">Answers to review</dt><dd data-show="extract-review-count" class="m-0 mt-1 font-semibold"></dd></div>
        </dl>
        <p class="m-0 mt-3 text-sm text-gray-600">Review the draft before submitting.</p>
    </div>
    <div id="form" class="p-4 sm:p-6"></div>
@endsection

@section('actions')
    <button id="sample-scan" type="button" class="box-border inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-sjs-dark bg-sjs-dark px-3 text-sm font-semibold text-white [font-family:inherit] hover:bg-sjs-dark/90">Use the sample scan</button>
    <label class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-sjs">
        Your own file<input id="own-scan" type="file" accept="image/*,application/pdf" class="sr-only">
    </label>
    @include('examples.partials.start-over')
    <span data-show="extract-status" class="text-gray-700">No scan</span>
@endsection
