@if (session()->has('flash_message'))
	<script>
	Swal.fire({
	  title: "{{ session('flash_message.title') }}",
	  text: "{!! session('flash_message.message') !!}",
	  type: "{{ session('flash_message.level') }}",
	  timer: 2500,
	  showConfirmButton: false,
		preConfirm: function() {
			return new Promise(function(resolve) {
				setTimeout(function() {
					resolve()
				}, 2000)
			})
		}
	});
	</script>
@endif
