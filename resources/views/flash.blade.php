@if (session()->has('flash_message'))
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const options = {
			title: @json((string) session('flash_message.title')),
			text: @json((string) session('flash_message.message')),
			icon: @json((string) session('flash_message.level')),
			timer: 2500,
			showConfirmButton: false,
			preConfirm: function() {
				return new Promise(function(resolve) {
					setTimeout(function() {
						resolve()
					}, 2000)
				})
			}
		};

		if (window.Swal && typeof window.Swal.fire === 'function') {
			window.Swal.fire(options);
			return;
		}

		window.alert(options.text || options.title || 'Notification');
	});
	</script>
@endif
