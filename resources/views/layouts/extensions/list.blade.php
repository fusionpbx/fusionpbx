@extends('layouts.partials.listing.layout', ['pageTitle' => 'Extensions', 'breadcrumbs' => []])

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $extensions])
@endsection

@section('actionbar')
    @if ($permissions['import'])
        <button type="button" class="btn btn-sm btn-outline-info mb-2 me-2" data-bs-toggle="modal"
            data-bs-target="#extension-upload-modal"><i class="uil uil-upload me-1"></i>Import</button>
    @endif
    @if ($permissions['add_new'])
        <a href="{{ route('extensions.create') }}" class="btn btn-sm btn-success mb-2 me-2">
            <i class="uil uil-plus me-1"></i>
            Add New
        </a>
    @endif
    @if ($permissions['delete'])
        <a href="javascript:confirmDeleteAction('{{ route('extensions.destroy', ':id') }}');" id="deleteMultipleActionButton"
            class="btn btn-danger btn-sm mb-2 me-2 disabled">Delete Selected</a>
    @endif
    {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
@endsection

@section('searchbar')
    <form id="filterForm" method="GET" action="{{ url()->current() }}?page=1"
        class="row gy-2 gx-2 align-items-center justify-content-xl-start justify-content-between">
        <div class="col-auto">
            <label for="search" class="visually-hidden">Search</label>
            <div class="input-group input-group-merge">
                <input type="search" class="form-control" name="search" id="search" value="{{ $searchString }}"
                    placeholder="Search..." />
                <input type="button" class="btn btn-light" name="clear" id="clearSearch" value="Clear" />
            </div>
        </div>
        <div class="d-none"><input type="submit" name="submit" value="Ok" /></div>
    </form>
@endsection

@section('table-head')
    <tr>
        <th style="width: 20px;">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="selectallCheckbox">
                <label class="form-check-label" for="selectallCheckbox">&nbsp;</label>
            </div>
        </th>
        <th style="width: 20px;"></th>
        <th>Extension</th>
        <th>Name</th>
        <th>Email</th>
        <th>Outbound Caller ID</th>
        {{-- <th>Status</th> --}}
        <th>Description</th>
        <th class="text-end"></th>
    </tr>
@endsection

@section('table-body')
    @if ($extensions->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 9])
    @else
        @foreach ($extensions as $extension)
            <tr id="id{{ $extension->extension_uuid }}">
                <td>
                    <div class="form-check">
                        <input type="checkbox" name="action_box[]" value="{{ $extension->extension_uuid }}"
                            class="form-check-input action_checkbox">
                        <label class="form-check-label">&nbsp;</label>
                    </div>
                </td>
                <td>
                    @if ($extension['registrations'])
                        {{-- <h6><span class="badge bg-success rounded-pill dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&nbsp;&nbsp;&nbsp;</span></h6> --}}
                        <a class="" href="#" data-bs-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <i class="mdi mdi-circle text-success"></i>
                        </a>
                        <div class="dropdown-menu p-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">Registered devices</h5>
                                    @foreach ($extension['registrations'] as $registration)
                                        <p class="card-text">
                                            {{-- Check if this is a mobile app --}}
                                            @if (preg_match('/Bria|Push/i', $registration['agent']) > 0)
                                                <i class="mdi mdi-cellphone-link"><span class="ms-2">Bria Mobile
                                                        App</span></i>
                                            @elseif (preg_match('/Ringotel/i', $registration['agent']) > 0)
                                                <i class="mdi mdi-cellphone-link"><span class="ms-2">Mobile App</span></i>
                                            @else
                                                <i class="uil uil-phone"><span
                                                        class="ms-2">{{ $registration['agent'] }}</span></i>
                                            @endif
                                        </p>
                                    @endforeach
                                </div> <!-- end card-body-->
                            </div>

                        </div>
                    @else
                        <i class="mdi mdi-circle text-light"></i>
                    @endif

                </td>
                <td>
                    <a href="{{ route('extensions.edit', $extension) }}"
                        class="text-body fw-bold me-2">{{ $extension['extension'] }}</a>
                </td>
                <td>
                    <a href="{{ route('extensions.edit', $extension) }}"
                        class="text-body fw-bold me-1">{{ $extension['effective_caller_id_name'] }}
                    </a>
                    @if ($extension->suspended == 'true')
                        <small><span class="badge badge-outline-danger">Suspended</span></small>
                    @elseif ($extension->do_not_disturb == 'true')
                        <small><span class="badge badge-outline-danger">DND</span></small>
                    @endif
                    @if ($extension['forward_all_enabled'] == 'true')
                        <small><span class="badge badge-outline-primary">FWD</span></small>
                    @endif
                    @if ($extension['follow_me_enabled'] == 'true')
                        <small><span class="badge badge-outline-primary">Sequence</span></small>
                    @endif
                </td>
                <td>
                    {{-- @if ($extension->voicemail->exists) --}}
                    {{ $extension->voicemail->voicemail_mail_to ?? '' }}
                    {{-- @endif --}}
                </td>
                <td>
                    {{ $extension['outbound_caller_id_number'] }}

                </td>
                <td>
                    {{ $extension['description'] }}
                </td>
                <td class="text-nowrap text-end">
                    {{-- Action Buttons --}}
                    <div id="tooltip-container-actions">

                        <a href="{{ route('extensions.edit', $extension) }}" class="action-icon" title="Edit">
                            <i class="mdi mdi-lead-pencil" data-bs-container="#tooltip-container-actions"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit user"></i>
                        </a>

                        <a href="#" data-attr="{{ route('mobileAppUserSettings', $extension) }}"
                            class="action-icon mobileAppButton" title="Mobile App Settings">
                            <i class="mdi mdi-cellphone-cog" data-bs-container="#tooltip-container-actions"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Mobile App Settings"></i>
                        </a>

                        @if ($permissions['device_restart'])
                            <a href="javascript:sendEventNotify('{{ route('extensions.send-event-notify', ':id') }}','{{ $extension->extension_uuid }}');"
                                class="action-icon">
                                <i class="mdi mdi-restart" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Restart Devices"></i>
                            </a>
                        @endif

                        @if ($permissions['delete'])
                            <a href="javascript:confirmDeleteAction('{{ route('extensions.destroy', ':id') }}','{{ $extension->extension_uuid }}');"
                                class="action-icon">
                                <i class="mdi mdi-delete" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
                            </a>
                        @endif

                        {{-- <div class="dropdown"> --}}
                        <a class="dropdown-toggle arrow-none card-drop" href="#"
                            id="options{{ $extension->extension_uuid }}" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" aria-haspopup="true" aria-expanded="false">
                            <i class="mdi mdi-dots-vertical"></i>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end"
                            aria-labelledby="options{{ $extension->extension_uuid }}">
                            @if (userCheckPermission('extension_password'))
                                <a href="#" data-attr="{{ route('extensions.sip.show', $extension) }}"
                                    class="dropdown-item sipCredentialsButton">SIP
                                    Credentials</a>
                            @endif

                            @if ($permissions['add_user'])
                                <div class="accordion custom-accordion" id="accordion{{ $extension->extension_uuid }}">
                                    <h6 class="dropdown-header" id="heading{{ $extension->extension_uuid }}">
                                        <a class="custom-accordion-title d-block" data-bs-toggle="collapse"
                                            href="#collapse{{ $extension->extension_uuid }}" aria-expanded="false"
                                            aria-controls="collapse{{ $extension->extension_uuid }}">
                                            Groups
                                            <i class="mdi mdi-chevron-down accordion-arrow"></i>
                                        </a>
                                    </h6>

                                    <div id="collapse{{ $extension->extension_uuid }}" class="collapse"
                                        aria-labelledby="heading{{ $extension->extension_uuid }}"
                                        data-bs-parent="#accordion{{ $extension->extension_uuid }}">
                                        {{-- <a href="#" data-attr="{{ route('extensions.sip.show', $extension) }}"
                                        class="dropdown-item">Make User</a> --}}
                                        <livewire:extensions.make-user :extension="$extension" role="user" />
                                        <livewire:extensions.make-user :extension="$extension" role="admin" />
                                    </div>
                                </div>
                            @endif

                            @stack('options_dropdown_end')

                            @if (Module::find('ContactCenter') &&
                                    Module::find('ContactCenter')->isEnabled() &&
                                    ($permissions['contact_center_agent_create'] ||
                                        $permissions['contact_center_admin_create'] ||
                                        $permissions['contact_center_supervisor_create']))
                                @include('contactcenter::layouts.extensions.extension-options')
                            @endif

                        </div>
                        {{-- </div> --}}

                    </div>
                    {{-- End of action buttons --}}

                </td>
            </tr>
        @endforeach
    @endif
@endsection




@section('includes')
    @include('layouts.extensions.extensionUploadModal')
    @include('layouts.extensions.createMobileAppModal')
    @include('layouts.extensions.mobileAppModal')
    @include('layouts.extensions.createMobileAppSuccessModal')
    @include('layouts.extensions.createMobileAppDeactivatedSuccessModal')
    @include('layouts.extensions.sipCredentialsModal')
    @include('layouts.extensions.extensionUploadResultModal')
    @include('layouts.extensions.extensionScripts')
@endsection
