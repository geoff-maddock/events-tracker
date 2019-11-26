@if (session()->has('flash_message'))
	<script>
	Swal.fire({
	  title: "{{ session('flash_message.title') }}",
	  text: "{!! session('flash_message.message') !!}",
	  type: "{{ session('flash_message.level') }}",
	  timer: 2500,
	  showConfirmButton: false,
      closeOnConfirm: true,
      closeOnCancel: true
	});
	</script>
@endif
