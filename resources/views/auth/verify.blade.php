@extends('app')

@section('title', 'Verify Email')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h2>{{ __('Verify Your Email Address') }}</h2></div>

                    <div class="card-body">
                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                {{ __('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif

                        {{ __('Before proceeding, please check your email for a verification link.') }}<br>
                        {{ __('If you did not receive the email') }}, 
                        <a  onclick="event.preventDefault(); document.getElementById('email-form').submit();">{{ __('click here to request another') }}</a>.
                        <form id="email-form" action="{{ route('verification.resend') }}" method="POST" class="d-none">@csrf</form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection