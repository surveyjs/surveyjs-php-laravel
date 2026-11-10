@extends('layouts.example')

@section('description')
    <p>Load choice lists from APIs and update them when related answers change. For example, changing a region reloads the office list with matching options from your backend.</p>
@endsection

@section('try')
    <li>Select an <strong>Office</strong>, then change the <strong>Region</strong>. The previous office selection is cleared, and the backend supplies offices in the new region.</li>
    <li>Choose <strong>Your country</strong> from a separate list served by PHP. The backend caches the list and uses bundled choices if the external service is unavailable.</li>
    <li>Press <strong>Complete</strong> to save your answers, then open <strong>Data</strong> to inspect the stored office ID and country code.</li>
    <li>Open <strong>Requests</strong> to inspect the office and country lookups, including the example authorization header.</li>
    <li>Press <strong>Start over</strong> to reset the form.</li>
@endsection

@section('actions')
    @include('examples.partials.start-over')
@endsection
