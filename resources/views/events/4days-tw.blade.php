<div class="flex flex-col gap-4 sm:gap-6 min-w-0">
    <!-- Navigation Controls -->
    <div class="flex flex-wrap items-stretch justify-center sm:justify-between gap-1 sm:gap-2 bg-card p-2 sm:p-4 rounded-lg border border-border shadow-sm min-w-0">
        <!-- Past Controls -->
        <div class="flex gap-1 sm:gap-2 flex-1 sm:flex-initial">
            {!! link_to_route('events.upcoming', '< Past Week', ['date' => $prev_day_window->format('Ymd')], ['class' => 'flex-1 sm:flex-initial px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap text-center']) !!}
            {!! link_to_route('events.upcoming', '< Past Day', ['date' => $prev_day->format('Ymd')], ['class' => 'flex-1 sm:flex-initial px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap text-center']) !!}
        </div>

        <!-- Future Controls -->
        <div class="flex gap-1 sm:gap-2 flex-1 sm:flex-initial">
            {!! link_to_route('events.upcoming', 'Future Day >', ['date' => $next_day->format('Ymd')], ['class' => 'flex-1 sm:flex-initial px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap text-center']) !!}
            {!! link_to_route('events.upcoming', 'Future Week >', ['date' => $next_day_window->format('Ymd')], ['class' => 'flex-1 sm:flex-initial px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap text-center']) !!}
        </div>
    </div>

    <!-- Events Grid -->
    <div class="home grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3 sm:gap-4 w-full min-w-0">
        @for ($offset = 0; $offset < 4; $offset++)
        <?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
            <section class="day min-h-[500px]" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
                @include('events.day-tw', ['day' => $day, 'position' => $offset ])
            </section>
        @endfor
    </div>

    <!-- Next Events Button -->
    <div class="flex justify-center mt-4 sm:mt-6" id="next-events">
        {!! link_to_route('events.add', 'Load Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'px-4 sm:px-6 py-2.5 sm:py-3 bg-accent text-foreground border-2 border-primary font-semibold text-sm sm:text-base rounded-lg hover:bg-accent/80 transition-colors shadow-lg next-events whitespace-nowrap']) !!}
    </div>
</div>