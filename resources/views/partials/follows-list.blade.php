		@if (isset($follows) && $follows)

		<?php $type = NULL;?>
		<ul class='list'>
			@foreach ($follows as $follow)
				<?php $object = $follow->getObject();?>
				@include('entities.single', ['entity' => $entity])
			@endforeach
		</ul>
		@else
			<p><i>None listed</i></p>
		@endif