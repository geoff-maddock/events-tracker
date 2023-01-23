<div class="col">
    <div class="card surface-container mb-4">

            <div class="card-header bg-primary">
                <h5 class="my-0 fw-normal">
                    @if (\Carbon\Carbon::now('America/New_York')->format('Y-m-d') == $day->format('Y-m-d'))
                    Today's Events
                    @else
                    {{ $day->format('l F jS Y') }}
                    @endif
                </h5>
            </div>

            <div class="card-body">
                <?php $events = App\Models\Event::starting($day->format('Y-m-d'))->get(); ?>

                @if (count($events) > 0)

                <ul class='day-list'>
                    <?php $month = '';?>
                    @foreach ($events as $event)
                                @include('events.single', ['event' => $event])
                    @endforeach
                </ul>
                
                @else
                    <div><small>No events listed today.</small></div>
                @endif
                

                <!-- find all series that would fall on this date -->
                <?php $series = App\Models\Series::byNextDate($day->format('Y-m-d')); ?>
                @if (count($series) > 0)
                <ul class='day-list'>
                
                    @php $type = NULL @endphp
                
                    @foreach ($series as $s)
                        @if ($type !== $s->occurrence_type_id)
                            <li>
                                <h4>{{ $s->occurrenceType->name }}</h3>
                                <?php $type = $s->occurrence_type_id; ?>
                            </li>
                        @endif
                        @include('series.single', ['series' => $s])
                    @endforeach
                </ul>
                @else
                <div><small>No series listed today.</small></div>
                @endif
                
            </div>
    </div>
</div>