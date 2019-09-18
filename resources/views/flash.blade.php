@if (session()->has('flash_message'))
	<script>
	swal({
	  title: "{{ session('flash_message.title') }}",
	  text: "{!! session('flash_message.message') !!}",
	  type: "{{ session('flash_message.level') }}",
	  timer: 1700,
	  showConfirmButton: false,
      closeOnConfirm: true,
      closeOnCancel: true
	});
	</script>
@endif
