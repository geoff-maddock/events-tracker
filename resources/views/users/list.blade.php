		@if (isset($users) && $users)

		<?php $type = NULL;?>
		<ul class='list col-md-6'>
			@foreach ($users as $user)

					@include('users.single', ['user' => $user])

			@endforeach
		</ul>
		@else
			<p><i>None listed</i></p>
		@endif

@section('scripts.footer')
	<script type="text/javascript">
		$('button.delete').on('click', function(e){
			e.preventDefault();
			var form = $(this).parents('form');
			var type = $(this).data('type');
			swal({
					title: "Are you sure?",
					text: "You will not be able to recover this "+type+"!",
					type: "warning",
					showCancelButton: true,
					confirmButtonColor: "#DD6B55",
					confirmButtonText: "Yes, delete it!",
					closeOnConfirm: true
				},
				function(isConfirm){
					if (isConfirm) {
						form.submit();
					};
					//
				});
		})
	</script>

@stop