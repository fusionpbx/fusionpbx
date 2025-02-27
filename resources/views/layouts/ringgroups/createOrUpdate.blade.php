@extends('layouts.app', ['page_title' => 'Edit Ring Group'])

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('ring-groups.index') }}">Ring Groups</a></li>
                            @if ($ringGroup->exists)
                                <li class="breadcrumb-item active">Edit Ring Group</li>
                            @else
                                <li class="breadcrumb-item active">Create Ring Group</li>
                            @endif
                        </ol>
                    </div>
                    @if ($ringGroup->exists)
                        <h4 class="page-title">Edit Ring Group ({{ $ringGroup->ring_group_name }})</h4>
                    @else
                        <h4 class="page-title">Create Ring Group</h4>
                    @endif
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @php
                            if ($ringGroup->exists) {
                                $actionUrl = route('ring-groups.update', $ringGroup);
                            } else {
                                $actionUrl = route('ring-groups.store');
                            }
                        @endphp
                        <form method="POST" id="ringGroupForm" action="{{ $actionUrl }}" class="form">
                            <input type="hidden" name="ring_group_uuid" value="{{ $ringGroup->ring_group_uuid }}" />
                            @if ($ringGroup->exists)
                                @method('put')
                            @endif
                            @csrf
                            <div class="row">
                                <div class="col-sm-2 mb-2 mb-sm-0">
                                    <div class="nav flex-column nav-pills" id="extensionNavPills" role="tablist"
                                        aria-orientation="vertical">
                                        <a class="nav-link active show" id="v-pills-home-tab" data-bs-toggle="pill"
                                            href="#v-pills-home" role="tab" aria-controls="v-pills-home"
                                            aria-selected="true">
                                            <i class="mdi mdi-home-variant d-md-none d-block"></i>
                                            <span class="d-none d-md-block">Basic Information
                                                <span
                                                    class="float-end text-end
                                            ring_group_name_err_badge
                                            ring_group_extension_badge
                                            ring_group_call_timeout_err_badge
                                            ring_group_timeout_data_err_badge
                                            ring_group_cid_name_prefix_err_badge
                                            ring_group_cid_number_prefix_err_badge
                                            ring_group_description_err_badge
                                            "
                                                    hidden><span class="badge badge-danger-lighten">error</span></span>
                                            </span>
                                        </a>

                                        @if (userCheckPermission('ring_group_forward'))
                                            <a class="nav-link" id="v-pills-callforward-tab" data-bs-toggle="pill"
                                                href="#v-pills-callforward" role="tab"
                                                aria-controls="v-pills-callforward" aria-selected="false">
                                                <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                                                <span class="d-none d-md-block">Call Forward
                                                    <span
                                                        class="float-end text-end
                                            ring_group_forward_all_target_external_err_badge
                                            ring_group_forward_destination_err_badge
                                            "
                                                        hidden><span class="badge badge-danger-lighten">error</span></span>
                                                </span>
                                            </a>
                                        @endif

                                        <a class="nav-link" id="v-pills-advanced-tab" data-bs-toggle="pill"
                                            href="#v-pills-advanced" role="tab" aria-controls="v-pills-advanced"
                                            aria-selected="false">
                                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                                            <span class="d-none d-md-block">Advanced
                                                <span
                                                    class="float-end text-end
                                            ring_group_forward_enabled_err_badge
                                            ring_group_forward_destination_err_badge
                                            ring_group_strategy_err_badge
                                            ring_group_caller_id_name_err_badge
                                            ring_group_caller_id_number_err_badge
                                            ring_group_distinctive_ring_err_badge
                                            ring_group_missed_call_data_err_badge
                                            ring_group_forward_toll_allow_err_badge
                                            ring_group_context_err_badge
                                            "
                                                    hidden><span class="badge badge-danger-lighten">error</span></span>
                                            </span>
                                        </a>
                                    </div>
                                </div> <!-- end col-->

                                <div class="col-sm-10">

                                    <div class="tab-content">
                                        <div class="text-sm-end" id="action-buttons">
                                            <a href="{{ route('ring-groups.index') }}" class="btn btn-light me-2">Cancel</a>
                                            <button class="btn btn-success" type="submit" id="submitFormButton"><i
                                                    class="uil uil-down-arrow me-2"></i> Save
                                            </button>
                                            {{-- <button class="btn btn-success" type="submit">Save</button> --}}
                                        </div>
                                        <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel"
                                            aria-labelledby="v-pills-home-tab">
                                            <!-- Basic Info Content-->
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mt-2">Basic information</h4>
                                                    <p class="text-muted mb-4">Provide basic information about the ring
                                                        group</p>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="ring_group_name" class="form-label">Ring
                                                                    Group Name <span class="text-danger">*</span></label>
                                                                <input class="form-control" type="text" placeholder=""
                                                                    id="ring_group_name" name="ring_group_name"
                                                                    value="{{ $ringGroup->ring_group_name }}" />
                                                                <div id="ring_group_name_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>


                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="ring_group_extension" class="form-label">Ring
                                                                    Group Extension Number <span
                                                                        class="text-danger">*</span></label>
                                                                <input class="form-control" type="text" placeholder="xxx"
                                                                    id="ring_group_extension" name="ring_group_extension"
                                                                    value="{{ $ringGroup->ring_group_extension }}" />
                                                                <div id="ring_group_extension_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-5">
                                                            <div class="mb-3">
                                                                @include(
                                                                    'layouts.partials.greetingSelector',
                                                                    [
                                                                        'id' => 'ring_group_greeting',
                                                                        'allRecordings' => $recordings,
                                                                        'value' =>
                                                                            $ringGroup->ring_group_greeting ??
                                                                            null,
                                                                        'entity' => 'ringGroup',
                                                                        'entityid' => $ringGroup->ring_group_uuid,
                                                                        'showUseRecordingAction' => (bool)$ringGroup->ring_group_uuid
                                                                    ]
                                                                )
                                                                <span class="help-block"><small>Turn ON this option if you
                                                                        want callers to hear a recorded greeting before
                                                                        being connected to a group member.</small></span>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    <div class="row">
                                                        <div class="col-md-5">
                                                            <div class="mb-1">
                                                                <label for="ring_group_strategy" class="form-label">Ring
                                                                    Pattern</label>

                                                                <a href="javascript://" data-bs-toggle="popover"
                                                                    data-bs-placement="right" data-bs-trigger="focus"
                                                                    data-bs-html="true"
                                                                    data-bs-content="<div>
                                                                        <ul>
                                                                        <li><b>Sequential Ring:</b> This option rings one phone at a time in a specific order.</li>
                                                                        <li><b>Simultaneous Ring:</b> This option rings all phones at once.</li>
                                                                        <li><b>Random Ring:</b> This option rings one phone at a time in a random order. </li>
                                                                        <li><b>Advanced (default):</b>  This option rings all phones at once, but each phone has its own thread. This is especially useful when there are multiple registrations for the same extension. </li>
                                                                        <li><b>Rollover:</b> This option rings each phone one at a time, but it skips busy phones.</li>
                                                                        </ul>
                                                                        </div>"
                                                                    title="">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>

                                                                <select class="select2 form-control" data-toggle="select2"
                                                                    data-placeholder="Choose ..." id="ring_group_strategy"
                                                                    name="ring_group_strategy">
                                                                    <option value="simultaneous"
                                                                        @if ($ringGroup->ring_group_strategy == 'simultaneous') selected="selected" @endif>
                                                                        Simultaneous Ring
                                                                    </option>
                                                                    <option value="sequence"
                                                                        @if ($ringGroup->ring_group_strategy == 'sequence') selected="selected" @endif>
                                                                        Sequential Ring
                                                                    </option>
                                                                    <option value="random"
                                                                        @if ($ringGroup->ring_group_strategy == 'random') selected="selected" @endif>
                                                                        Random Ring
                                                                    </option>
                                                                    <option value="enterprise"
                                                                        @if ($ringGroup->ring_group_strategy == 'enterprise') selected="selected" @endif>
                                                                        Advanced
                                                                    </option>
                                                                    <option value="rollover"
                                                                        @if ($ringGroup->ring_group_strategy == 'rollover') selected="selected" @endif>
                                                                        Rollover
                                                                    </option>
                                                                </select>
                                                                <div id="ring_group_strategy_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr />
                                                    <div class="row">
                                                        <h4 class="mt-2">Destinations</h4>
                                                        <p class="text-muted mb-2">You can drag-n-drop lines to adjust
                                                            current destinations order.</p>
                                                        <table
                                                            class="table table-centered table-responsive table-sm mb-0 sequential-table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 20px;">Order</th>
                                                                    <th>Destination</th>
                                                                    <th class="colDelay" style="width: 150px">Delay</th>
                                                                    <th style="width: 150px">Number of rings
                                                                    </th>
                                                                    <th style="width: 130px;">Answer
                                                                        confirmation required
                                                                    </th>
                                                                    <th style="width: 130px;">Status</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            @php $b = 0 @endphp
                                                            <tbody id="destination_sortable">
                                                                @foreach ($ringGroupDestinations as $destination)
                                                                    <tr
                                                                        id="row{{ $destination->ring_group_destination_uuid }}">
                                                                        @php $b++ @endphp
                                                                        <td class="drag-handler"><i
                                                                                class="mdi mdi-drag"></i>
                                                                            <span>{{ $b }}</span>
                                                                        </td>
                                                                        <td>
                                                                            @include(
                                                                                'layouts.partials.destinationSelector',
                                                                                [
                                                                                    'type' =>
                                                                                        'ring_group_destinations',
                                                                                    'id' =>
                                                                                        $destination->ring_group_destination_uuid,
                                                                                    'value' =>
                                                                                        $destination->destination_number,
                                                                                    'extensions' => $extensions,
                                                                                ]
                                                                            )
                                                                        </td>
                                                                        <td class="colDelay">
                                                                            <select
                                                                                id="destination_delay_{{ $destination->ring_group_destination_uuid }}"
                                                                                name="ring_group_destinations[{{ $destination->ring_group_destination_uuid }}][delay]">
                                                                                @for ($i = 0; $i < 20; $i++)
                                                                                    <option value="{{ $i * 5 }}"
                                                                                        @if ($destination->destination_delay == $i * 5) selected @endif>
                                                                                        {{ $i }} @if ($i > 1)
                                                                                            Rings
                                                                                        @else
                                                                                            Ring
                                                                                        @endif -
                                                                                        {{ $i * 5 }}Sec
                                                                                    </option>
                                                                                @endfor
                                                                            </select>
                                                                        </td>
                                                                        <td>
                                                                            <select
                                                                                id="destination_timeout_{{ $destination->ring_group_destination_uuid }}"
                                                                                name="ring_group_destinations[{{ $destination->ring_group_destination_uuid }}][timeout]">
                                                                                @for ($i = 1; $i < 21; $i++)
                                                                                    <option value="{{ $i * 5 }}"
                                                                                        @if ($destination->destination_timeout == $i * 5) selected @endif>
                                                                                        {{ $i }} @if ($i > 1)
                                                                                            Rings
                                                                                        @else
                                                                                            Ring
                                                                                        @endif -
                                                                                        {{ $i * 5 }}Sec
                                                                                    </option>
                                                                                @endfor
                                                                            </select>
                                                                        </td>
                                                                        <td>
                                                                            <input type="hidden"
                                                                                name="ring_group_destinations[{{ $destination->ring_group_destination_uuid }}][prompt]"
                                                                                value="false">
                                                                            <input type="checkbox"
                                                                                id="destination_prompt_{{ $destination->ring_group_destination_uuid }}"
                                                                                value="true"
                                                                                name="ring_group_destinations[{{ $destination->ring_group_destination_uuid }}][prompt]"
                                                                                @if ($destination->destination_prompt == '1') checked @endif
                                                                                data-switch="primary" />
                                                                            <label
                                                                                for="destination_prompt_{{ $destination->ring_group_destination_uuid }}"
                                                                                data-on-label="On"
                                                                                data-off-label="Off"></label>
                                                                        </td>
                                                                        <td>
                                                                            <input type="hidden"
                                                                                   name="ring_group_destinations[{{ $destination->ring_group_destination_uuid }}][status]"
                                                                                   value="false">
                                                                            <input type="checkbox"
                                                                                   id="destination_status_{{ $destination->ring_group_destination_uuid }}"
                                                                                   value="true"
                                                                                   name="ring_group_destinations[{{ $destination->ring_group_destination_uuid }}][status]"
                                                                                   @if ($destination->destination_enabled) checked @endif
                                                                                   data-switch="primary" />
                                                                            <label
                                                                                for="destination_status_{{ $destination->ring_group_destination_uuid }}"
                                                                                data-on-label="On"
                                                                                data-off-label="Off"></label>
                                                                        </td>
                                                                        <td>
                                                                            <div id="tooltip-container-actions">
                                                                                <a href="javascript:confirmDeleteDestinationAction('row{{ $destination->ring_group_destination_uuid }}');"
                                                                                    class="action-icon">
                                                                                    <i class="mdi mdi-delete"
                                                                                        data-bs-container="#tooltip-container-actions"
                                                                                        data-bs-toggle="tooltip"
                                                                                        data-bs-placement="bottom"
                                                                                        title="Delete"></i>
                                                                                </a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <div id="addDestinationBar" class="my-1"
                                                            @if ($ringGroup->getGroupDestinations()->count() >= 30) style="display: none;" @endif>
                                                            <a href="javascript:addDestinationAction(this);"
                                                                class="btn btn-success">
                                                                <i class="mdi mdi-plus"></i> Add destination
                                                            </a>
                                                        </div>
                                                        @if ($ringGroup->getGroupDestinations()->count() < 30)
                                                            @include(
                                                                'layouts.partials.destinationExtensionsSelectorCheckboxModal',
                                                                [
                                                                    'label' => 'Add multiple extensions',
                                                                    'extensions' => $extensions['Extensions'],
                                                                    'extensionsSelected' => $ringGroup->getGroupDestinations()->pluck('destination_number'),
                                                                    'callbackOnClick' => 'fillDestinationForm()',
                                                                ]
                                                            )
                                                        @endif
                                                    </div>
                                                    <br /><br />

                                                    @include('layouts.partials.timeoutDestinations', ['entityUuid' => $ringGroup->ring_group_timeout_data])


                                                    <div class="row">
                                                        @if (userCheckPermission('ring_group_cid_name_prefix'))
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label for="ring_group_cid_name_prefix"
                                                                        class="form-label">Caller ID Name Prefix</label>
                                                                    <a href="javascript://" data-bs-toggle="popover"
                                                                        data-bs-placement="right" data-bs-trigger="focus"
                                                                        data-bs-html="true"
                                                                        data-bs-content="<div>
                                                                        The Caller ID Prefix field allows you to add characters to the Caller's ID. This can be useful if you have multiple DID's pointed to the same extension or ring group and you need to identify which number was dialed.
                                                                        </div>"
                                                                        title="">
                                                                        <i class="uil uil-info-circle"></i>
                                                                    </a>
                                                                    <input class="form-control" type="text"
                                                                        placeholder="" id="ring_group_cid_name_prefix"
                                                                        name="ring_group_cid_name_prefix"
                                                                        value="{{ $ringGroup->ring_group_cid_name_prefix }}" />
                                                                    <div id="ring_group_cid_name_prefix_err"
                                                                        class="text-danger error_message"></div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if (userCheckPermission('ring_group_cid_number_prefix'))
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label for="ring_group_cid_number_prefix"
                                                                        class="form-label">Caller ID Number Prefix</label>
                                                                    <a href="javascript://" data-bs-toggle="popover"
                                                                        data-bs-placement="right" data-bs-trigger="focus"
                                                                        data-bs-html="true"
                                                                        data-bs-content="<div>
                                                                        The Caller ID Prefix field allows you to add characters to the Caller's ID. This can be useful if you have multiple DID's pointed to the same extension or ring group and you need to identify which number was dialed.
                                                                        </div>"
                                                                        title="">
                                                                        <i class="uil uil-info-circle"></i>
                                                                    </a>
                                                                    <input class="form-control" type="text"
                                                                        placeholder="" id="ring_group_cid_number_prefix"
                                                                        name="ring_group_cid_number_prefix"
                                                                        value="{{ $ringGroup->ring_group_cid_number_prefix }}" />
                                                                    <div id="ring_group_cid_number_prefix_err"
                                                                        class="text-danger error_message"></div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="ring_group_description"
                                                                    class="form-label">Description</label>
                                                                <input class="form-control" type="text" placeholder=""
                                                                    id="ring_group_description"
                                                                    name="ring_group_description"
                                                                    value="{{ $ringGroup->ring_group_description }}" />
                                                                <div id="ring_group_description_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {{-- <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Enabled</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="ring_group_enabled"
                                                                    value="false">
                                                                <input type="checkbox" id="enabled-switch"
                                                                    name="ring_group_enabled"
                                                                    @if ($ringGroup->ring_group_enabled == 'true') checked @endif
                                                                    data-switch="primary" value="true" />
                                                                <label for="enabled-switch" data-on-label="On"
                                                                    data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div> --}}
                                                    <input type="hidden" name="ring_group_enabled"
                                                                    value="true">
                                                </div>

                                            </div>

                                        </div>
                                        @if (userCheckPermission('ring_group_forward'))
                                            <div class="tab-pane fade" id="v-pills-callforward" role="tabpanel"
                                                aria-labelledby="v-pills-callforward-tab">

                                                <div class="tab-pane show active">
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <h4 class="mt-2">Forward calls</h4>
                                                            <p class="text-muted mb-2">Ensure customers and colleagues can
                                                                reach you, regardless of your physical location.
                                                                Automatically redirect all incoming calls to another phone
                                                                number of your choice.</p>
                                                            <div class="row">
                                                                <div class="mb-2">
                                                                    <input type="hidden"
                                                                        name="ring_group_forward_enabled" value="false">
                                                                    <input type="checkbox" id="ring_group_forward_enabled"
                                                                        value="true" name="ring_group_forward_enabled"
                                                                        data-option="ring_group_forward"
                                                                        class="forward_checkbox"
                                                                        @if ($ringGroup->ring_group_forward_enabled == 'true') checked @endif
                                                                        data-switch="primary" />
                                                                    <label for="ring_group_forward_enabled"
                                                                        data-on-label="On" data-off-label="Off"></label>
                                                                    <div
                                                                        class="text-danger ring_group_forward_enabled_err error_message">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div id="ring_group_forward_phone_number"
                                                                class="row @if ($ringGroup->ring_group_forward_enabled !== 'true') d-none @endif">
                                                                <div class="col-md-12">
                                                                    <p>
                                                                        @include(
                                                                            'layouts.partials.destinationSelector',
                                                                            [
                                                                                'type' => 'ring_group_forward',
                                                                                'id' => 'all',
                                                                                'value' =>
                                                                                    $ringGroup->ring_group_forward_destination,
                                                                                'extensions' => $extensions,
                                                                            ]
                                                                        )
                                                                    <div
                                                                        class="text-danger ring_group_forward_destination_err error_message">
                                                                    </div>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        @endif

                                        <div class="tab-pane fade" id="v-pills-advanced" role="tabpanel"
                                            aria-labelledby="v-pills-home-tab">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mt-2">Advanced</h4>
                                                    <p class="text-muted mb-4">Provide advanced information about the
                                                        ring group</p>
                                                    <div class="row">
                                                        @if (userCheckPermission('ring_group_caller_id_name'))
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label for="ring_group_caller_id_name"
                                                                        class="form-label">Outbound Caller ID Name</label>
                                                                    <input class="form-control" type="text"
                                                                        placeholder="" id="ring_group_caller_id_name"
                                                                        name="ring_group_caller_id_name"
                                                                        value="{{ $ringGroup->ring_group_caller_id_name }}" />
                                                                    <div id="ring_group_caller_id_name_err"
                                                                        class="text-danger error_message"></div>
                                                                    <span class="help-block"><small>Set the caller ID name
                                                                            for
                                                                            outbound external calls.</small></span>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if (userCheckPermission('ring_group_caller_id_number'))
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label for="ring_group_caller_id_number"
                                                                        class="form-label">Outbound Caller ID
                                                                        Number</label>
                                                                    <input class="form-control" type="text"
                                                                        placeholder="" id="ring_group_caller_id_number"
                                                                        name="ring_group_caller_id_number"
                                                                        value="{{ $ringGroup->ring_group_caller_id_number }}" />
                                                                    <div id="ring_group_caller_id_number_err"
                                                                        class="text-danger error_message"></div>
                                                                    <span class="help-block"><small>Set the caller ID
                                                                            number
                                                                            for outbound external calls.</small></span>

                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="ring_group_distinctive_ring"
                                                                    class="form-label">Distinctive Ring</label>
                                                                <input class="form-control" type="text" placeholder=""
                                                                    id="ring_group_distinctive_ring"
                                                                    name="ring_group_distinctive_ring"
                                                                    value="{{ $ringGroup->ring_group_distinctive_ring }}" />
                                                                <div id="ring_group_distinctive_ring_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="ring_group_ringback" class="form-label">Ring
                                                                    Back Music</label>
                                                                <select class="form-control"
                                                                    data-placeholder="Choose ..." id="ring_group_ringback"
                                                                    name="ring_group_ringback">
                                                                    <option
                                                                        value="null"
                                                                        @if (empty($ringGroup->ring_group_ringback)) selected @endif>
                                                                        Don't use ring back music
                                                                    </option>
                                                                    @if (!$moh->isEmpty())
                                                                        <optgroup label="Music on Hold">
                                                                            @foreach ($moh as $music)
                                                                                <option
                                                                                    value="local_stream://{{ $music->music_on_hold_name }}"
                                                                                    @if ('local_stream://' . $music->music_on_hold_name == $ringGroup->ring_group_ringback) selected @endif>
                                                                                    {{ $music->music_on_hold_name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </optgroup>
                                                                    @endif
                                                                    @if (!$recordings->isEmpty())
                                                                        <optgroup label="Recordings">
                                                                            @foreach ($recordings as $recording)
                                                                                <option
                                                                                    value="{{ $recording->recording_filename }}"
                                                                                    @if (getDefaultSetting('switch','recordings'). "/" . Session::get('domain_name') . "/" .$recording->recording_filename == $ringGroup->ring_group_ringback) selected @endif>
                                                                                    {{ $recording->recording_name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </optgroup>
                                                                    @endif
                                                                    <optgroup label="Ringtones">
                                                                        <option value="${us-ring}"
                                                                            @if ($ringGroup->ring_group_ringback == '${us-ring}') selected="selected" @endif>
                                                                            ${us-ring}
                                                                        </option>
                                                                    </optgroup>
                                                                </select>
                                                                <span class="help-block"><small>Ring back audio that the
                                                                        caller hears when calling the ring
                                                                        group</small></span>
                                                                <div id="ring_group_ringback_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Allow Destination Call Forwarding
                                                                    Rules</label>
                                                                <a href="javascript://" data-bs-toggle="popover"
                                                                    data-bs-placement="right" data-bs-trigger="focus"
                                                                    data-bs-html="true"
                                                                    data-bs-content="<div>
                                                                    Allow call forwarding rules for group destinations when the <b>Advanced ring pattern</b> is selected in a ring group.
                                                                    </div>"
                                                                    title="">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden"
                                                                    name="ring_group_call_forward_enabled" value="false">
                                                                <input type="checkbox"
                                                                    id="ring_group_call_forward_enabled"
                                                                    name="ring_group_call_forward_enabled"
                                                                    @if ($ringGroup->ring_group_call_forward_enabled == 'true') checked @endif
                                                                    data-switch="primary" value="true" />
                                                                <label for="ring_group_call_forward_enabled"
                                                                    data-on-label="On" data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Allow Destination Sequential Ring
                                                                    Rules</label>
                                                                <a href="javascript://" data-bs-toggle="popover"
                                                                    data-bs-placement="right" data-bs-trigger="focus"
                                                                    data-bs-html="true"
                                                                    data-bs-content="<div>
                                                                    Allow sequential ring rules for group destinations when the <b>Advanced ring pattern</b> is selected in a ring group.
                                                                    </div>"
                                                                    title="">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="ring_group_follow_me_enabled"
                                                                    value="false">
                                                                <input type="checkbox" id="ring_group_follow_me_enabled"
                                                                    name="ring_group_follow_me_enabled"
                                                                    @if ($ringGroup->ring_group_follow_me_enabled == 'true') checked @endif
                                                                    data-switch="primary" value="true" />
                                                                <label for="ring_group_follow_me_enabled"
                                                                    data-on-label="On" data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if (userCheckPermission('ring_group_missed_call'))
                                                        <div class="row">
                                                            <div class="col-8">
                                                                <div class="mb-3">
                                                                    <label for="ring_group_missed_call_data"
                                                                        class="form-label">Missed Call Notification</label>
                                                                    <div class="row">
                                                                        <div class="col-md-3">
                                                                            <select class="select2 form-control"
                                                                                data-toggle="select2"
                                                                                data-placeholder="Choose ..."
                                                                                id="ring_group_missed_call_category"
                                                                                name="ring_group_missed_call_category">
                                                                                @foreach (['disabled', 'email'] as $missedCallCategory)
                                                                                    <option
                                                                                        value="{{ $missedCallCategory }}"
                                                                                        @if ($ringGroup->ring_group_missed_call_app == $missedCallCategory) selected="selected" @endif>
                                                                                        {{ ucfirst($missedCallCategory) }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                        <div id="missed_call_wrapper" class="col-md-9"
                                                                             @if($ringGroup->ring_group_missed_call_app != 'email') style="display: none" @endif>
                                                                            <input class="form-control" type="text"
                                                                                placeholder=""
                                                                                id="ring_group_missed_call_data"
                                                                                name="ring_group_missed_call_data"
                                                                                value="{{ $ringGroup->ring_group_missed_call_data }}" />
                                                                        </div>
                                                                    </div>
                                                                    <div id="ring_group_missed_call_data_err"
                                                                        class="text-danger error_message"></div>
                                                                </div>
                                                            </div>
                                                    @endif

                                                </div>

                                                @if (userCheckPermission('ring_group_forward_toll_allow'))
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="ring_group_forward_toll_allow"
                                                                    class="form-label">Forward Toll Allow</label>
                                                                <input class="form-control" type="text" placeholder=""
                                                                    id="ring_group_forward_toll_allow"
                                                                    name="ring_group_forward_toll_allow"
                                                                    value="{{ $ringGroup->ring_group_forward_toll_allow }}" />
                                                                <div id="ring_group_forward_toll_allow_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (userCheckPermission('ring_group_context'))
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="ring_group_context"
                                                                    class="form-label">Context</label>
                                                                <input class="form-control" type="text" placeholder=""
                                                                    id="ring_group_context" name="ring_group_context"
                                                                    value="{{ $ringGroup->ring_group_context }}" />
                                                                <div id="ring_group_context_err"
                                                                    class="text-danger error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <input type="hidden" name="ring_group_context"
                                                        value="{{ Session::get('domain_name') }}">
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- end tab-content-->
                            </div> <!-- end col-->
                    </div>
                    </form>
                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>
    </div> <!-- container -->
    <div id="destinationColDelay">
        @if($ringGroup->ring_group_strategy == 'sequence' || $ringGroup->ring_group_strategy == 'rollover' || $ringGroup->ring_group_strategy == 'random')
            <style>.colDelay { display: none; }</style>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        .input-group>.select2-container {
            width: auto !important;
            flex: 1 1 auto;
        }

        .select2-container--open {
            z-index: 10000;
        }

        /*
                            @media (min-width: 576px) {
                                #ForwardDestinationModal > .modal-dialog {
                                    max-width: 800px;
                                }
                            }*/
        .drag-handler {
            cursor: all-scroll;
        }

        #addDestinationBar {
            width: auto;
            text-align: center;
        }

        #addDestinationBarMultiple {
            width: auto;
            text-align: center;
        }

        .destination_wrapper {
            width: 415px;
        }

        @media (max-width: 1724px) {
            .sequential-table {
                width: 100%;
            }

            .sequential-table td .destination_wrapper {
                width: auto !important;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = $('#ringGroupForm');
            //const timeoutAction = $('#timeout_action');
            //const timeoutActionWrapper = $('#timeout_action_wrapper');
            const missedCallWrapper = $('#missed_call_wrapper');

            $('#ring_group_ringback').select2({
                width: 'element'
            });

            applyDestinationSelect2()
/* NOTE: not needed cause we moved it to separate blade resources/views/layouts/partials/timeoutDestinations.blade.php
            $('#timeout_category').on('change', function(e) {
                e.preventDefault();
                if (e.target.value === 'disabled') {
                    timeoutActionWrapper.hide()
                    return;
                } else {
                    timeoutActionWrapper.show()
                }

                timeoutActionWrapper.find('div').hide();
                timeoutActionWrapper.find('div#timeout_action_wrapper_' + e.target.value).show();
            })
*/
            $('#ring_group_missed_call_category').on('change', function(e) {
                e.preventDefault();
                if (e.target.value === 'disabled') {
                    missedCallWrapper.hide()
                } else {
                    missedCallWrapper.show()
                }
            })

            $('#ring_group_strategy').on('change', function(e) {
                e.preventDefault();
                if (e.target.value === 'rollover' || e.target.value === 'sequence' || e.target.value === 'random') {
                    $('#destinationColDelay').append('<style>.colDelay { display: none; }</style>')
                } else {
                    $('#destinationColDelay').empty();
                }
            })

            $('#submitFormButton').on('click', function(e) {
                e.preventDefault();
                $('.loading').show();

                //Reset error messages
                $('.error_message').text("");

                var url = form.attr('action');

                $.ajax({
                    type: "POST",
                    url: url,
                    cache: false,
                    data: form.serialize(),
                    beforeSend: function() {
                        //Reset error messages
                        form.find('.error').text('');
                        $('.error_message').text("");
                        $('.btn').attr('disabled', true);
                        $('.loading').show();
                    },
                    complete: function(xhr, status) {
                        $('.btn').attr('disabled', false);
                        $('.loading').hide();
                    },
                    success: function(result) {
                        $.NotificationApp.send("Success", result.message, "top-right",
                            "#10c469", "success");
                        if(result.redirect_url){
                            window.location=result.redirect_url;
                        } else {
                            $('.loading').hide();
                        }
                    },
                    error: function(error) {
                        $('.loading').hide();
                        $('.btn').attr('disabled', false);
                        if (error.status == 422) {
                            if (error.responseJSON.errors) {
                                $.each(error.responseJSON.errors, function(key, value) {
                                    if (value != '') {
                                        form.find('#' + key + '_err').text(value);
                                        printErrorMsg(value);
                                    }
                                });
                            } else {
                                printErrorMsg(error.responseJSON.message);
                            }
                        } else {
                            printErrorMsg(error.responseJSON.message);
                        }
                    }
                })
            });

            $(document).on('click', '.forward_checkbox', function(e) {
                var checkbox = $(this);
                var cname = checkbox.data('option');
                if (checkbox.is(':checked')) {
                    $('#' + cname + '_phone_number').removeClass('d-none');
                } else {
                    $('#' + cname + '_phone_number').addClass('d-none');
                    $('#' + cname + '_phone_number').find('.mx-1').find('select').val('internal');
                    $('#' + cname + '_phone_number').find('.mx-1').find('select').trigger('change');
                }
            });

            let sortable = new Sortable(document.getElementById('destination_sortable'), {
                delay: 0, // time in milliseconds to define when the sorting should start
                delayOnTouchOnly: false, // only delay if user is using touch
                touchStartThreshold: 0, // px, how many pixels the point should move before cancelling a delayed drag event
                disabled: false, // Disables the sortable if set to true.
                store: null, // @see Store
                animation: 150, // ms, animation speed moving items when sorting, `0` — without animation
                easing: "cubic-bezier(1, 0, 0, 1)", // Easing for animation. Defaults to null. See https://easings.net/ for examples.
                handle: ".drag-handler", // Drag handle selector within list items
                filter: ".ignore-elements", // Selectors that do not lead to dragging (String or Function)
                preventOnFilter: true, // Call `event.preventDefault()` when triggered `filter`

                ghostClass: "sortable-ghost", // Class name for the drop placeholder
                chosenClass: "sortable-chosen", // Class name for the chosen item
                dragClass: "sortable-drag", // Class name for the dragging item

                swapThreshold: 1, // Threshold of the swap zone
                invertSwap: false, // Will always use inverted swap zone if set to true
                invertedSwapThreshold: 1, // Threshold of the inverted swap zone (will be set to swapThreshold value by default)
                direction: 'vertical', // Direction of Sortable (will be detected automatically if not given)

                forceFallback: false, // ignore the HTML5 DnD behaviour and force the fallback to kick in

                fallbackClass: "sortable-fallback", // Class name for the cloned DOM Element when using forceFallback
                fallbackOnBody: false, // Appends the cloned DOM Element into the Document's Body
                fallbackTolerance: 0, // Specify in pixels how far the mouse should move before it's considered as a drag.

                dragoverBubble: false,
                removeCloneOnHide: true, // Remove the clone element when it is not showing, rather than just hiding it
                emptyInsertThreshold: 5, // px, distance mouse must be from empty sortable to insert drag element into it

                setData: function( /** DataTransfer */ dataTransfer, /** HTMLElement*/ dragEl) {
                    dataTransfer.setData('Text', dragEl
                        .textContent); // `dataTransfer` object of HTML5 DragEvent
                },

                // Element dragging ended
                onEnd: function( /**Event*/ evt) {
                    updateDestinationOrder()
                }
            });

            $(`#ring_group_forward_target_internal_all`).select2();
            $(`#ring_group_forward_type_all`).select2();
        });

        function showHideAddDestination() {
            if ($('#destination_sortable > tr').length > 49) {
                $('#addDestinationBar').hide();
            } else {
                $('#addDestinationBar').show();
            }
        }

        function applyDestinationSelect2() {
            $('#destination_sortable > tr').each(function(i, el) {
                $(el).find('select').each(function(i, el2) {
                    if ($(el2).data('select2')) {
                        $(el2).select2('destroy').hide()
                        $(el2).select2({
                            width: 'element'
                        }).show()
                    } else {
                        $(el2).select2({
                            width: 'element'
                        }).show()
                    }
                });
            })
            $('[data-bs-toggle="tooltip"]').tooltip();
        }

        function getDestinationByCategory(category, ringGroupTimeoutAction, ringGroupTimeoutActionWrapper) {
            $.ajax({
                type: "GET",
                url: '/ring-groups-destination-category/' + category,
                cache: false,
                beforeSend: function() {

                },
                complete: function(xhr, status) {

                },
                success: function(result) {
                    ringGroupTimeoutAction.empty().trigger('change');
                    if (result.list.length > 0) {
                        for (let i = 0; i < result.list.length; i++) {
                            var option = new Option(result.list[i].label, result.list[i].id);
                            ringGroupTimeoutAction.append(option)
                        }
                        ringGroupTimeoutAction.trigger('change');
                    }
                    ringGroupTimeoutActionWrapper.show()
                },
                error: function(error) {
                    console.warn(error)
                    alert('something went wrong')
                }
            })
        }

        function updateDestinationOrder() {
            $('#destination_sortable > tr').each(function(i, el) {
                $(el).find('.drag-handler').find('span').text(i + 1)
            })
        }
        function addDestinationAction(el) {
            let wrapper = $(`#destination_sortable > tr`)
            let count = wrapper.length
            let newCount = (count + 1)
            if (newCount > 50) {
                return false;
            }

            let newRow = `
        <tr id="row__NEWROWID__"><td class="drag-handler"><i class="mdi mdi-drag"></i> <span>__NEWROWID__</span></td>
        <td>
        @include('layouts.partials.destinationSelector', [
            'type' => 'ring_group_destinations',
            'id' => '__NEWROWID__',
            'value' => '',
            'extensions' => $extensions,
        ])
            </td>
            <td class="colDelay"><select id="destination_delay___NEWROWID__" name="ring_group_destinations[newrow__NEWROWID__][delay]">
@for ($i = 0; $i < 20; $i++) <option value="{{ $i * 5 }}" @if ($i == 0) selected @endif>
        {{ $i }} @if ($i > 1) Rings @else Ring @endif - {{ $i * 5 }} Sec</option> @endfor </select></td>
        <td><select id="destination_timeout___NEWROWID__" name="ring_group_destinations[newrow__NEWROWID__][timeout]">
        @for ($i = 1; $i < 21; $i++) <option value="{{ $i * 5 }}" @if ($i == 5) selected @endif>
        {{ $i }} @if ($i > 1) Rings @else Ring @endif - {{ $i * 5 }} Sec</option> @endfor </select></td><td>
        <input type="hidden" name="ring_group_destinations[newrow__NEWROWID__][prompt]" value="false">
        <input type="checkbox" id="destination_prompt___NEWROWID__" value="true" name="ring_group_destinations[newrow__NEWROWID__][prompt]" data-option="ring_group_destinations_prompt" class="forward_checkbox" data-switch="primary"/>
        <label for="destination_prompt___NEWROWID__" data-on-label="On" data-off-label="Off"></label>
        </td>
        <td><input type="hidden" name="ring_group_destinations[newrow__NEWROWID__][status]" value="false">
        <input type="checkbox" id="destination_status___NEWROWID__" value="true" name="ring_group_destinations[newrow__NEWROWID__][status]" data-option="ring_group_destinations_enabled" class="forward_checkbox" data-switch="primary" checked />
        <label for="destination_status___NEWROWID__" data-on-label="On" data-off-label="Off"></label>
        </td><td><div class="tooltip-container-actions"><a href="javascript:confirmDeleteDestinationAction('row__NEWROWID__');" class="action-icon">
        <i class="mdi mdi-delete" data-bs-container=".tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
        </a></div></td></tr>`;
            newRow = newRow.replaceAll('__NEWROWID__', Math.random().toString(16).slice(2))

            newRow = $(newRow)

            $('#destination_sortable').append(newRow)

            showHideAddDestination()
            updateDestinationOrder()
            applyDestinationSelect2()

            return newRow;
        }

        function confirmDeleteDestinationAction(el) {
            if ($(`#${el}`).data('select2')) {
                $(`#${el}`).select2('destroy').hide()
            }
            $(`#${el}`).remove();
            updateDestinationOrder()
            showHideAddDestination()
        }

        function fillDestinationForm() {
            const values = $('#destinationMultipleListExtensions').find('.action_checkbox:checked')
            for (let i = 0; i < values.length; i++) {
                let value = values[i].value.trim()
                if (value !== '' && !destinationsSelected.includes(value)) {
                    let addedRow = addDestinationAction(null)
                    if (value.length <= 5) {
                        addedRow.find('.flex-fill').find('select').val(value).trigger('change')
                        addedRow.find('.mx-1').find('select').val('internal').trigger('change')
                        destinationsSelected.push(value)
                    } else {
                        addedRow.find('.flex-fill').find('input').val(value)
                        addedRow.find('.mx-1').find('select').val('external').trigger('change')
                    }
                }
            }
            $('#addDestinationMultipleModal').modal('hide');
        }
    </script>
@endpush
