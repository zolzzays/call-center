{{-- Хэвтээ баганууд. rows: [['label' => .., 'value' => ..], ...] --}}
@props(['rows' => [], 'color' => 'var(--c-blue)', 'thin' => false, 'unit' => 'дуудлага', 'label' => 'Баганан график'])
@php $max = max(array_merge([1], array_column($rows, 'value'))); @endphp
@if (empty($rows))
    <div class="empty-chart">Өгөгдөл алга.</div>
@else
    <div class="hbars {{ $thin ? 'thin' : '' }}" role="list" aria-label="{{ $label }}">
        @foreach ($rows as $row)
            <div class="hbar" role="listitem" tabindex="0" data-tip="{{ $row['label'] }}|{{ number_format($row['value']) }} {{ $unit }}@isset($row['percent']) · {{ $row['percent'] }}%@endisset">
                <span class="hbar-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                <span class="hbar-track">
                    <span class="hbar-fill" style="width: {{ $row['value'] / $max * 100 }}%; background: {{ $color }}"></span>
                    <span class="hbar-val">{{ number_format($row['value']) }}</span>
                </span>
            </div>
        @endforeach
    </div>
@endif
