@extends('layouts.example')

@section('description')
    <p>Explore collected responses through interactive charts and filters. Use the form schema to choose charts automatically, and load a date range of responses from your backend.</p>
@endsection

@section('theme', 'dashboard')

@section('try')
    <li>Choose a date preset or set <strong>From</strong> and <strong>To</strong>. The status above the charts shows the number of responses loaded for that period.</li>
    <li>Click a bar under <strong>Where did you hear about us?</strong> to filter the other charts to that group. Press <strong>Reset Filter</strong> to include everyone again.</li>
    <li>Change a chart type or sort order to explore the same answers in a different way.</li>
    <li>Open <strong>Requests</strong> to inspect the form schema and response lookups. <strong>Data</strong> shows the number of stored responses in the selected date range.</li>
@endsection

@section('actions')
    <label for="period" class="text-gray-600">Period</label>
    <select id="period" class="h-9 rounded-md border border-gray-300 bg-white px-2 text-sm"><option value="7">Last 7 days</option><option value="30" selected>Last 30 days</option><option value="90">Last 90 days</option><option value="all">All time</option><option value="custom">Custom</option></select>
    <label for="from" class="text-gray-600">From</label>
    <input id="from" type="date" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
    <label for="to" class="text-gray-600">To</label>
    <input id="to" type="date" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
    <span class="text-gray-500">UTC</span>
@endsection

@section('form')
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 p-4 text-sm">
        <div class="min-w-0 flex-1"><p data-show="dashboard-status" role="status" aria-live="polite" class="m-0 font-semibold">Loading responses...</p><p data-show="dashboard-range" class="m-0 mt-1 text-gray-600"></p></div>
        <button id="retry-dashboard" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100">Try again</button>
    </div>
    <div id="dashboard" hidden aria-busy="true" class="min-h-96 p-2"></div>
@endsection
