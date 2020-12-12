<div class="col-lg-3">
    <div class="bs-component">
        <div class="panel panel-info">

            <div class="panel-heading">
                <h3 class="panel-title">
                    @if (\Carbon\Carbon::now()->format('Y-m-d') == $day->format('Y-m-d'))
                    Today's Events
                    @else
                    {{ $day->format('l M jS Y') }}
                    @endif
                </h3>
            </div>

            <div class="panel-body">
                <?php $events = App\Models\Event::starting($day->format('Y-m-d'))->get(); ?>

                @include('events.list', ['events' => $events])

                <!-- find all series that would fall on this date -->
                <?php $series = App\Models\Series::byNextDate($day->format('Y-m-d')); ?>
                @include('series.list', ['series' => $series])
            </div>
        </div>
    </div>
</div>