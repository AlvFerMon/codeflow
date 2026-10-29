{{-- Datos del tutorial para resources/js/onboarding.js. El rediseño solo necesita conservar este
     bloque y los atributos data-tour="…" de los elementos que se resaltan (ver config/onboarding.php). --}}
@php
    $onboardingState = app(\App\Services\OnboardingService::class)->state(auth()->user());
    $onboardingPayload = [
        'tour' => $onboardingState['tour'],
        'urls' => [
            'progress' => route('onboarding.progress'),
            'complete' => route('onboarding.complete'),
            'skip' => route('onboarding.skip'),
        ],
    ];
@endphp
@if ($onboardingState['tour']['show'])
    <script type="application/json" id="cf-onboarding">@json($onboardingPayload)</script>
@endif
