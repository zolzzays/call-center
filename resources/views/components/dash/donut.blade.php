{{-- Дугуй график. items: [['label' => .., 'value' => .., 'color' => ..], ...] --}}
@props(['items' => [], 'size' => 120, 'stroke' => 16, 'center' => null, 'sub' => null, 'label' => 'Дугуй график'])
@php
    $sum = array_sum(array_column($items, 'value'));
    $r = ($size - $stroke) / 2;
    $c = $size / 2;
    $circ = 2 * M_PI * $r;
    $nonZero = count(array_filter($items, fn ($i) => $i['value'] > 0));
    $gap = $nonZero > 1 ? 2.5 : 0;   // хэсгүүдийн хооронд цагаан зай
    $offset = 0;
@endphp
<div class="donut" style="width: {{ $size }}px; height: {{ $size }}px">
    <svg viewBox="0 0 {{ $size }} {{ $size }}" role="img"
         aria-label="{{ $label }}: @foreach ($items as $it){{ $it['label'] }} {{ $it['value'] }}@if (! $loop->last), @endif @endforeach">
        <circle cx="{{ $c }}" cy="{{ $c }}" r="{{ $r }}" stroke="#eef2f6" stroke-width="{{ $stroke }}" fill="none"></circle>
        @foreach ($items as $it)
            @continue($it['value'] <= 0)
            @php $arc = $circ * $it['value'] / max(1, $sum); @endphp
            <circle class="donut-seg" cx="{{ $c }}" cy="{{ $c }}" r="{{ $r }}" fill="none" stroke="{{ $it['color'] }}" stroke-width="{{ $stroke }}"
                    stroke-dasharray="{{ max(0.01, $arc - $gap) }} {{ $circ }}" stroke-dashoffset="{{ -$offset }}"
                    data-tip="{{ $it['label'] }}|{{ number_format($it['value']) }} · {{ $sum ? round($it['value'] / $sum * 100, 1) : 0 }}%"></circle>
            @php $offset += $arc; @endphp
        @endforeach
    </svg>
    @if ($center !== null || $sub !== null || trim($slot) !== '')
        <div class="donut-center">
            @if (trim($slot) !== '')
                {{ $slot }}
            @else
                <b>{{ $center }}</b>@if ($sub)<span>{{ $sub }}</span>@endif
            @endif
        </div>
    @endif
</div>
