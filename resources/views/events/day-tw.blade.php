<div class="h-full">
    <div class="bg-white dark:bg-dark-surface rounded-lg border border-gray-200 dark:border-dark-border h-full flex flex-col shadow-sm text-gray-900 dark:text-gray-100">
        <!-- Header -->
        <div class="px-4 py-3 bg-primary/5 dark:bg-primary/10 border-b border-gray-200 dark:border-dark-border rounded-t-lg">
            <h5 class="text-lg font-semibold text-primary m-0">
                @if (\Carbon\Carbon::now('America/New_York')->format('Y-m-d') == $day->format('Y-m-d'))
                Today's Events
                @else
                {{ $day->format('l F jS Y') }}
                @endif
            </h5>
        </div>

        <!-- Body -->
        <div class="p-4 flex-grow overflow-y-auto custom-scrollbar">
            <?php $events = App\Models\Event::with('series')->starting($day->format('Y-m-d'))->get(); ?>

            @if (count($events) > 0)
            <ul class="space-y-4">
                @foreach ($events as $event)
                    @include('events.single-tw', ['event' => $event])
                @endforeach
            </ul>
            @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="bi bi-calendar-x text-3xl mb-2 block"></i>
                <small>No events listed today.</small>
            </div>
            @endif

            <!-- Series Section -->
            <?php $series = App\Models\Series::byNextDate($day->format('Y-m-d'), $events); ?>
            @if (count($series) > 0)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-dark-border">
                <h6 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Series Occurring Today</h6>
                <ul class="space-y-4">
                    @php $type = NULL @endphp
                    @foreach ($series as $s)
                        @if ($type !== $s->occurrence_type_id)
                            <li class="text-primary font-semibold text-sm mb-2 mt-4 first:mt-0">
                                {{ $s->occurrenceType->name }}
                            </li>
                            <?php $type = $s->occurrence_type_id; ?>
                        @endif
                        @include('series.single-tw', ['series' => $s])
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>