@extends('app')

@section('title', 'Register User')

@section('content')
<section class="vh-100">
	<div class="container-fluid h-custom">
	  <div class="row d-flex justify-content-center align-items-center h-100 mt-5">
		<div class="col-md-9 col-lg-6 col-xl-5">
		  <img src="images/register-walk.png" class="img-fluid rounded-med" alt="Sign In">
		</div>
		<div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
			@if (count($errors) > 0)
			<div class="alert alert-danger">
				<strong>Whoops!</strong> There were some problems with your input.<br><br>
				<ul>
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif
		@if (session('error'))
		<div class="alert alert-danger">
			 {{ session('error') }}
		</div>
		@endif

		
		 <h4>Register</h4>
		  <form role="form" method="POST" action="{{ url('/register') }}">
			<x-honeypot />
			{{ csrf_field() }}

			<div class="form-outline mb-4 {{ $errors->has('name') ? ' has-error' : '' }}">
				<label for="name" class="col-md-4 control-label">Name</label>

				<div class="col-md-6">
					<input id="name" type="text" class="form-control form-control-lg form-background" name="name" value="{{ old('name') }}" required autofocus>

					@if ($errors->has('name'))
						<span class="help-block">
							<strong>{{ $errors->first('name') }}</strong>
						</span>
					@endif
				</div>
			</div>

			<div class="form-outline mb-4 {{ $errors->has('email') ? ' has-error' : '' }}">
				<label for="email" class="col-md-4 control-label">E-Mail Address</label>

				<div class="col-md-6">
					<input id="email" type="email" class="form-control form-control-lg form-background" name="email" value="{{ old('email') }}" required>

					@if ($errors->has('email'))
						<span class="help-block">
							<strong>{{ $errors->first('email') }}</strong>
						</span>
					@endif
				</div>
			</div>

			<div class="form-outline mb-4 {{ $errors->has('password') ? ' has-error' : '' }}">
				<label for="password" class="col-md-4 control-label">Password</label>

				<div class="col-md-6">
					<input id="password" type="password" class="form-control form-control-lg form-background" name="password" required>

					@if ($errors->has('password'))
						<span class="help-block">
							<strong>{{ $errors->first('password') }}</strong>
						</span>
					@endif
				</div>
			</div>

			<div class="form-outline mb-4">
				<label for="password-confirm" class="col-md-4 control-label">Confirm Password</label>

				<div class="col-md-6">
					<input id="password-confirm" type="password" class="form-control form-control-lg form-background" name="password_confirmation" required>
				</div>
			</div>
  
			<div class="text-center text-lg-start mt-4 pt-2">
				<button type="submit" class="btn btn-primary btn-lg" >
					Register
				</button>
			  <p class="small fw-bold mt-2 pt-1 mb-0">Already have an account? <a href="{{ url('/login') }}"
				  class="link-danger">Login</a></p>
			</div>
  
		  </form>
		</div>
	  </div>
	</div>
  </section>
@endsection
