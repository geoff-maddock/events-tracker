		@if (isset($entities) && $entities)

		<?php $type = NULL;?>
		<ul class='list'>
			@foreach ($entities as $entity)
	
			<li style="clear: both;">
				@if ($primary = $entity->getPrimaryPhoto())
				<div style="float: left; padding: 5px;">
						<img src="/{!! str_replace(' ','%20',$entity->getPrimaryPhoto()->thumbnail) !!}" alt="{{ $entity->name}}"  style="max-width: 100px; ">
				</div>
				@endif

				{!! link_to_route('entities.show', $entity->name, [$entity->id], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}


				@if ($signedIn && $entity->ownedBy($user))
				<a href="{!! route('entities.edit', ['id' => $entity->id]) !!}">
				<span class='glyphicon glyphicon-pencil'></span></a>
				@endif 
				
				@if ($type = $entity->entityType)
					<br><b>{{ $entity->entityType->name }}</b>
				@endif

				@if ($entity->getPrimaryLocationAddress() )
					{{ $entity->getPrimaryLocationAddress() }} - {{ $entity->getPrimaryLocation()->neighborhood }} 	<br>
				@endif
				<ul class="list">
				@if ($events = $entity->futureEvents()->take(1))
				@foreach ($events as $event)
					<li>Next Event:
					<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
				@endforeach
				@endif
				@if ($events = $entity->pastEvents()->take(3))
				@foreach ($events as $event)
					<li>Past Event:
					<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
				@endforeach
				@endif
				</ul>
			</li>
			@endforeach
		</ul>
		@else
			<p><i>None listed</i></p>
		@endif