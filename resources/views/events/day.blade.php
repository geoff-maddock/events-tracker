<div class="col">
    <div class="card mb-4 rounded-3 shadow-sm">
            <div class="card-header py-3 bg-primary">
                <h3 class="my-0 fw-normal">
                    @if (\Carbon\Carbon::now()->format('Y-m-d') == $day->format('Y-m-d'))
                    Today's Events
                    @else
                    {{ $day->format('l M jS Y') }}
                    @endif
                </h3>
            </div>

            <div class="card-body">
                <?php $events = App\Models\Event::starting($day->format('Y-m-d'))->get(); ?>

                @include('events.list', ['events' => $events])

                <!-- find all series that would fall on this date -->
                <?php $series = App\Models\Series::byNextDate($day->format('Y-m-d')); ?>
                @include('series.list', ['series' => $series])
            </div>
    </div>
</div>