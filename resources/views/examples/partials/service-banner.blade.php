{{-- Demo only: shown when a SurveyJS service switch is off --}}
@if (isset($switch) && ! $on)
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        <strong>{{ $switch === 'VALIDATE_RESPONSES' ? 'Response validation is disabled.' : 'Form schema checking is disabled.' }}</strong>
        Set <code>{{ $switch }}=true</code> in <code>.env</code>, start the service with <code>docker compose up -d surveyjs</code>, and reload.
    </p>
@endif
