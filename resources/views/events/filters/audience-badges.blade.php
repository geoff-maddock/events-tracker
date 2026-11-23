{{-- Active-filter badges for the fields in events.filters.audience-fields (#2361) --}}
@foreach ([
	'age' => ['Ages', ($ageOptions ?? ['' => 'Any'] + \App\Filters\AudienceFilter::AGE_OPTIONS)],
	'price' => ['Price', ($priceOptions ?? ['' => 'Any'] + \App\Filters\AudienceFilter::PRICE_OPTIONS)],
	'promoter' => ['Promoter', $promoterOptions ?? []],
	'series' => ['Series', $seriesOptions ?? []],
] as $field => [$badgeLabel, $badgeOptions])
@if(!empty($filters[$field]) && is_scalar($filters[$field]))
<x-filter-badge>{{ $badgeLabel }}: {{ $badgeOptions[$filters[$field]] ?? 'Unknown' }}</x-filter-badge>
@endif
@endforeach
@if(!empty($filters['has_tickets']))
<x-filter-badge>Has ticket link</x-filter-badge>
@endif
@if(!empty($filters['is_benefit']))
<x-filter-badge>Benefit</x-filter-badge>
@endif
