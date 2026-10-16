{{-- Босоо баганууд + Y тэнхлэг. rows: [['label' => .., 'value' => ..], ...]; highlight: тод харуулах мөрийн дугаар --}}
@props(['rows' => [], 'color' => 'var(--c-blue)', 'soft' => 'var(--c-blue-soft)', 'highlight' => null, 'height' => 150, 'unit' => 'дуудлага', 'label' => 'Баганан график'])
@php
    $maxVal = max(array_merge([0], array_column($rows, 'value')));
    // Тэнхлэгийн дээд утгыг ойлгомжтой тоо болгоно (1, 2, 5 × 10ⁿ)
    $step = 1;
    if ($maxVal > 0) {
        $raw = $maxVal / 4;
        $mag = 10 ** floor(log10($raw));
        $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($s) => $s >= $raw) ?? 10 * $mag;
        $step = max(1, $step);
    }
    $top = max(1, ceil($maxVal / $step) * $step);
    $ticks = range(0, $top, $step);
@endphp
<div class="cols-chart" style="--h: {{ $height }}px" role="list" aria-label="{{ $label }}">
    <div class="cols-axis" aria-hidden="true">
        @foreach (array_reverse($ticks) as $t)<span>{{ number_format($t) }}</span>@endforeach
    </div>
    <div class="cols-plot">
        @foreach ($ticks as $t)
            <span class="cols-grid" style="bottom: {{ $t / $top * 100 }}%" aria-hidden="true"></span>
        @endforeach
        @foreach ($rows as $i => $row)
            @php $h = $row['value'] / $top * 100; $strong = $highlight === null || $highlight === $i; @endphp
            <div class="col" role="listitem" tabindex="0" data-tip="{{ $row['label'] }}|{{ number_format($row['value']) }} {{ $unit }}">
                <span class="col-val" style="bottom: calc({{ $h }}% + 4px)">{{ number_format($row['value']) }}</span>
                <span class="col-bar" style="height: {{ $h }}%; background: {{ $strong ? $color : $soft }}"></span>
                <span class="col-label">{{ $row['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>
