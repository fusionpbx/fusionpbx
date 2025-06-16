<div>
    <div class="container-fluid">
        <div class="card card-primary mt-3 card-outline">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        {{ isset($extensions) ? 'Edit Extension' : 'Create Extension' }}
                    </h3>
                    <div>
                        @if (isset($extensions))
                            @can('extension_copy')
                                <button type="button" class="btn btn-primary btn-sm" wire:click="showCopyModals">
                                    <i class="fa fa-clone" aria-hidden="true"></i> {{ __('Copy') }}
                                </button>
                            @endcan

                            @can('call_forward')
                                <button type="button" class="btn btn-primary btn-sm">
                                    <i class="fa fa-share-alt" aria-hidden="true"></i> {{ __('Call forward') }}
                                </button>
                            @endcan

                            @can('xml_cdr_view')
                                <button type="button" class="btn btn-primary btn-sm">
                                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i> {{ __('CDR') }}
                                </button>
                            @endcan

                            @can('extension_setting_view')
                                <button type="button" class="btn btn-primary btn-sm">
                                    <i class="fa fa-cogs" aria-hidden="true"></i> {{ __('Settings') }}
                                </button>
                            @endcan

                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form wire:submit.prevent="save">
                    <!-- Basic Information -->
                    <h5 class="mb-3">{{ __('Basic Information') }}</h5>
                    <div class="row">
                        @can('extension_extension')
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="extension" class="form-label">Extension <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('extension') is-invalid @enderror"
                                        id="extension" wire:model="extension" placeholder="Enter extension number" required>
                                    @error('extension')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endcan
                        @can('number_alias')
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="number_alias" class="form-label">Number Alias</label>
                                    <input type="number" class="form-control @error('number_alias') is-invalid @enderror"
                                        id="number_alias" wire:model="number_alias" placeholder="Enter number alias">
                                    @error('number_alias')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endcan
                        @if (!$extensions)
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="range" class="form-label">Range</label>
                                    <input type="text" class="form-control @error('range') is-invalid @enderror"
                                        id="range" wire:model="range" placeholder="Enter range">
                                    @error('range')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        {{ __('Enter the number of consecutive extensions to create starting from the extension number above.') }}
                                    </small>
                                </div>
                            </div>
                        @endif
                        @if (isset($extensions))
                            @can('extension_password')
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="password" class="form-label">Password <span
                                                class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                                            id="password" wire:model="password" placeholder="Enter password" required>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endcan
                        @endif
                    </div>

                    <div class="row">
                        @can('extension_accountcode')
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="accountcode" class="form-label">Account Code</label>
                                    <input type="text" class="form-control @error('accountcode') is-invalid @enderror"
                                        id="accountcode" wire:model="accountcode" placeholder="Enter account code">
                                    @error('accountcode')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endcan

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label d-block">Enabled</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enabled"
                                        wire:model="enabled" value="true" {{ $enabled === 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Caller ID Configuration -->
                    <h5 class="mt-4 mb-3">{{ __('Caller ID Configuration') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                @can('effective_caller_id_name')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="effective_caller_id_name" class="form-label">Effective Caller ID
                                                Name</label>
                                            <input type="text"
                                                class="form-control @error('effective_caller_id_name') is-invalid @enderror"
                                                id="effective_caller_id_name" wire:model="effective_caller_id_name"
                                                placeholder="Enter effective caller ID name">
                                            @error('effective_caller_id_name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                                @can('effective_caller_id_number')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="effective_caller_id_number" class="form-label">Effective Caller ID
                                                Number</label>
                                            <input type="text"
                                                class="form-control @error('effective_caller_id_number') is-invalid @enderror"
                                                id="effective_caller_id_number" wire:model="effective_caller_id_number"
                                                placeholder="Enter effective caller ID number">
                                            @error('effective_caller_id_number')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                            </div>
                            <div class="row">
                                @can('outbound_caller_id_name')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="outbound_caller_id_name" class="form-label">Outbound Caller ID
                                                Name</label>
                                            <input type="text"
                                                class="form-control @error('outbound_caller_id_name') is-invalid @enderror"
                                                id="outbound_caller_id_name" wire:model="outbound_caller_id_name"
                                                placeholder="Enter outbound caller ID name">
                                            @error('outbound_caller_id_name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                                @can('outbound_caller_id_number')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="outbound_caller_id_number" class="form-label">Outbound Caller ID
                                                Number</label>
                                            <input type="text"
                                                class="form-control @error('outbound_caller_id_number') is-invalid @enderror"
                                                id="outbound_caller_id_number" wire:model="outbound_caller_id_number"
                                                placeholder="Enter outbound caller ID number">
                                            @error('outbound_caller_id_number')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                            </div>
                            <div class="row">
                                @can('emergency_caller_id_name')
                                    <div class="col-md-6">
                                        <label for="emergency_caller_id_name" class="form-label">
                                            {{ __('Emergency Caller ID Name') }}
                                        </label>

                                        @can('emergency_caller_id_select')
                                            @if ($emergencyDestinations->isNotEmpty())
                                                <select wire:model="emergencyCallerIdName" id="emergency_caller_id_name"
                                                    class="form-select mt-1 block w-full">
                                                    <option value="">{{ __('Seleccione una opción') }}</option>
                                                    @foreach ($emergencyDestinations as $destination)
                                                        @php
                                                            $label =
                                                                $destination->destination_caller_id_name ?:
                                                                $destination->destination_description;
                                                        @endphp
                                                        @if (!empty($label))
                                                            <option value="{{ $label }}">{{ $label }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            @else
                                                <a href="{{ route('destinations.index') }}"></a>
                                                <button type="button" class="btn btn-primary">
                                                    {{ __('add') }}
                                                </button>
                                            @endif
                                        @else
                                            <input type="text"
                                                class="form-control @error('emergency_caller_id_name') is-invalid @enderror"
                                                id="emergency_caller_id_name" wire:model="emergency_caller_id_name"
                                                placeholder="Enter emergency caller ID name">
                                        @endcan

                                        <small class="text-sm text-gray-500 mt-2">
                                            @can('outbound_caller_id_select')
                                                {{ __('Select the emergency caller ID name from the list.') }}
                                            @else
                                                {{ __('Enter the emergency caller ID name here.') }}
                                            @endcan
                                        </small>
                                    </div>
                                @endcan

                                @can('emergency_caller_id_number')
                                    <div class="col-md-6">
                                        <label for="emergency_caller_id_number"
                                            class="block font-medium text-sm text-gray-700">
                                            {{ __('Emergency Caller ID Number') }}
                                        </label>
                                        @can('emergency_caller_id_select')
                                            @if ($emergency_destination->isNotEmpty())
                                                <select wire:model="emergencyCallerIdNumber" id="emergency_caller_id_number"
                                                    class="form-select mt-1 block w-full">
                                                    @can('emergency_caller_id_select_empty')
                                                        <option value="">{{ __('Selected an option') }}</option>
                                                    @endcan

                                                    @foreach ($emergency_destination as $destination)
                                                        @php
                                                            $label =
                                                                $destination->destination_caller_id_number ?:
                                                                $destination->destination_number;
                                                        @endphp
                                                        @if (!empty($label))
                                                            <option value="{{ $label }}">{{ $label }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            @else
                                                <a href="{{ route('destinations.index') }}">

                                                    <button type="button" class="btn btn-primary ">
                                                        <i class="fas fa-plus"></i>
                                                        {{ __('Add') }}
                                                    </button>
                                                </a>
                                            @endif
                                        @else
                                            <input type="text"
                                                class="form-control @error('emergency_caller_id_number') is-invalid @enderror"
                                                id="emergency_caller_id_number" wire:model="emergency_caller_id_number"
                                                placeholder="Enter emergency caller ID number">
                                            @error('emergency_caller_id_number')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @endcan
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>

                    @can('voicemail_edit')
                        <h5 class="mt-4 mb-3">{{ __('Voicemail') }}</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="voicemail_enabled" class="form-label">Voicemail Enabled</label>
                                            <select class="form-select @error('voicemail_enabled') is-invalid @enderror"
                                                id="voicemail_enabled" wire:model="voicemail_enabled">
                                                <option value="true">True</option>
                                                <option value="false">False</option>
                                            </select>
                                            @error('voicemail_enabled')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="voicemail_password" class="form-label">Voicemail Password</label>
                                            <input type="password"
                                                class="form-control @error('voicemail_password') is-invalid @enderror"
                                                id="voicemail_password" wire:model="voicemail_password"
                                                placeholder="Enter voicemail password">
                                            @error('voicemail_password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="voicemail_mail_to" class="form-label">Voicemail Mail To</label>
                                            <input type="text"
                                                class="form-control @error('voicemail_mail_to') is-invalid @enderror"
                                                id="voicemail_mail_to" wire:model="voicemail_mail_to"
                                                placeholder="Enter voicemail mail to">
                                            @error('voicemail_mail_to')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @can('voicemail_transcription_enabled')
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="voicemail_transcription_enabled" class="form-label">Transcription
                                                    Enabled</label>
                                                <select
                                                    class="form-select @error('voicemail_transcription_enabled') is-invalid @enderror"
                                                    id="voicemail_transcription_enabled"
                                                    wire:model="voicemail_transcription_enabled">
                                                    <option value="true">True</option>
                                                    <option value="false">False</option>
                                                </select>
                                                @error('voicemail_transcription_enabled')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endcan
                                    @can('voicemail_transcription_enable')
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="voicemail_file" class="form-label">Voicemail File</label>
                                                <select name="voicemail_file" class="form-select" id="voicemail_file"
                                                    wire:model="voicemail_file">
                                                    <option value="">Listen Link (Login Required)</option>
                                                    <option value="link">Download Link (No Login Required)</option>
                                                    <option value="attach">Audio File </option>

                                                </select>
                                                @error('voicemail_file')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endcan


                    <!-- Directory Configuration -->
                    <h5 class="mt-4 mb-3">{{ __('Directory Configuration') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="directory_first_name" class="form-label">First Name</label>
                                        <input type="text"
                                            class="form-control @error('directory_first_name') is-invalid @enderror"
                                            id="directory_first_name" wire:model="directory_first_name"
                                            placeholder="Enter first name">
                                        @error('directory_first_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="directory_last_name" class="form-label">Last Name</label>
                                        <input type="text"
                                            class="form-control @error('directory_last_name') is-invalid @enderror"
                                            id="directory_last_name" wire:model="directory_last_name"
                                            placeholder="Enter last name">
                                        @error('directory_last_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="directory_visible" class="form-label">Directory Visible</label>
                                        <select class="form-select @error('directory_visible') is-invalid @enderror"
                                            id="directory_visible" wire:model="directory_visible">
                                            <option value="true">True</option>
                                            <option value="false">False</option>
                                        </select>
                                        @error('directory_visible')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="directory_exten_visible" class="form-label">Extension
                                            Visible</label>
                                        <select
                                            class="form-select @error('directory_exten_visible') is-invalid @enderror"
                                            id="directory_exten_visible" wire:model="directory_exten_visible">
                                            <option value="true">True</option>
                                            <option value="false">False</option>
                                        </select>
                                        @error('directory_exten_visible')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Configuration -->
                    <h5 class="mt-4 mb-3">{{ __('Advanced Configuration') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="max_registrations" class="form-label">Max Registrations</label>
                                        <input type="number"
                                            class="form-control @error('max_registrations') is-invalid @enderror"
                                            id="max_registrations" wire:model="max_registrations"
                                            placeholder="Enter max registrations" min="1">
                                        @error('max_registrations')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="limit_max" class="form-label">Limit Max</label>
                                        <input type="number"
                                            class="form-control @error('limit_max') is-invalid @enderror"
                                            id="limit_max" wire:model="limit_max" placeholder="Enter limit max"
                                            min="1">
                                        @error('limit_max')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="call_timeout" class="form-label">Call Timeout</label>
                                        <input type="number"
                                            class="form-control @error('call_timeout') is-invalid @enderror"
                                            id="call_timeout" wire:model="call_timeout"
                                            placeholder="Enter call timeout" min="1">
                                        @error('call_timeout')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="limit_destination" class="form-label">Limit Destination</label>
                                        <input type="text"
                                            class="form-control @error('limit_destination') is-invalid @enderror"
                                            id="limit_destination" wire:model="limit_destination"
                                            placeholder="Enter limit destination">
                                        @error('limit_destination')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="user_context" class="form-label">User Context</label>
                                        <input type="text"
                                            class="form-control @error('user_context') is-invalid @enderror"
                                            id="user_context" wire:model="user_context"
                                            placeholder="Enter user context">
                                        @error('user_context')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @can('extension_call_group')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="call_group" class="form-label">Call Group</label>
                                            <input type="text"
                                                class="form-control @error('call_group') is-invalid @enderror"
                                                id="call_group" wire:model="call_group" placeholder="Enter call group">
                                            @error('call_group')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                                @can('extension_call_screen')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="call_screen_enabled" class="form-label">Call Screen
                                                Enabled</label>
                                            <select class="form-select @error('call_screen_enabled') is-invalid @enderror"
                                                id="call_screen_enabled" wire:model="call_screen_enabled">
                                                <option value="true">True</option>
                                                <option value="false">False</option>
                                            </select>
                                            @error('call_screen_enabled')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                            </div>
                            <div class="row">
                                @can('extension_user_record')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="user_record" class="form-label">Record</label>
                                            <select name="user_record" class="form-select" id="user_record"
                                                wire:model="user_record">
                                                <option value="">Disabled</option>
                                                <option value="local">Local</option>
                                                <option value="all">All</option>
                                                <option value="inbound">Inbound</option>
                                                <option value="outbound">Outbound</option>
                                            </select>
                                            @error('user_record')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                                @can('extension_hold_music')
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="hold_music" class="form-label">Hold Music</label>
                                            <input type="text"
                                                class="form-control @error('hold_music') is-invalid @enderror"
                                                id="hold_music" wire:model="hold_music" placeholder="Enter hold music">
                                            @error('hold_music')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <!-- Missed Call Configuration -->
                    <h5 class="mt-4 mb-3">{{ __('Missed Call Configuration') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="missed_call_app" class="form-label">Missed Call App</label>
                                        <input type="text"
                                            class="form-control @error('missed_call_app') is-invalid @enderror"
                                            id="missed_call_app" wire:model="missed_call_app"
                                            placeholder="Enter missed call app">
                                        @error('missed_call_app')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="missed_call_data" class="form-label">Missed Call Data</label>
                                        <input type="text"
                                            class="form-control @error('missed_call_data') is-invalid @enderror"
                                            id="missed_call_data" wire:model="missed_call_data"
                                            placeholder="Enter missed call data">
                                        @error('missed_call_data')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label for="toll_allow" class="form-label">Toll Allow</label>
                                        <input type="text"
                                            class="form-control @error('toll_allow') is-invalid @enderror"
                                            id="toll_allow" wire:model="toll_allow" placeholder="Enter toll allow">
                                        @error('toll_allow')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SIP Advanced Configuration -->
                    @can('extension_advanced')
                        <div class="accordion mb-4" id="sipAdvancedAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="sipAdvancedHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#sipAdvancedCollapse" aria-expanded="false"
                                        aria-controls="sipAdvancedCollapse">
                                        <i class="fa fa-cog me-2"></i>
                                        {{ __('SIP Advanced Configuration') }}
                                    </button>
                                </h2>
                                <div id="sipAdvancedCollapse" class="accordion-collapse collapse"
                                    aria-labelledby="sipAdvancedHeading" data-bs-parent="#sipAdvancedAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="auth_acl" class="form-label">Auth ACL</label>
                                                    <input type="text"
                                                        class="form-control @error('auth_acl') is-invalid @enderror"
                                                        id="auth_acl" wire:model="auth_acl"
                                                        placeholder="Enter auth ACL">
                                                    @error('auth_acl')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="cidr" class="form-label">CIDR</label>
                                                    <input type="text"
                                                        class="form-control @error('cidr') is-invalid @enderror"
                                                        id="cidr" wire:model="cidr" placeholder="Enter CIDR">
                                                    @error('cidr')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="sip_force_contact" class="form-label">SIP Force
                                                        Contact</label>
                                                    <select name="sip_force_contact" class="form-select"
                                                        id="sip_force_contact" wire:model="sip_force_contact">
                                                        <option value="">Select SIP Force Contact</option>
                                                        <option value="NDLB-connectile-dysfunction"> NDLB connectile
                                                            dysfunction</option>
                                                        <option value="NDLB-connectile-dysfunction-2.0"> NDLB connectile
                                                            dysfunction 2.0</option>
                                                        <option value="NDLB-tls-connectile-dysfunction"> NDLB tls
                                                            connectile dysfunction</option>
                                                    </select>
                                                    @error('sip_force_contact')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="sip_force_expires" class="form-label">SIP Force
                                                        Expires</label>
                                                    <input type="text"
                                                        class="form-control @error('sip_force_expires') is-invalid @enderror"
                                                        id="sip_force_expires" wire:model="sip_force_expires"
                                                        placeholder="Enter SIP force expires">
                                                    @error('sip_force_expires')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="mwi_account" class="form-label">MWI Account</label>
                                                    <input type="text"
                                                        class="form-control @error('mwi_account') is-invalid @enderror"
                                                        id="mwi_account" wire:model="mwi_account"
                                                        placeholder="Enter MWI account">
                                                    @error('mwi_account')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="sip_bypass_media" class="form-label">SIP Bypass
                                                        Media</label>
                                                    <select name="sip_bypass_media" class="form-select"
                                                        id="sip_bypass_media" wire:model="sip_bypass_media">
                                                        <option value="">Select SIP Bypass Media</option>
                                                        <option value="bypass-media">Bypass Media</option>
                                                        <option value="bypass-media-after-bridge">Bypass Media After Bridge
                                                        </option>
                                                        <option value="proxy-media">Proxy Media</option>
                                                    </select>
                                                    @error('sip_bypass_media')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="absolute_codec_string" class="form-label">Absolute Codec
                                                        String</label>
                                                    <input type="text"
                                                        class="form-control @error('absolute_codec_string') is-invalid @enderror"
                                                        id="absolute_codec_string" wire:model="absolute_codec_string"
                                                        placeholder="Enter absolute codec string">
                                                    @error('absolute_codec_string')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="force_ping" class="form-label">Force Ping</label>
                                                    <select name="" class="form-select" id="force_ping">
                                                        <option value="">Select</option>
                                                        <option value="true">True</option>
                                                        <option value="false">False</option>
                                                    </select>
                                                    @error('force_ping')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group mb-3">
                                                    <label for="dial_string" class="form-label">Dial String</label>
                                                    <input type="text"
                                                        class="form-control @error('dial_string') is-invalid @enderror"
                                                        id="dial_string" wire:model="dial_string"
                                                        placeholder="Enter dial string">
                                                    @error('dial_string')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan

                    <h5 class="mt-4 mb-3">{{ __('Users') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary btn-sm" wire:click="addUser">
                                        <i class="fa fa-plus"></i> {{ __('Add User') }}
                                    </button>
                                </div>
                            </div>
                            @foreach ($extensionUsers as $index => $user)
                                <div class="row mb-3 border-bottom pb-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">{{ __('User') }}</label>
                                            <select
                                                class="form-select @error('extensionUsers.' . $index . '.user_uuid') is-invalid @enderror"
                                                wire:model="extensionUsers.{{ $index }}.user_uuid">
                                                <option value="">{{ __('Select User') }}</option>
                                                @foreach ($availableUsers as $availableUser)
                                                    <option value="{{ $availableUser['user_uuid'] }}">
                                                        {{ $availableUser['username'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('extensionUsers.' . $index . '.user_uuid')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger btn-sm d-block"
                                                wire:click="removeUser({{ $index }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if (empty($extensionUsers))
                                <div class="text-muted text-center py-3">
                                    {{ __('No users assigned') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">{{ __('Domain') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="selectedDomain" class="form-label">{{ __('Domain') }}</label>
                                        @can('extension_domain')
                                            <select class="form-select @error('selectedDomain') is-invalid @enderror"
                                                id="selectedDomain" wire:model="selectedDomain">
                                                @foreach ($availableDomains as $availableDomain)
                                                    <option value="{{ $availableDomain['domain_uuid'] }}">
                                                        {{ $availableDomain['domain_name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="form-control"
                                                value="{{ auth()->user()->domain->domain_name }}" readonly>
                                            <small class="form-text text-muted">
                                                {{ __('You can only create extensions in your assigned domain.') }}
                                            </small>
                                        @endcan
                                        @error('selectedDomain')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Description -->
                    <h5 class="mt-4 mb-3">{{ __('Description') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="description" class="form-label">{{ __('Description') }}</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" wire:model="description"
                                    rows="4" placeholder="Enter extension description"></textarea>
                                @error('description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('extensions.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> {{ isset($extensions) ? __('Update') : __('Create') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if ($showCopyModal)
        <div class="modal fade show" id="copyExtensionModal" tabindex="-1"
            aria-labelledby="copyExtensionModalLabel" style="display: block; background-color: rgba(0,0,0,0.5);"
            aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="copyExtensionModalLabel">
                            <i class="fa fa-clone me-2"></i>{{ __('Copy Extension') }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeCopyModal"
                            aria-label="Close"></button>
                    </div>

                    <form wire:submit.prevent="copy">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle me-2"></i>
                                {{ __('You are copying extension') }}:
                                <strong>{{ $extensions->extension }}</strong>
                                @if ($extensions->number_alias)
                                    ({{ $extensions->number_alias }})
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="copyExtensionNumber" class="form-label">
                                            {{ __('New Extension Number') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                            class="form-control @error('copyExtensionNumber') is-invalid @enderror"
                                            id="copyExtensionNumber" wire:model="copyExtensionNumber"
                                            placeholder="{{ __('Enter new extension number') }}" autofocus>
                                        @error('copyExtensionNumber')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="copyNumberAlias" class="form-label">
                                            {{ __('New Number Alias') }}
                                        </label>
                                        <input type="text"
                                            class="form-control @error('copyNumberAlias') is-invalid @enderror"
                                            id="copyNumberAlias" wire:model="copyNumberAlias"
                                            placeholder="{{ __('Enter new number alias (optional)') }}">
                                        @error('copyNumberAlias')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                {{ __('All other settings will be copied from the original extension. You can modify them after creation.') }}
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeCopyModal">
                                <i class="fa fa-times me-2"></i>{{ __('Cancel') }}
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-clone me-2"></i>{{ __('Copy Extension') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggleOnHover = (inputId) => {
                const input = document.getElementById(inputId);
                if (!input) return;

                input.addEventListener("mouseenter", () => {
                    input.type = "text";
                });

                input.addEventListener("mouseleave", () => {
                    input.type = "password";
                });
            };

            toggleOnHover("password");
            toggleOnHover("voicemail_password");
        });
    </script>
</div>
