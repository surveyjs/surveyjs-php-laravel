{{-- Demo only: the catalog, rendered from surveyjs-integration.json, in the SurveyJS site's design
     (Figma General, page "Backend Integration examples page + demo"; desktop 1400, tablet 900, mobile 375) --}}
@php
    $sections = [
        'Run Forms' => 'SurveyJS Form Library displays forms in the browser. Start with a PHP endpoint that saves submitted answers, then add the workflows your application needs.',
        'Analyze Results with Dashboard' => 'SurveyJS Dashboard lets users analyze responses with interactive charts in the browser. Your PHP backend supplies the saved responses.',
        'Edit Forms in Survey Creator' => 'Survey Creator lets users build and edit forms in the browser. Your PHP backend loads and saves form JSON schemas and checks that users have permission to make changes.',
        'Run Server-Side Workflows' => 'Use a Node.js service alongside your PHP application to run SurveyJS workflows: validate responses, check form schemas, generate PDFs, and extract answers from documents.',
    ];
    // Hero cards, by the section they link to: the label and the icon.
    $heroLinks = [
        'Run Forms' => ['Run Forms', 'preview'],
        'Analyze Results with Dashboard' => ['Analyze Results with Dashboard', 'graph'],
        'Edit Forms in Survey Creator' => ['Edit Forms in Survey Creator', 'embed-creator'],
        'Run Server-Side Workflows' => ['Run Server-Side Workflows', 'server'],
    ];
    $steps = collect($manifest['steps'])->groupBy('section');
    // The manifest's licence reads "MIT", "Commercial" or "Commercial client", optionally followed by ", needs an AI provider"
    $licence = fn (array $step) => str_starts_with($step['licence'], 'MIT') ? 'MIT' : 'Commercial';
    $needsAi = fn (array $step) => str_contains($step['licence'], 'needs an AI provider');
    $cardBadge = 'rounded-lg px-3 py-1 text-base/6 font-semibold';
    $licenceBadge = ['MIT' => 'bg-sjs-10 text-sjs', 'Commercial' => 'bg-sjs-yellow-10 text-sjs-yellow'];
    // Site Button, medium: primary and tertiary neutral
    $blackButton = 'rounded-lg bg-sjs-fg px-6 py-3 text-center text-base/6 font-semibold text-white no-underline hover:bg-gray-700';
    $whiteButton = 'inline-block rounded-lg bg-white px-6 py-3 text-center text-base/6 font-semibold text-sjs-fg no-underline shadow-[inset_0_0_0_1px_var(--color-sjs-border)] hover:bg-sjs-back-gray';
    // Site Button, large from tablets up: the footer's primary and secondary brand buttons
    $footerButton = 'rounded-lg px-6 py-3 text-center text-base/6 font-semibold no-underline md:px-12 md:py-5 md:text-2xl/6';
    // The footer's logos, by file (images/site/stack-*.svg)
    $stackLogos = ['nextjs', 'nodejs', 'aspnet', 'php', 'wordpress', 'model', 'postgresql', 'mongodb', 'python'];
    $solidUnderline = 'underline [text-decoration-skip-ink:none] [text-underline-position:from-font]';
    $column = 'mx-auto max-w-sjs-content px-4 md:px-6';
    $number = 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Support\Demo::TITLE }} · SurveyJS</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white font-sans text-sjs-fg antialiased">
@include('partials.site-header')

<main>
    <div class="{{ $column }} pt-12 pb-12 text-center md:pt-16 xl:pt-24">
        <h1 class="mx-auto max-w-[880px] text-sjs-title-lg font-bold text-balance md:text-sjs-title-2xl">Build a PHP Backend for&nbsp;Your SurveyJS Forms</h1>
        <p class="mt-6 mb-0 text-sjs-para-lg font-medium text-pretty text-sjs-fg-50">
            Learn how to connect SurveyJS components to a PHP backend using Laravel.
            These working examples accompany the <a class="{{ $solidUnderline }} text-inherit hover:text-sjs-fg" href="{{ \App\Support\Demo::docsUrl() }}" target="_blank">PHP integration guide</a> and show the code behind each request.
            SurveyJS does not include a backend, so use these examples as a starting point for your application.
        </p>
    </div>

    {{-- Phones: one column, tablets: two, desktops: one row --}}
    <nav class="{{ $column }} mt-2.5 grid gap-3 md:mt-0 md:grid-cols-2 xl:grid-cols-4" aria-label="Sections">
        @foreach ($heroLinks as $title => [$label, $icon])
            @continue(! $steps->has($title))
            <a class="flex flex-col items-start gap-6 rounded-3xl border border-sjs-line bg-white p-6 text-sjs-para-md text-sjs-fg no-underline hover:bg-sjs-back-gray" href="#{{ str($title)->slug() }}">
                <span class="flex rounded-lg bg-sjs-10 p-2"><img src="{{ asset("images/site/icon-{$icon}.svg") }}" width="24" height="24" alt=""></span>
                <span class="{{ $solidUnderline }}">{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    <div class="{{ $column }} pt-12 pb-12 md:pb-16 xl:pb-24">
        <a class="flex items-center gap-3 rounded-xl border border-sjs-line bg-white p-3 text-sjs-title-sm font-bold text-sjs-fg no-underline hover:bg-sjs-back-gray md:rounded-3xl md:py-6 md:pr-5 md:pl-6 md:text-sjs-para-lg" href="{{ config('surveyjs.github') }}" target="_blank">
            <img src="{{ asset('images/site/icon-github.svg') }}" width="40" height="40" alt="">
            <span class="flex-1">View on GitHub</span>
            <img src="{{ asset('images/site/icon-chevron.svg') }}" width="40" height="40" alt="">
        </a>
    </div>

    @foreach ($sections as $title => $intro)
        @continue(! $steps->has($title))
        <section id="{{ str($title)->slug() }}" @class(['border-t border-sjs-line pb-12 md:pb-16 xl:pb-24', 'bg-sjs-back-gray' => $number++ % 2 === 0])>
            <div class="{{ $column }} pt-16 pb-12 xl:pt-24">
                <h2 class="text-sjs-title-lg font-bold md:text-sjs-title-xl">{{ $title }}</h2>
                <p class="mt-3 mb-0 text-sjs-para-lg font-medium text-sjs-fg-50">{{ $intro }}</p>
            </div>
            <div class="{{ $column }} grid gap-3 md:grid-cols-2">
                @foreach ($steps[$title] as $step)
                    {{-- A card alone in its row takes the full width. Phones: stacked, full-width buttons --}}
                    <article class="flex flex-col gap-10 rounded-3xl border border-sjs-line bg-white p-4 md:p-6 md:odd:last:col-span-2">
                        <div class="flex flex-1 flex-col gap-6 p-2 md:p-0">
                            <div class="flex flex-wrap gap-3">
                                <span class="{{ $cardBadge }} {{ $licenceBadge[$licence($step)] }}">{{ $licence($step) }}</span>
                                @if ($needsAi($step))
                                    <span class="{{ $cardBadge }} bg-sjs-violet-10 text-sjs-violet">AI Provider Required</span>
                                @endif
                            </div>
                            <div class="flex flex-col gap-3">
                                <h3 class="text-sjs-para-lg font-semibold">{{ $step['title'] }}</h3>
                                <p class="m-0 text-sjs-para-md text-sjs-fg-75"><strong>Use when: </strong>{{ $step['need'] }}</p>
                            </div>
                        </div>
                        <p class="m-0 flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center">
                            <a class="{{ $blackButton }}" href="{{ url($step['page']) }}">Run Example</a>
                            <a class="{{ $whiteButton }}" href="{{ config('surveyjs.github') }}/blob/{{ $manifest['branch'] }}/{{ $step['files']['server'] ?? $step['files']['client'] }}" target="_blank">View Source ↗&#xFE0E;</a>
                            <a class="{{ $whiteButton }}" href="{{ \App\Support\Demo::docsUrl($step) }}" target="_blank">Read Guide ↗&#xFE0E;</a>
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- Phones and tablets: stacked, full-width buttons. Desktops: one row --}}
    <div class="flex flex-col items-center gap-12 border-t border-sjs-line px-4 pt-16 pb-24 md:px-6 md:pt-24 md:pb-[120px] xl:pb-40">
        <div class="relative w-full max-w-sjs-content overflow-hidden py-4" aria-hidden="true">
            <div class="flex w-max animate-sjs-marquee gap-6 pr-6 motion-reduce:animate-none md:gap-12 md:pr-12">
                @foreach ([...$stackLogos, ...$stackLogos] as $logo)
                    {{-- Figma's cell: the ASP.NET art is shorter than it, the Python art smaller --}}
                    <span class="flex size-16 shrink-0 items-center justify-center md:size-20">
                        <img src="{{ asset("images/site/stack-{$logo}.svg") }}" @class(['h-auto', 'w-[91.7%]' => $logo === 'python', 'w-full' => $logo !== 'python']) alt="">
                    </span>
                @endforeach
            </div>
            <div class="absolute inset-y-0 left-0 w-16 bg-linear-to-r from-white to-transparent md:w-20"></div>
            <div class="absolute inset-y-0 right-0 w-16 bg-linear-to-l from-white to-transparent md:w-20"></div>
        </div>
        <div class="flex w-full flex-col items-center gap-12 md:gap-16">
            <p class="m-0 w-full max-w-sjs-content text-center text-sjs-para-lg font-medium">SurveyJS works with any backend, so you can build a complete survey management system using your preferred technologies. Let users create, edit, and run surveys, collect responses, and analyze results with interactive charts.</p>
            <div class="flex w-full max-w-sjs-content flex-col gap-3 xl:flex-row xl:justify-center xl:gap-8">
                <a class="{{ $footerButton }} bg-sjs text-white hover:bg-sjs-dim" href="https://surveyjs.io/backend-integration/examples" target="_blank">More Backend Examples</a>
                <a class="{{ $footerButton }} bg-sjs-15 text-sjs-fg hover:bg-sjs-15-dim" href="https://surveyjs.io/use-cases" target="_blank">SurveyJS Use Cases</a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
