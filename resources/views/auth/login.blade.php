@extends('layouts.app')

@section('content')
<div class="container">

    <!-- Outer Row -->
    <div class="row justify-content-center">

        <div class="col-xl-6 col-lg-6 col-md-9">

          <div class="card o-hidden border-0 shadow-lg my-5" id="mainlogin">
            <div class="card-body p-0">
              <!-- Nested Row within Card Body -->
              <div class="row">
                <div class="col-lg-10 col-md-9 col-xl-7 mx-auto">
                    <div class="text-center">
                        <img src="{{asset('/storage/logo.png')}}" style="padding:20px;max-width:100%;">
                    </div>
                   <div class="text-center mb-12">
                      <h1 class="ls-tight font-bolder mt-6">Login</h1>
                      <p class="mt-2">Business Phone System Portal</p>
                   </div>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                   <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="user_email" class="col-form-label text-md-end">{{ __('Username') }}</label>
                            <input id="email" type="email" placeholder="Your email address or username" class="form-control @error('user_email') is-invalid @enderror" name="user_email" value="{{ old('user_email') }}" required autocomplete="email" autofocus>

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
                      <div class="mb-4">
                         <div class="d-flex align-items-center justify-content-between">
                            <div><label for="password" class="col-form-label text-md-end">{{ __('Password') }}</label></div>
                            <div class="mb-2">
                                @if (Route::has('password.request'))
                                    <a class="text-sm text-muted text-primary-hover text-underline" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                         </div>
                         <input id="password" type="password" placeholder="Password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                         @error('password')
                             <span class="invalid-feedback" role="alert">
                                 <strong>{{ $message }}</strong>
                             </span>
                         @enderror

                      </div>

                      <div class="mb-5 text-center d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Log in') }}
                        </button>
                      </div>
                   </form>
                </div>
              </div>

            </div>
          </div>

        </div>

    </div>

</div>
@endsection
