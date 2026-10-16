{{-- ДҮТ-ийн лого: public/images/logo.(svg|png|webp|jpg) байвал харуулна, үгүй бол түр дүрс --}}
@php
    $logo = collect(['svg', 'png', 'webp', 'jpg', 'jpeg'])
        ->map(fn ($ext) => "images/logo.{$ext}")
        ->first(fn ($path) => is_file(public_path($path)));
@endphp
@if ($logo)
    <img src="{{ asset($logo) }}?v={{ filemtime(public_path($logo)) }}" alt="ДҮТ" class="brand-logo">
@else
    <span class="brand-icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
    </span>
@endif
