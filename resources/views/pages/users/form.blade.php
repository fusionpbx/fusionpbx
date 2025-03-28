@extends('layouts.app')

@section('content')
<div class="container-fluid ">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($user) ? 'Edit User' : 'Create User' }}
            </h3>
        </div>

        <form action="{{ isset($user) ? route('users.update', $user->user_uuid) : route('users.store') }}"
              method="POST">
            @csrf
            @if(isset($user))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username" class="form-label">User Name</label>
                            <input
                                type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                id="username"
                                name="username"
                                placeholder="Enter user name"
                                value="{{ old('user_name', $user->username ?? '') }}"
                                required
                            >
                            @error('username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                placeholder="Enter password"
                                value=""
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="password_confirm" class="form-label">Confirm password</label>
                            <input
                                type="text"
                                class="form-control @error('password_confirm') is-invalid @enderror"
                                id="password_confirm"
                                name="password_confirm"
                                placeholder="Confirm password"
                                value=""
                                required
                            >
                            @error('password_confirm')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="user_email" class="form-label">E-mail</label>
                            <input
                                type="text"
                                class="form-control @error('user_email') is-invalid @enderror"
                                id="user_email"
                                name="user_email"
                                placeholder="Enter e-mail"
                                value="{{ old('user_name', $user->user_email ?? '') }}"
                                required
                            >
                            @error('user_email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user_language" class="form-label">Language</label>
                            <select
                                class="form-select @error('user_language') is-invalid @enderror"
                                id="user_language"
                                name="user_language"
                            >
                                <option value="">Select language</option>
                                @foreach($languages as $language)
                                    <option value="{{ $language->language_uuid }}"
										{{ old('user_language', $selectedLanguage) == $language->code ? 'selected' : '' }}>
                                        {{ $language->language }} [{{ $language->code }}]
                                    </option>
                                @endforeach
                            </select>
                            @error('user_language')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select
                                class="form-select @error('timezone') is-invalid @enderror"
                                id="timezone"
                                name="timezone"
                            >
                                <option value="">Select timezone</option>
                                @foreach($timezones as $timezone)
                                    <option value="{{ $timezone }}"
									    {{ old('user_timezone', $selectedTimezone) == $timezone ? 'selected' : '' }}>
                                        {{ $timezone }}
                                    </option>
                                @endforeach
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_uuid" class="form-label">Contact</label>
                            <select
                                class="form-select @error('contact_uuid') is-invalid @enderror"
                                id="contact_uuid"
                                name="contact_uuid"
                            >
                                <option value="">Select contact</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->contact_uuid }}"
										{{ old('contact_uuid', $user->contact_uuid) == $contact->contact_uuid ? 'selected' : '' }}>
                                        {{ $contact->contact_nickname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contact_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="groups" class="form-label">Groups</label>
							@foreach($groups as $group)
							@php
								$checked = $user->groups->contains('group_uuid', $group->group_uuid);
							@endphp
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="groups[]" value="{{ $group->group_uuid }}" @if($checked) checked @endif>
								<label class="form-check-label">{{ $group->group_name }}</label>
							</div>
							@endforeach
                            @error('groups')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain_uuid" class="form-label">Domain</label>
                            <select
                                class="form-select @error('domain_uuid') is-invalid @enderror"
                                id="domain_uuid"
                                name="domain_uuid"
                            >
                                <option value="">Select domain</option>
                                @foreach($domains as $domain)
                                    <option value="{{ $domain->domain_uuid }}"
										{{ old('domain_uuid', $user->domain_uuid) == $domain->domain_uuid ? 'selected' : '' }}>
                                        {{ $domain->domain_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('domain_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($user) ? 'Update User' : 'Create User' }}
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
