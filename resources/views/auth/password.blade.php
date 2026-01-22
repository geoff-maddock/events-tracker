@extends('layouts.app-tw')

@section('title', 'Reset Password')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
	<div class="w-full max-w-md">
		<div class="bg-card rounded-lg border border-border shadow-sm p-8">
			<div class="text-center mb-8">
				<h1 class="text-2xl font-bold text-foreground">Reset Password</h1>
				<p class="text-muted-foreground mt-2">We'll email you a link to reset your password</p>
			</div>

			@if (count($errors) > 0)
				<div class="mb-6 p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
					<div class="flex items-start gap-3">
						<i class="bi bi-exclamation-triangle text-destructive text-lg mt-0.5"></i>
						<div>
							<p class="font-semibold text-destructive">There were some problems with your input.</p>
							<ul class="mt-2 text-sm text-destructive/90 list-disc list-inside">
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					</div>
				</div>
			@endif

			@if (session('status'))
				<div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
					<div class="flex items-center gap-3">
						<i class="bi bi-check-circle text-green-500"></i>
						<span class="text-green-500">{{ session('status') }}</span>
					</div>
				</div>
			@endif

			<form method="POST" action="{{ url('/password/email') }}" class="space-y-6">
				@csrf

				<x-ui.form-group name="email" label="Email Address" :error="$errors->first('email')">
					<x-ui.input
						type="email"
						name="email"
						id="email"
						:value="old('email')"
						placeholder="Enter your email address"
						:hasError="$errors->has('email')"
						autocomplete="email"
						required />
				</x-ui.form-group>

				<x-ui.button type="submit" variant="default" class="w-full">
					Send Password Reset Link
				</x-ui.button>

				<p class="text-center text-sm text-muted-foreground">
					Remember your password?
					<a href="{{ url('/login') }}" class="text-primary hover:underline font-medium">Sign In</a>
				</p>
			</form>
		</div>
	</div>
</div>
@endsection
