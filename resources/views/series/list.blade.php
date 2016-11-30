	<ul class='event-list'>

	<?php $type = NULL;?>
		@foreach ($series as $series)
		@if ($type != $series->occurrence_type_id)
		<li>			<br style="clear: left;"/>
				<h3>{{ $series->occurrenceType->name }}</h3>
				<?php $type = $series->occurrence_type_id?>
		</li>
		@endif

		<li style="clear: both;">
			@if ($primary = $series->getPrimaryPhoto())
			<div style="float: left; padding: 5px;">
					<img src="/{{ $series->getPrimaryPhoto()->thumbnail }}" alt="{{ $series->name}}"  style="max-width: 100px; ">
			</div>
			@endif
		 	{!! link_to_route('series.show', $series->name, [$series->id]) !!} {{ $series->short }}

			@if ($signedIn && $series->ownedBy($user))
			<a href="{!! route('series.edit', ['id' => $series->id]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
			<a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}" title="Create the next occurrence of {{ $series->name }}"><span class='glyphicon glyphicon-fire'></span></a>
			@endif
			<br>
			{{ $series->occurrenceType->name }}  {{ $series->occurrenceRepeat() }} 
			@if ($series->cancelled_at == NULL)
			next is 
			{{ $series->nextEvent() ? $series->nextEvent()->start_at->format('l F jS Y') : $series->cycleFromFoundedAt()->format('l F jS Y') }}
			
			@else
			<br>Founded {!! $series->founded_at ? $series->founded_at->format('l F jS Y') : 'unknown'!!}<br>
			Cancelled {!! $series->cancelled_at ? $series->cancelled_at->format('l F jS Y') : 'unknown'!!}
			@endif

			@if ($venue = $series->venue)
			<br><a href="{!! route('entities.show', ['id' => $series->venue->id]) !!}">{{ $series->venue->name }}</a> at {{ $series->venue->getPrimaryLocationAddress() }}
			@endunless
			@if ($event = $series->nextEvent())
			<br>Next Event is {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} 
			@endif

			<P>
			@unless ($series->entities->isEmpty())
			Related:
				@foreach ($series->entities as $entity)
				<span class="label label-tag"><a href="/series/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
				@endforeach
			@endunless

			@unless ($series->tags->isEmpty())
			Tags:
			@foreach ($series->tags as $tag)
				<span class="label label-tag"><a href="/series/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
				@endforeach 
			@endunless
			</P>
		</li> 
	@endforeach 
 	 </ul> 
  
	<br>