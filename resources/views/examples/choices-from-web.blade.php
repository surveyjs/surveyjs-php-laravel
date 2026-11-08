@extends('layouts.example')

@section('description')
    <p>Load choice lists from APIs and update them when related answers change. For example, changing a region reloads the office list with matching options from your backend.</p>
@endsection

@section('try')
    <li>Select an <strong>Office</strong>, then change the <strong>Region</strong>. The previous office selection is cleared, and the backend supplies offices in the new region.</li>
    <li>Choose <strong>Your country</strong> from a separate list served by PHP. The backend caches the list and uses bundled choices if the external service is unavailable.</li>
    <li>Press <strong>Review selection</strong> to compare the displayed labels with the office ID and country code in the answers.</li>
    <li>Open <strong>Requests</strong> to inspect the office and country lookups, including the example authorization header.</li>
    <li>Press <strong>Start over</strong> to clear the selection and return to Europe.</li>
@endsection

@section('actions')
    @include('examples.partials.start-over')
@endsection

@section('form')
    <section id="choice-summary" hidden class="space-y-3 border-b border-gray-200 p-4 sm:p-6">
        <h3 class="m-0 text-lg font-semibold">Your selection</h3>
        <dl class="m-0 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-gray-600">Office</dt><dd data-show="selected-office" class="m-0 mt-1 font-semibold"></dd></div>
            <div><dt class="text-gray-600">Country</dt><dd data-show="selected-country" class="m-0 mt-1 font-semibold"></dd></div>
        </dl>
        <p class="m-0 text-sm text-gray-600">The form displays labels and returns their corresponding values.</p>
        <pre data-show="selected-values" class="m-0 overflow-x-auto rounded bg-gray-50 p-3 text-xs"></pre>
    </section>
    <div id="form" class="p-4 sm:p-6"></div>
@endsection
