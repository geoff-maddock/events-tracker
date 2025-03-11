@extends('app')

@section('title', 'Login')

@section('content')
<section class="vh-100">
	<div class="container-fluid h-custom">
	  <div class="row d-flex justify-content-center align-items-center h-100 mt-5">
		<div class="col-md-9 col-lg-6 col-xl-5">
		  <img src="images/sign-in-computer.png" class="img-fluid" alt="Sign In">
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

		
		 <h4>Login</h4>
		  <form role="form" method="POST" action="{{ url('/login') }}">
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
  
			<!-- Email input -->
			<div class="form-outline mb-4">
			  <input type="email" name="email" id="email" class="form-control form-control-lg form-background"
				placeholder="Enter a valid email address" autocomplete="on"/>
			</div>
  
			<!-- Password input -->
			<div class="form-outline mb-3">
			  <input type="password" name="password" id="password" class="form-control form-control-lg form-background"
				placeholder="Enter password" autocomplete="on"/>
			</div>
  
			<div class="d-flex justify-content-between align-items-center">

			  <a href="/password/reset" >Forgot password?</a>
			</div>
  
			<div class="text-center text-lg-start mt-4 pt-2">
			  <button type="submit" class="btn btn-primary btn-lg px-4">Login</button>
			  <p class="small fw-bold mt-2 pt-1 mb-0">Don't have an account? <a href="{{ url('/register') }}"
				  class="link-danger">Register</a></p>
			</div>
  
		  </form>
		</div>
	  </div>
	</div>

  </section>

@endsection
