{{-- Active-filter badges for the fields in events.filters.audience-fields (#2361) --}}
@foreach ([
	'age' => ['Ages', ($ageOptions ?? ['' => 'Any'] + \App\Filters\AudienceFilter::AGE_OPTIONS)],
	'price' => ['Price', ($priceOptions ?? ['' => 'Any'] + \App\Filters\AudienceFilter::PRICE_OPTIONS)],
	'promoter' => ['Promoter', $promoterOptions ?? []],
	'series' => ['Series', $seriesOptions ?? []],
] as $field => [$badgeLabel, $badgeOptions])
@if(!empty($filters[$field]) && is_scalar($filters[$field]))
<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
	{{ $badgeLabel }}: {{ $badgeOptions[$filters[$field]] ?? 'Unknown' }}
</span>
@endif
@endforeach
@if(!empty($filters['has_tickets']))
<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
	Has ticket link
</span>
@endif
@if(!empty($filters['is_benefit']))
<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
	Benefit
</span>
@endif
