<div class="flex flex-wrap gap-2 mb-4">
    <a href="{!! URL::route('events.index') !!}" class="px-3 py-1.5 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors text-sm">
        <i class="bi bi-list-ul mr-1"></i> Index
    </a>
    <a href="{!! URL::route('calendar') !!}" class="px-3 py-1.5 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors text-sm">
        <i class="bi bi-calendar3 mr-1"></i> Calendar
    </a>
    @auth
    <a href="{!! URL::route('events.create') !!}" class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors text-sm shadow-sm">
        <i class="bi bi-plus-lg mr-1"></i> Add Event
    </a>
    <a href="{!! URL::route('series.create') !!}" class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors text-sm shadow-sm">
        <i class="bi bi-plus-lg mr-1"></i> Add Series
    </a>
    @endauth
    
    @if (isset($slug) && $slug == 'Attending')
        <a href="{!! URL::route('events.export.attending') !!}" class="px-3 py-1.5 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors text-sm" target="_blank">
            <i class="bi bi-download mr-1"></i> Export
        </a>
    @elseif (!isset($slug))
        <a href="{!! URL::route('events.export') !!}" class="px-3 py-1.5 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors text-sm" target="_blank">
            <i class="bi bi-file-text mr-1"></i> Export TXT
        </a>
    @endif
    <a href="{!! URL::route('events.indexIcal') !!}" class="px-3 py-1.5 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors text-sm" target="_blank">
        <i class="bi bi-calendar-event mr-1"></i> Export iCal
    </a>
</div>