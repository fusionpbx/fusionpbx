@extends('layouts.app')

@section('content')
    <div class="container-fluid ">
        <div class="card card-primary mt-3 card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    {{ isset($gateway) ? 'Edit Gateway' : 'Create Gateway' }}
                </h3>

                @if (isset($gateway))
                    @can('gateway_add')
                    <div class="card-tools">
                        <a href="{{route('gateways.copy', $gateway->gateway_uuid)}}" class="btn btn-primary btn-sm">
                            <i class="fa fa-clone" aria-hidden="true"></i> {{ __('Copy') }}
                        </a>
                    </div>
                    @endcan
                @endif
            </div>

            <form action="{{ isset($gateway) ? route('gateways.update', $gateway->gateway_uuid) : route('gateways.store') }}"
                method="POST">
                @csrf
                @if (isset($gateway))
                    @method('PUT')
                @endif

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gateway" class="form-label">Gateway</label>
                                <input type="text" class="form-control @error('gateway') is-invalid @enderror"
                                    id="gateway" name="gateway" placeholder="Enter gateway name"
                                    value="{{ old('gateway', $gateway->gateway ?? '') }}" required>
                                @error('gateway')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="from_user" class="form-label">From User</label>
                                <input type="text" class="form-control @error('from_user') is-invalid @enderror"
                                    id="from_user" name="from_user" placeholder="From User"
                                    value="{{ old('from_user', $gateway->from_user ?? '') }}">
                                @error('from_user')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>


                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="from_domain" class="form-label">From Domain</label>
                                <input type="text" class="form-control @error('from_domain') is-invalid @enderror"
                                    id="from_domain" name="from_domain" placeholder="From Domain"
                                    value="{{ old('from_domain', $gateway->from_domain ?? '') }}">
                                @error('from_domain')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6 advancedSection" style="display: block;">
                            <div class="form-group">
                                <label for="domain_uuid" class="form-label">Domain</label>
                                <select class="form-select @error('domain_uuid') is-invalid @enderror" id="domain_uuid"
                                    name="domain_uuid" required>
                                    <option value="">Select Domain</option>
                                    @foreach ($domains as $domain)
                                        <option value="{{ $domain->domain_uuid }}"
                                            {{ old('domain_uuid', $gateway->domain_uuid ?? '') == $domain->domain_uuid ? 'selected' : '' }}>
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


                    <h5 class="mt-4 mb-3">Credentials</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                    id="username" name="username" placeholder="Username"
                                    value="{{ old('username', $gateway->username ?? '') }}">
                                @error('username')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password" placeholder="Password"
                                    value="{{ old('password', $gateway->password ?? '') }}">
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label for="auth_username" class="form-label">Authentication Username</label>
                                <input type="text" class="form-control @error('auth_username') is-invalid @enderror"
                                    id="auth_username" name="auth_username" placeholder="Authentication username"
                                    value="{{ old('auth_username', $gateway->auth_username ?? '') }}">
                                @error('auth_username')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">SIP Configuration</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="realm" class="form-label">Realm</label>
                                <input type="text" class="form-control @error('realm') is-invalid @enderror"
                                    id="realm" name="realm" placeholder="Realm"
                                    value="{{ old('realm', $gateway->realm ?? '') }}">
                                @error('realm')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Proxy Configuration</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="proxy" class="form-label">Proxy</label>
                                <input type="text" class="form-control @error('proxy') is-invalid @enderror"
                                    id="proxy" name="proxy" placeholder="Proxy"
                                    value="{{ old('proxy', $gateway->proxy ?? '') }}">
                                @error('proxy')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 advancedSection" style="display: block;">
                            <div class="form-group">
                                <label for="register_proxy" class="form-label">Register Proxy</label>
                                <input type="text" class="form-control @error('register_proxy') is-invalid @enderror"
                                    id="register_proxy" name="register_proxy" placeholder="Register Proxy"
                                    value="{{ old('register_proxy', $gateway->register_proxy ?? '') }}">
                                @error('register_proxy')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label for="outbound_proxy" class="form-label">Outbound Proxy</label>
                                <input type="text" class="form-control @error('outbound_proxy') is-invalid @enderror"
                                    id="outbound_proxy" name="outbound_proxy" placeholder="Outbound Proxy"
                                    value="{{ old('outbound_proxy', $gateway->outbound_proxy ?? '') }}">
                                @error('outbound_proxy')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Registration Configuration</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="expire_seconds" class="form-label">Expire Seconds</label>
                                <input type="number" class="form-control @error('expire_seconds') is-invalid @enderror"
                                    id="expire_seconds" name="expire_seconds" placeholder="Expire seconds"
                                    min="1" max="65535"
                                    value="{{ old('expire_seconds', $gateway->expire_seconds ?? '800') }}">
                                @error('expire_seconds')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="retry_seconds" class="form-label">Retry Seconds</label>
                                <input type="number" class="form-control @error('retry_seconds') is-invalid @enderror"
                                    id="retry_seconds" name="retry_seconds" placeholder="Retry seconds" min="1"
                                    max="65535" value="{{ old('retry_seconds', $gateway->retry_seconds ?? '30') }}">
                                @error('retry_seconds')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3 advancedSection"  style="display: block;">
                            <div class="form-group" >
                                <label for="register_transport" class="form-label">Register Transport</label>
                                <select class="form-select @error('register_transport') is-invalid @enderror"
                                    id="register_transport" name="register_transport">
                                    <option value="">Select</option>
                                    <option value="udp"
                                        {{ old('register_transport', $gateway->register_transport ?? '') == 'udp' ? 'selected' : '' }}>
                                        UDP</option>
                                    <option value="tcp"
                                        {{ old('register_transport', $gateway->register_transport ?? '') == 'tcp' ? 'selected' : '' }}>
                                        TCP</option>
                                    <option value="tls"
                                        {{ old('register_transport', $gateway->register_transport ?? '') == 'tls' ? 'selected' : '' }}>
                                        TLS</option>
                                </select>
                                @error('register_transport')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label d-block">Register</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="register" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch" id="register"
                                        name="register" value="true"
                                        {{ old('register', $gateway->register ?? 'true') == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="register">{{ __('Enabled') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-4 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label for="extension" class="form-label">Extension</label>
                                <input type="text" class="form-control @error('extension') is-invalid @enderror"
                                    id="extension" name="extension" placeholder="Extension"
                                    value="{{ old('extension', $gateway->extension ?? '') }}">
                                @error('extension')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                             <div class="form-group">
                                <label for="context" class="form-label">Context</label>
                                <input type="text" class="form-control @error('context') is-invalid @enderror"
                                    id="context" name="context" placeholder="Context"
                                    value="{{ old('context', $gateway->context ?? 'public') }}">
                                @error('context')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="profile" class="form-label">Profile</label>
                                <select class="form-select" id="profile" name="profile" required>
                                    @foreach ($profiles as $profile)
                                        <option value="{{ $profile->sip_profile_name }}"
                                            {{ old('profile', $gateway->profile ?? '') == $profile->sip_profile_name ? 'selected' : '' }}>
                                            {{ $profile->sip_profile_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('profile')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3 " >
                        <div class="col-md-4 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label for="channels" class="form-label">Channels</label>
                                <input type="number" class="form-control @error('channels') is-invalid @enderror"
                                    id="channels" name="channels" placeholder="Channels" min="1" max="65535"
                                    value="{{ old('channels', $gateway->channels ?? '') }}">
                                @error('channels')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label for="hostname" class="form-label">Hostname</label>
                                <input type="text" class="form-control @error('hostname') is-invalid @enderror"
                                    id="hostname" name="hostname" placeholder="Hostname"
                                    value="{{ old('hostname', $gateway->hostname ?? '') }}">
                                @error('hostname')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label d-block">Status</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enabled" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enabled"
                                        name="enabled" value="true"
                                        {{ old('enabled', $gateway->enabled ?? 'true') == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3 advancedSection"  style="display: block;" >Ping Configuration</h5>
                    <div class="row " ">
                        <div class="col-md-3 advancedSection"  style="display: block;" >
                            <div class="form-group">
                                <label for="ping" class="form-label">Ping</label>
                                <input type="text" class="form-control @error('ping') is-invalid @enderror"
                                    id="ping" name="ping" placeholder="Ping" min="1" max="65535"
                                    value="{{ old('ping', $gateway->ping ?? '') }}">
                                @error('ping')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3 advancedSection" style="display: block;">
                            <div class="form-group">
                                <label for="ping_min" class="form-label">Minimum Ping</label>
                                <input type="number" class="form-control @error('ping_min') is-invalid @enderror"
                                    id="ping_min" name="ping_min" placeholder="Minimum ping" min="1"
                                    max="65535" value="{{ old('ping_min', $gateway->ping_min ?? '') }}">
                                @error('ping_min')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group advancedSection"  style="display: block;">
                                <label for="ping_max" class="form-label">Maximum Ping</label>
                                <input type="number" class="form-control @error('ping_max') is-invalid @enderror"
                                    id="ping_max" name="ping_max" placeholder="Maximum ping" min="1"
                                    max="65535" value="{{ old('ping_max', $gateway->ping_max ?? '') }}">
                                @error('ping_max')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label class="form-label d-block">Contact in Ping</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="contact_in_ping" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch" id="contact_in_ping"
                                        name="contact_in_ping" value="true"
                                        {{ old('contact_in_ping', $gateway->contact_in_ping ?? '') == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="contact_in_ping">{{ __('Enabled') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="row" >
                        <div class="col-md-6 advancedSection" style="display: block;">
                            <div class="form-group ">
                                <label for="contact_params" class="form-label">Contact Parameters</label>
                                <input type="text" class="form-control @error('contact_params') is-invalid @enderror"
                                    id="contact_params" name="contact_params" placeholder="Contact parameters"
                                    value="{{ old('contact_params', $gateway->contact_params ?? '') }}">
                                @error('contact_params')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 advancedSection"  style="display: block;">
                            <div class="form-check form-switch">
                                <input type="hidden" name="distinct_to" value="false">
                                <input class="form-check-input" type="checkbox" role="switch" id="distinct_to"
                                    name="distinct_to" value="true"
                                    {{ old('distinct_to', $gateway->distinct_to ?? '') == 'true' ? 'checked' : '' }}>

                                <label class="form-check-label" for="distinct_to">{{ __('Distinct To') }}</label>

                                @error('distinct_to')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label for="codec_prefs" class="form-label">Codec Preferences</label>
                                <input type="text" class="form-control @error('codec_prefs') is-invalid @enderror"
                                    id="codec_prefs" name="codec_prefs" placeholder="Codec preferences"
                                    value="{{ old('codec_prefs', $gateway->codec_prefs ?? '') }}">
                                @error('codec_prefs')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 advancedSection"  style="display: block;">
                            <div class="form-group">
                                <label for="sip_cid_type" class="form-label">SIP CID Type</label>
                                <input type="text" class="form-control @error('sip_cid_type') is-invalid @enderror"
                                    id="sip_cid_type" name="sip_cid_type" placeholder="SIP CID Type"
                                    value="{{ old('sip_cid_type', $gateway->sip_cid_type ?? '') }}">
                                @error('sip_cid_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 advancedSection" style="display: block;">
                            <div class="form-group">
                                <label class="form-label d-block">Additional Options</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="caller_id_in_from" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="caller_id_in_from" name="caller_id_in_from" value="true"
                                        {{ old('caller_id_in_from', $gateway->caller_id_in_from ?? '') == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label"
                                        for="caller_id_in_from">{{ __('Caller ID in From') }}</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="supress_cng" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch" id="supress_cng"
                                        name="supress_cng" value="true"
                                        {{ old('supress_cng', $gateway->supress_cng ?? '') == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="supress_cng">{{ __('Suppress CNG') }}</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="extension_in_contact" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="extension_in_contact" name="extension_in_contact" value="true"
                                        {{ old('extension_in_contact', $gateway->extension_in_contact ?? '') == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label"
                                        for="extension_in_contact">{{ __('Extension in Contact') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="3" placeholder="Enter a description">{{ old('description', $gateway->description ?? '') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                        {{ isset($gateway) ? 'Update Gateway' : 'Create Gateway' }}
                    </button>
                    <a href="{{ route('gateways.index') }}" class="btn btn-secondary ml-2 px-4 py-2"
                        style="border-radius: 4px;">
                        Cancel
                    </a>
                </div>
    </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const passwordInput = document.getElementById("password");

        if (passwordInput) {
            passwordInput.addEventListener("mouseover", function() {
                passwordInput.type = "text";
            });

            passwordInput.addEventListener("mouseout", function() {
                passwordInput.type = "password";
            });
        }

        const advancedButton = document.getElementById('advancedButton');
        const advancedSections = document.querySelectorAll('.advancedSection');

        if (advancedButton && advancedSections.length) {
            advancedButton.addEventListener('click', function() {
                const isHidden = advancedSections[0].style.display === 'none';

                advancedSections.forEach(section => {
                    section.style.display = isHidden ? 'block' : 'none';
                });

                advancedButton.classList.toggle('btn-primary', isHidden);
                advancedButton.classList.toggle('btn-secondary', !isHidden);
            });
        }
    });
</script>
@endpush