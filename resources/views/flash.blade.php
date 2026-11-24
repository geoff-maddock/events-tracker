{{--
    The flash() message of the last request, as data for the toast script
    (resources/assets/js/toast.js), which shows it when the page loads.
--}}
@if (session()->has('flash_message'))
	@php
		// flash() stores [title, message, level]; show a bare string as the message rather than an empty toast
		$flashMessage = session('flash_message');
		$flashMessage = is_array($flashMessage) ? $flashMessage : ['title' => '', 'message' => (string) $flashMessage, 'level' => 'info'];
		$flashData = [
			'title' => (string) ($flashMessage['title'] ?? ''),
			'message' => (string) ($flashMessage['message'] ?? ''),
			'level' => (string) ($flashMessage['level'] ?? 'info'),
		];
	@endphp
	<script type="application/json" id="flash-data">@json($flashData)</script>
@endif
