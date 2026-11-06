@php
    // Demo only: this view also serves the embedded-schema example at /claim
    $step = \App\Support\Demo::step('load-definition-variables-data');
    $optionA = isset($definition);
    $record = request()->query('record', '');
    $records = $optionA ? [] : \App\Support\Demo::records('claim', 'customer_email', customerEmail: auth()->user()?->email ?? '');
@endphp
@extends('layouts.example')

@section('description')
    <p>Load form schemas and user variables from your PHP backend, either embedded in HTML or fetched from an API. Use variables to personalize form text and control question visibility, and restore saved answers through the API to prefill the form.</p>
@endsection

@section('head')
    @if ($optionA)
        {{-- Embed the schema and user variables as JSON escaped for an inline script --}}
        <script>
            window.FORM_DEFINITION = {{ Js::from($definition) }};
            window.FORM_VARIABLES = {{ Js::from($variables) }};
        </script>
    @endif
@endsection

@section('try')
    <li>Choose <strong>Schema embedded in HTML</strong> to receive the schema and user variables with the page, or <strong>Load from the API</strong> to fetch them and any saved answers in a separate request. You can inspect the API call in <strong>Requests</strong>.</li>
    <li>Switch <strong>Customer</strong> between Alice and Bob. User variables personalize the title with their name and plan and show priority handling only for the premium plan.</li>
    <li>When using <strong>Load from the API</strong>, you can select a <strong>Saved response</strong> to prefill the form with the customer's answers.</li>
    <li>Press <strong>Submit claim</strong> to save a new response. Open <strong>Data</strong> to inspect the saved answers; user variables are excluded.</li>
    <li>Press <strong>Start over</strong> to clear the form and the saved response selection.</li>
@endsection

@section('demo-user-control')
    <label class="flex items-center gap-2">
        <span class="text-gray-600">Customer</span>
        <select id="demo-user" data-reset-query="record" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
            <option value="alice" @selected(auth()->user()?->email === 'alice@example.com')>Alice (premium)</option>
            <option value="bob" @selected(auth()->user()?->email === 'bob@example.com')>Bob (basic)</option>
        </select>
    </label>
@endsection

@section('actions')
    <span class="box-border inline-flex min-h-9 max-w-full items-stretch rounded-md border border-gray-300 bg-white p-0.5" role="group" aria-label="Schema delivery">
        <a href="{{ url('/claim') }}" @if ($optionA) aria-current="page" @endif @class(['flex items-center rounded px-2.5 py-1 no-underline', 'bg-sjs-dark text-white' => $optionA, 'text-gray-700 hover:bg-gray-100' => ! $optionA])>Schema embedded in HTML</a>
        <a href="{{ route('example', 'load-definition-variables-data') }}" @unless ($optionA) aria-current="page" @endunless @class(['flex items-center rounded px-2.5 py-1 no-underline', 'bg-sjs-dark text-white' => ! $optionA, 'text-gray-700 hover:bg-gray-100' => $optionA])>Load from the API</a>
    </span>
    @unless ($optionA)
        <form method="get" class="flex items-center gap-2">
            <label for="record" class="text-gray-600">Saved response</label>
            <select id="record" name="record" onchange="this.form.submit()" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
                <option value="">New response</option>
                @foreach ($records as $id => $label)
                    <option value="{{ $id }}" @selected((string) $id === (string) $record)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    @endunless
    @include('examples.partials.start-over')
@endsection
