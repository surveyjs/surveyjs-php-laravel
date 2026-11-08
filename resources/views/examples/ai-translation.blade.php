@extends('layouts.example')

@section('description')
    <p>Translate form text with AI and let authors review the results. Your backend sends titles, choices, and other text to your chosen provider, keeping its API key on the server.</p>
@endsection

@section('theme', 'creator')

@section('banner')
    @unless (config('surveyjs.ai.api_key'))
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            <strong>AI translation is unavailable.</strong> Configure <code>AI_API_KEY</code> in <code>.env</code> to enable it, then reload.
        </p>
    @endunless
@endsection

@section('try')
    <li>Choose a <strong>Language</strong> and press <strong>Translate</strong>. Only text missing in that language is sent to the translation provider through your backend.</li>
    <li>Review the generated text in <strong>Translations</strong> and edit any wording that needs correction. The sample already has a French title to demonstrate keeping existing translations.</li>
    <li>Press <strong>Preview language</strong> to see the translated form. Select <strong>English (source)</strong> to compare it with the original.</li>
    <li>Press <strong>Save translations</strong> to store the reviewed schema. Reload the page to confirm the translations were saved. Failed saves retain your edits for another attempt.</li>
    <li>Open <strong>Requests</strong> to inspect the translation request and schema save. If translation fails, correct the provider configuration or retry with <strong>Translate</strong>.</li>
@endsection

@section('actions')
    <label for="translation-language" class="text-gray-600">Language</label>
    <select id="translation-language" disabled class="h-9 rounded-md border border-gray-300 bg-white px-2"><option value="es">Español</option><option value="fr">Français</option><option value="de">Deutsch</option><option value="en">English (source)</option></select>
    <button id="translate-language" type="button" disabled class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100 disabled:opacity-50">Translate</button>
    <button id="preview-language" type="button" disabled class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100 disabled:opacity-50">Preview language</button>
    <button id="save-translations" type="button" disabled class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100 disabled:opacity-50">Save translations</button>
@endsection

@section('form')
    <div class="space-y-2 border-b border-gray-200 p-4 text-sm" role="status" aria-live="polite">
        <p class="m-0"><strong>Translation:</strong> <span data-show="translation-status">Choose a language and translate missing text.</span></p>
        <p class="m-0"><strong>Form schema:</strong> <span data-show="schema-status">Loading the saved form...</span></p>
        <button id="retry-load" type="button" hidden class="h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-3 font-semibold hover:bg-gray-100">Retry loading</button>
    </div>
    <div id="creator" hidden inert class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
