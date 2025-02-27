@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">

        <div class="col-xl-6 col-lg-6 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5" id="passwordreset">
                <div class="col-lg-10 col-md-9 col-xl-7 mx-auto">
                    <div class="text-center mb-12">
                        <div class="text-center">
                            <img src="{{asset('/storage/logo.png')}}" style="padding:20px;max-width:100%;">
                        </div>
                        <h1 class="ls-tight font-bolder mt-6">Password Reset</h1>
                        <p class="mt-2">Enter your email and we will send you a reset link</p>
                    </div>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="mb-5">
                            <label for="email" class="col-form-label text-md-end">{{ __('E-Mail Address') }}</label> 
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                        </div>
                        <div class="text-center mb-5">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Send Password Reset Link') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="text-muted">Back to <a href="{{ route('login') }}" class="text-muted ms-1"><b>Log In</b></a></p>
                </div> <!-- end col -->
            </div>
        </div>

    </div>
</div>
@endsection
