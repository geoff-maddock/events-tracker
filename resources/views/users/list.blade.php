		@if (isset($users) && $users)

		<?php $type = NULL;?>
		<ul class='list'>
			@foreach ($users as $user)

					@include('users.single', ['user' => $user])

			@endforeach
		</ul>
		@else
			<p><i>None listed</i></p>
		@endif

@section('scripts.footer')


@stop