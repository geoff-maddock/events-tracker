{{-- Age, price, promoter, series, tickets and benefit fields, shared by every event filter panel (#2361) --}}
<!-- Age Filter -->
<div class="min-w-0">
	<label for="filter_age" class="block text-sm font-medium text-muted-foreground mb-1">Ages</label>
	<x-ui.select-field name="filters[age]" :options="($ageOptions ?? ['' => 'Any'] + \App\Filters\AudienceFilter::AGE_OPTIONS)" :selected="$filters['age'] ?? null"
		class="form-select-tw" id="filter_age" />
</div>

<!-- Price Filter -->
<div class="min-w-0">
	<label for="filter_price" class="block text-sm font-medium text-muted-foreground mb-1">Door Price</label>
	<x-ui.select-field name="filters[price]" :options="($priceOptions ?? ['' => 'Any'] + \App\Filters\AudienceFilter::PRICE_OPTIONS)" :selected="$filters['price'] ?? null"
		class="form-select-tw" id="filter_price" />
</div>

<!-- Promoter Filter -->
<div class="min-w-0">
	<label for="filter_promoter" class="block text-sm font-medium text-muted-foreground mb-1">Promoter</label>
	<x-ui.select-field name="filters[promoter]" :options="$promoterOptions ?? ['' => '']" :selected="$filters['promoter'] ?? null"
		data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a promoter" id="filter_promoter" />
</div>

<!-- Series Filter -->
<div class="min-w-0">
	<label for="filter_series" class="block text-sm font-medium text-muted-foreground mb-1">Series</label>
	<x-ui.select-field name="filters[series]" :options="$seriesOptions ?? ['' => '']" :selected="$filters['series'] ?? null"
		data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a series" id="filter_series" />
</div>

<!-- Ticket and Benefit Filters -->
<div class="min-w-0 space-y-2 sm:pt-6">
	<div class="flex items-center gap-2">
		<x-ui.checkbox name="filters[has_tickets]" id="filter_has_tickets" value="1" :checked="!empty($filters['has_tickets'])" />
		<label for="filter_has_tickets" class="text-sm text-foreground cursor-pointer">Has ticket link</label>
	</div>
	<div class="flex items-center gap-2">
		<x-ui.checkbox name="filters[is_benefit]" id="filter_is_benefit" value="1" :checked="!empty($filters['is_benefit'])" />
		<label for="filter_is_benefit" class="text-sm text-foreground cursor-pointer">Benefit</label>
	</div>
</div>
