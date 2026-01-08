<div class="flex flex-col gap-6">
    <!-- Navigation Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-card p-4 rounded-lg border border-border shadow-sm">
        <!-- Past Controls -->
        <div class="flex gap-2">
            {!! link_to_route('events.upcoming', '< Past Week', ['date' => $prev_day_window->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap']) !!}
            {!! link_to_route('events.upcoming', '< Past Day', ['date' => $prev_day->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap']) !!}
        </div>

        <!-- Future Controls -->
        <div class="flex gap-2">
            {!! link_to_route('events.upcoming', 'Future Day >', ['date' => $next_day->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap']) !!}
            {!! link_to_route('events.upcoming', 'Future Week >', ['date' => $next_day_window->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent hover:text-foreground transition-colors whitespace-nowrap']) !!}
        </div>
    </div>

    <!-- Events Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 w-full">
        @for ($offset = 0; $offset < 4; $offset++)
        <?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
            <section class="day min-h-[500px]" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
                @include('events.day-tw', ['day' => $day, 'position' => $offset ])
            </section>
        @endfor
    </div>

    <!-- Next Events Button -->
    <div class="flex justify-center mt-6" id="next-events">
        {!! link_to_route('events.add', 'Load Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'px-6 py-3 bg-accent text-foreground border-2 border-primary font-semibold rounded-lg hover:bg-accent/80 transition-colors shadow-lg next-events whitespace-nowrap']) !!}
    </div>
</div>