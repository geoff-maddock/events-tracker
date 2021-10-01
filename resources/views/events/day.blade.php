<div class="col">
    <div class="card mb-4">
            <div class="card-header bg-primary">
                <h5 class="my-0 fw-normal">
                    @if (\Carbon\Carbon::now()->format('Y-m-d') == $day->format('Y-m-d'))
                    Today's Events
                    @else
                    {{ $day->format('l M jS Y') }}
                    @endif
                </h5>
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