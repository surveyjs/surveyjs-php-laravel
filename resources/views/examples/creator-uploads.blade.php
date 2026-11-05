@extends('layouts.example')

@section('description')
    <p>Let authors upload logos and images, storing their URLs in form schemas. The same upload endpoint can handle respondent files, and private assets can be served through a download endpoint that checks access.</p>
@endsection

@section('theme', 'creator')

@section('try')
    <li>In the Designer, click the logo area at the top of the form (<strong>Add logo</strong>... next to the title) and pick an image. <strong>Requests</strong> shows <code>POST /api/files</code>, then <code>PUT /api/forms/support</code>.</li>
    <li><strong>Data</strong> shows the JSON schema's <code>logo</code> as <code>/api/files/&lt;id&gt;</code>: a URL, not Base64.</li>
    <li>The logo in the Designer loads from <code>GET /api/files/&lt;id&gt;</code>.</li>
@endsection

@section('form')
    <div id="creator" class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
