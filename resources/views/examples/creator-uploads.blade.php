@extends('layouts.example')

@section('description')
    <p>Let authors upload logos and images, storing their URLs in form schemas. The same upload endpoint can handle respondent files, and private assets can be served through a download endpoint that checks access.</p>
@endsection

@section('theme', request()->has('form') ? 'survey' : 'creator')

@section('try')
    <li>In the Designer, click <strong>Add logo</strong> beside the form title and choose an image. PNG, JPEG, WebP, and GIF files up to 5 MB are supported.</li>
    <li>Select the <strong>Support resources</strong> image and upload a replacement using its image controls. The upload status confirms the file upload; the separate save status confirms that its URL was saved in the schema.</li>
    <li>Press <strong>Open saved form</strong> or reload the page after saving. The logo and question image load from their saved URLs.</li>
    <li>Open <strong>Requests</strong> to inspect uploads and schema saves. <strong>Data</strong> shows this example's saved schema and the separate file records.</li>
@endsection

@section('actions')
    @if (request()->has('form'))
        <a id="back-to-editor" href="?" class="text-sjs-dark underline">Back to editor</a>
    @else
        <a id="open-saved-form" href="?form" target="_blank" aria-disabled="true" class="inline-flex h-9 items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 no-underline hover:bg-gray-100 aria-disabled:opacity-50">Open saved form</a>
    @endif
@endsection

@section('form')
    <div class="space-y-2 border-b border-gray-200 p-4 text-sm" role="status" aria-live="polite">
        <p class="m-0"><strong>Image upload:</strong> <span data-show="upload-status">Choose an image in the Designer.</span></p>
        <p class="m-0"><strong>Form schema:</strong> <span data-show="schema-status">Loading the saved form...</span></p>
        <button id="retry-load" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100">Retry loading</button>
        <button id="retry-save" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100">Try saving again</button>
        <pre data-show="asset-urls" class="m-0 overflow-x-auto rounded bg-gray-50 p-2 text-xs"></pre>
    </div>
    <div id="creator" hidden inert class="{{ request()->has('form') ? 'p-4 sm:p-6' : 'h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]' }}"></div>
@endsection
