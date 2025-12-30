<div class="flex flex-col gap-6">
    <!-- Navigation Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-dark-surface p-4 rounded-lg border border-gray-200 dark:border-dark-border shadow-sm">
        <!-- Past Controls -->
        <div class="flex gap-2">
            {!! link_to_route('events.upcoming', '< Past Week', ['date' => $prev_day_window->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-lg hover:bg-gray-200 dark:hover:bg-dark-border hover:text-gray-900 dark:hover:text-white transition-colors whitespace-nowrap']) !!}
            {!! link_to_route('events.upcoming', '< Past Day', ['date' => $prev_day->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-lg hover:bg-gray-200 dark:hover:bg-dark-border hover:text-gray-900 dark:hover:text-white transition-colors whitespace-nowrap']) !!}
        </div>
        
        <!-- Future Controls -->
        <div class="flex gap-2">
            {!! link_to_route('events.upcoming', 'Future Day >', ['date' => $next_day->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-lg hover:bg-gray-200 dark:hover:bg-dark-border hover:text-gray-900 dark:hover:text-white transition-colors whitespace-nowrap']) !!}
            {!! link_to_route('events.upcoming', 'Future Week >', ['date' => $next_day_window->format('Ymd')], ['class' => 'px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-lg hover:bg-gray-200 dark:hover:bg-dark-border hover:text-gray-900 dark:hover:text-white transition-colors whitespace-nowrap']) !!}
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
    <div class="flex justify-center" id="next-events">
        {!! link_to_route('events.add', 'Load Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary-hover transition-colors shadow-lg next-events whitespace-nowrap']) !!}
    </div>

    <script type="text/javascript">
        // init app module on document load
        $(function()
        {
            Home.loadDays();
        });
    </script>
</div>