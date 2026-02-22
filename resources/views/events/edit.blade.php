@extends('layouts.app-tw')

@section('title', 'Event Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="w-full">
	<!-- Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-foreground mb-2">Edit Event <span class="text-muted-foreground">{{ $event->name }}</span></h1>
		<div class="text-sm text-muted-foreground">
			@include('events.crumbs-tw', ['slug' => $event->slug ?: $event->id, 'event' => $event])
		</div>
	</div>

	<!-- Actions Menu -->
	<div class="mb-6">
		@include('events.edit.actions', ['event' => $event, 'user' => $user])
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
		<!-- Main Form Column -->
		<div class="lg:col-span-2">
			<div class="bg-card rounded-lg border border-border shadow-sm p-6">
				<form method="POST" action="{{ route('events.update', $event->id) }}" class="space-y-6">
					@include('events.form', ['action' => 'update'])
					@csrf
					@method('PATCH')
				</form>

				<!-- Delete Button -->
				@if ($user && ($event->ownedBy($user) || $user->hasGroup('super_admin')))
				<div class="mt-6 pt-6 border-t border-border">
					<form method="POST" action="{{ route('events.destroy', $event->id) }}" onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
						@csrf
						@method('DELETE')
						<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-md hover:bg-destructive/90 transition-colors">
							<i class="bi bi-trash mr-2"></i>
							Delete Event
						</button>
					</form>
				</div>
				@endif
			</div>

			<!-- Back Button -->
			<div class="mt-6">
				<x-ui.button variant="ghost" href="{{ route('events.show', $event->slug) }}">
					<i class="bi bi-arrow-left mr-2"></i>
					Back to Event
				</x-ui.button>
			</div>
		</div>

		<!-- Photos Sidebar -->
		<div class="lg:col-span-1">
			<!-- Photo Upload -->
			@if ($user && $event->user && (Auth::user()->id === $event->user->id || $user->hasGroup('super_admin') || $event->canUserPostPhoto($user)))
			<div class="rounded-lg border border-border bg-card shadow p-2 pt-2 space-y-4 mb-6">
				<form action="/events/{{ $event->id }}/photos"
					class="dropzone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 text-center cursor-pointer hover:border-gray-400 dark:hover:border-gray-600 transition-colors"
					id="myDropzone"
					method="POST">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
				</form>
			</div>
			@endif

			<!-- Photos Section -->
			@include('partials.photo-gallery-tw', ['event' => $event, 'lightboxGroup' => 'event-gallery'])
		</div>
	</div>
</div>

@stop

@section('scripts.footer')
@if ($user && $event->user && (Auth::user()->id === $event->user->id || $user->hasGroup('super_admin') || $event->canUserPostPhoto($user)))
<script>
$(document).ready(function(){
	// Wait for Dropzone to be available
	var attempts = 0;
	var maxAttempts = 50; // 5 seconds max

	function initDropzone() {
		attempts++;

		if (typeof window.Dropzone === 'undefined') {
			if (attempts >= maxAttempts) {
				console.error('Dropzone failed to load after ' + (maxAttempts * 100) + 'ms');
				return;
			}
			setTimeout(initDropzone, 100);
			return;
		}

		console.log('Dropzone loaded successfully!');
		window.Dropzone.autoDiscover = false;
		var myDropzone = new window.Dropzone('#myDropzone', {
			dictDefaultMessage: "Drop a file here to add an event image. (Max size 5MB)"
		});

		$('div.dz-default.dz-message').css({'color': '#9ca3af', 'opacity': 1, 'background-image': 'none'});

		myDropzone.options.addPhotosForm = {
			maxFilesize: 5,
			accept: ['.jpg','.png','.gif'],
			dictDefaultMessage: "Drop a file here to add a picture",
			init: function () {
				myDropzone.on("success", function (file) {
					location.reload();
				});
				myDropzone.on("successmultiple", function (file) {
					location.reload();
				});
				myDropzone.on("error", function (file, message) {
					Swal.fire({
						title: "Error",
						text: "Error: " + message.message,
						icon: "error",
						confirmButtonColor: "#ef4444",
						confirmButtonText: "Ok",
					}).then(result => {
						location.reload();
					});
				});
			},
			success: console.log('Upload successful')
		};

		myDropzone.options.addPhotosForm.init();
	}

	// Start trying to initialize Dropzone
	initDropzone();
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all flatpickr date/time pickers
    const dateTimePickers = document.querySelectorAll('[data-flatpickr]');

    dateTimePickers.forEach(function(picker) {
        const enableTime = picker.getAttribute('data-enable-time') === 'true';
        const dateFormat = picker.getAttribute('data-date-format') || 'Y-m-d H:i';
        const altFormat = picker.getAttribute('data-alt-format') || 'F j, Y at h:i K';
        const minDate = picker.getAttribute('data-min-date');
        const maxDate = picker.getAttribute('data-max-date');

        const config = {
            enableTime: enableTime,
            dateFormat: dateFormat,
            altInput: true,
            altFormat: altFormat,
            time_24hr: false,
            minDate: minDate,
            maxDate: maxDate,
        };

        if (picker.id === 'start_at') {
            config.onChange = function(selectedDates) {
                if (selectedDates.length === 0) return;
                const d = selectedDates[0];
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                checkEventsOnDate(year, month, day, '{{ $event->slug }}');
            };
        }

        flatpickr(picker, config);
    });
});

function checkEventsOnDate(year, month, day, excludeSlug) {
    const warning = document.getElementById('events-on-date-warning');
    const list = document.getElementById('events-on-date-list');
    if (!warning || !list) return;

    fetch(`/api/events/by-date/${year}/${month}/${day}`)
        .then(r => r.json())
        .then(data => {
            const events = (data.data || []).filter(e => e.slug !== excludeSlug);
            if (events.length === 0) {
                warning.classList.add('hidden');
                return;
            }
            list.innerHTML = events.map(e => {
                const venue = e.venue ? ` @ ${e.venue.name}` : '';
                const time = e.start_at
                    ? new Date(e.start_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})
                    : '';
                return `<li><a href="/events/${e.slug}" target="_blank" class="underline hover:no-underline">${e.name}</a>${venue}${time ? ' &mdash; ' + time : ''}</li>`;
            }).join('');
            warning.classList.remove('hidden');
        })
        .catch(() => warning.classList.add('hidden'));
}
</script>
@stop