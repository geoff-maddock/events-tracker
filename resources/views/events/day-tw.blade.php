<div class="h-full">
    <div class="bg-card rounded-lg border border-border h-full flex flex-col shadow-sm text-foreground">
        <!-- Header -->
        <div class="px-4 py-3 bg-primary/5 border-b border-border rounded-t-lg">
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
            {{-- $events and $series: this day's cards and scheduled series, from the controller --}}
            @if (count($events) > 0)
            <ul class="grid gap-3">
                @foreach ($events as $event)
                    @include('events.single-card-tw', ['event' => $event])
                @endforeach
            </ul>
            @else
            <x-empty-state icon="bi-calendar-x" compact>No events listed today.</x-empty-state>
            @endif

            <!-- Series Section -->
            @if (count($series) > 0)
            <div class="mt-6 pt-6 border-t border-border">
                <h6 class="text-sm font-bold text-muted-foreground uppercase tracking-wider mb-4">Series Occurring Today</h6>
                <ul class="grid gap-3">
                    @php $type = NULL @endphp
                    @foreach ($series as $s)
                        @if ($type !== $s->occurrence_type_id)
                            <li class="text-primary font-semibold text-sm mb-2 mt-4 first:mt-0">
                                {{ $s->occurrenceType->name }}
                            </li>
                            <?php $type = $s->occurrence_type_id; ?>
                        @endif
                        @include('series.single-card-tw', ['series' => $s])
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>