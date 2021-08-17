@extends('app')

@section('content')
<section class="vh-100">
	<div class="container-fluid h-custom">
	  <div class="row d-flex justify-content-center align-items-center h-100">
		<div class="col-md-9 col-lg-6 col-xl-5">
		  <img src="https://mdbootstrap.com/img/Photos/new-templates/bootstrap-login-form/draw2.png" class="img-fluid"
			alt="Sample image">
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
		  <form role="form" method="POST" action="{{ url('/login') }}">
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
			<div class="d-flex flex-row align-items-center justify-content-center justify-content-lg-start">
			  <p class="lead fw-normal mb-0 me-3">Sign in with</p>
			  <a href="{{url('/redirect')}}" class="btn btn-primary">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
					<path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
				  </svg>
				</a>
  
			</div>
  
			<div class="divider d-flex align-items-center my-4">
			  <p class="text-center fw-bold mx-3 mb-0">Or</p>
			</div>
  
			<!-- Email input -->
			<div class="form-outline mb-4">
			  <input type="email" name="email" id="email" class="form-control form-control-lg"
				placeholder="Enter a valid email address" />
			  <label class="form-label" for="email">Email address</label>
			</div>
  
			<!-- Password input -->
			<div class="form-outline mb-3">
			  <input type="password" name="password" id="password" class="form-control form-control-lg"
				placeholder="Enter password" />
			  <label class="form-label" for="password">Password</label>
			</div>
  
			<div class="d-flex justify-content-between align-items-center">
			  <!-- Checkbox -->
			  <div class="form-check mb-0">
				<input class="form-check-input me-2" type="checkbox" value="" id="form2Example3" />
				<label class="form-check-label" for="form2Example3">
				  Remember me
				</label>
			  </div>
			  <a href="href="{{ url('/password/reset') }}" >Forgot password?</a>
			</div>
  
			<div class="text-center text-lg-start mt-4 pt-2">
			  <button type="submit" class="btn btn-primary btn-lg"
				style="padding-left: 2.5rem; padding-right: 2.5rem;">Login</button>
			  <p class="small fw-bold mt-2 pt-1 mb-0">Don't have an account? <a href="{{ url('/register') }}"
				  class="link-danger">Register</a></p>
			</div>
  
		  </form>
		</div>
	  </div>
	</div>

  </section>

@endsection
