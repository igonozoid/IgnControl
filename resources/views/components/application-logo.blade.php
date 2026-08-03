@php
    $classes = $attributes->has('class') ? $attributes->get('class') : 'h-8 w-auto';
@endphp
<img src="{{ asset('images/ignf-logo-light.png') }}" alt="IgnControl" {{ $attributes->except('class') }} class="{{ $classes }} dark:hidden">
<img src="{{ asset('images/ignf-logo-dark.png') }}" alt="IgnControl" {{ $attributes->except('class') }} class="{{ $classes }} hidden dark:block">
