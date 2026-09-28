@if (session()->has('flash_message'))
	@php
		// flash() stores [title, message, level]; show a bare string as the message rather than an empty alert
		$flashMessage = session('flash_message');
		$flashMessage = is_array($flashMessage) ? $flashMessage : ['title' => '', 'message' => (string) $flashMessage, 'level' => 'info'];
	@endphp
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const options = {
			title: @json((string) ($flashMessage['title'] ?? '')),
			text: @json((string) ($flashMessage['message'] ?? '')),
			icon: @json((string) ($flashMessage['level'] ?? 'info')),
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
