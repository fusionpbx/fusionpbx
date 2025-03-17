@extends('layouts.simple')

@section('content')
    <div class="login-box">
        <div class="login-logo">
            <img src="{{asset('/storage/logo.png')}}" style="padding:20px;max-width:100%;">
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Business Phone System Portal</p>

                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="input-group mb-3">
                        <input id="email" type="email" class="form-control @error('user_email') is-invalid @enderror" placeholder="Email" name="user_email" value="{{ old('user_email') }}" required autocomplete="email" autofocus />
                        <div class="input-group-text"><span class="bi bi-envelope"></span></div>

                        @error('user_email')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        @if (session('error'))
                            <span class="invalid-feedback" role="alert" style="display:block;">
                                {{ session('error') }}
                            </span>
                        @endif
                    </div>
                    <div class="input-group mb-3">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" name="password" required autocomplete="current-password" />
                        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>

                        @error('password')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} />
                                <label class="form-check-label" for="remember">
                                    {{ __('Remember Me') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">{{ __('Log in') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="social-auth-links text-center mb-3 d-grid gap-2">
                    <p>- OR -</p>
                    <a href="#" class="btn btn-primary">
                        <i class="bi bi-facebook me-2"></i> Sign in using Facebook
                    </a>
                    <a href="#" class="btn btn-danger">
                        <i class="bi bi-google me-2"></i> Sign in using Google+
                    </a>
                </div>
                <p class="mb-1">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">{{ __('I forgot my password') }}</a>
                    @endif
                </p>
                <p class="mb-0">
                    <a href="register.html" class="text-center"> Register a new membership </a>
                </p>
            </div>
        </div>
    </div>
@endsection
