@extends('layouts.app', ["page_title"=> "Edit Extension"])

@section('content')
    @php
        /** @var \App\Models\Extensions $extension */
    @endphp
<!-- Start Content-->
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('extensions.index') }}">Extensions</a></li>
                        @if($extension->exists)
                            <li class="breadcrumb-item active">Edit Extension</li>
                        @else
                            <li class="breadcrumb-item active">Create Extension</li>
                        @endif
                    </ol>
                </div>
                @if($extension->exists)
                    <h4 class="page-title">Edit Extension ({{ $extension->extension }})</h4>
                @else
                    <h4 class="page-title">Create Extension</h4>
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
                        if ($extension->exists) {
                            $actionUrl = route('extensions.update', $extension);
                        } else {
                            $actionUrl = route('extensions.store');
                        }
                    @endphp
                    <form method="POST" id="extensionForm" action="{{$actionUrl}}" class="form">
                        @if ($extension->exists)
                            @method('put')
                        @endif
                        @csrf
                    <div class="row">
                        <div class="col-sm-2 mb-2 mb-sm-0">
                            <div class="nav flex-column nav-pills" id="extensionNavPills" role="tablist" aria-orientation="vertical">
                                <a class="nav-link active show" id="v-pills-home-tab" data-bs-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home"
                                    aria-selected="true">
                                    <i class="mdi mdi-home-variant d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Basic Information
                                        <span class="float-end text-end
                                            directory_first_name_err_badge
                                            directory_last_name_err_badge
                                            extension_err_badge
                                            voicemail_mail_to_err_badge
                                            users_err_badge
                                            directory_visible_err_badge
                                            directory_exten_visible_err_badge
                                            enabled_err_badge
                                            description_err_badge
                                            " hidden><span class="badge badge-danger-lighten">error</span></span>
                                    </span>
                                </a>
                                <a class="nav-link" id="v-pills-callerid-tab" data-bs-toggle="pill" href="#v-pills-callerid" role="tab" aria-controls="v-pills-callerid"
                                    aria-selected="false">
                                    <i class="mdi mdi-account-circle d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Caller ID
                                        @if( $errors->has('outbound_caller_id_number') ||
                                            $errors->has('emergency_caller_id_number'))
                                            <span class="float-end text-end"><span class="badge badge-danger-lighten">error</span></span>
                                        @endif
                                        <span class="float-end text-end
                                            outbound_caller_id_number_err_badge
                                            emergency_caller_id_number_err_badge
                                            " hidden><span class="badge badge-danger-lighten">error</span></span>
                                    </span>
                                </a>

                                <a class="nav-link" id="v-pills-callforward-tab" data-bs-toggle="pill" href="#v-pills-callforward" role="tab" aria-controls="v-pills-callforward"
                                   aria-selected="false">
                                    <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Call Forward
                                        <span class="float-end text-end
                                            forward_all_enabled_err_badge
                                            forward_all_destination_err_badge
                                            forward_busy_enabled_err_badge
                                            forward_busy_destination_err_badge
                                            forward_no_answer_enabled_err_badge
                                            forward_no_answer_destination_err_badge
                                            forward_user_not_registered_enabled_err_badge
                                            forward_user_not_registered_destination_err_badge
                                            " hidden><span class="badge badge-danger-lighten">error</span></span>
                                    </span>
                                </a>

                                @if (userCheckPermission('voicemail_option_edit') && $extension->exists)
                                <a class="nav-link" id="v-pills-voicemail-tab" data-bs-toggle="pill" href="#v-pills-voicemail" role="tab" aria-controls="v-pills-voicemail"
                                    aria-selected="false">
                                    <i class="mdi mdi-account-circle d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Voicemail
                                        <span class="float-end text-end
                                            voicemail_enabled_err_badge
                                            voicemail_password_err_badge
                                            voicemail_transcription_enabled_err_badge
                                            voicemail_local_after_email_err_badge
                                            voicemail_description_err_badge
                                            voicemail_tutorial_err_badge
                                            voicemail_destinations_err_badge
                                            " hidden><span class="badge badge-danger-lighten">error</span></span>
                                    </span>
                                </a>
                                @endif

                                @if ($extension->exists)
                                    <a class="nav-link" id="v-pills-device-tab" data-bs-toggle="pill" href="#v-pills-device" role="tab" aria-controls="v-pills-device"
                                       aria-selected="false">
                                        <i class="mdi mdi-devices-circle d-md-none d-inline-block"></i>
                                        <span class="d-inline-block">Devices</span>
                                    </a>
                                @endif

                                <a class="nav-link" id="v-pills-settings-tab" data-bs-toggle="pill" href="#v-pills-settings" role="tab" aria-controls="v-pills-settings"
                                   aria-selected="false">
                                    <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Settings
                                        <span class="float-end text-end
                                            domain_uuid_err_badge
                                            user_context_err_badge
                                            max_registrations_err_badge
                                            limit_max_err_badge
                                            limit_destination_err_badge
                                            toll_allow_err_badge
                                            call_group_err_badge
                                            call_screen_enabled_err_badge
                                            user_record_err_badge
                                            auth_acl_err_badge
                                            sip_force_contact_err_badge
                                            sip_force_expires_err_badge
                                            mwi_account_err_badge
                                            sip_bypass_media_err_badge
                                            absolute_codec_string_err_badge
                                            force_ping_err_badge
                                            dial_string_err_badge
                                            hold_music_err_badge
                                            " hidden><span class="badge badge-danger-lighten">error</span></span>
                                    </span>
                                </a>
                            </div>
                        </div> <!-- end col-->

                            <div class="col-sm-10">

                                <div class="tab-content">
                                    <div class="text-sm-end" id="action-buttons">
                                        <a href="{{ route('extensions.index') }}" class="btn btn-light me-2">Cancel</a>
                                        <button class="btn btn-success" type="submit" id="submitFormButton"><i class="uil uil-down-arrow me-2"></i> Save </button>
                                        {{-- <button class="btn btn-success" type="submit">Save</button> --}}
                                    </div>
                                    <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                        <!-- Basic Info Content-->
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <h4 class="mt-2">Basic information</h4>

                                                <p class="text-muted mb-4">Provide basic information about the user or extension</p>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="directory_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                                                <input class="form-control" type="text" placeholder="Enter first name" id="directory_first_name"
                                                                    name="directory_first_name" value="{{ $extension->directory_first_name }}"/>
                                                                <div class="text-danger directory_first_name_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="directory_last_name" class="form-label">Last Name</label>
                                                                <input class="form-control" type="text" placeholder="Enter last name" id="directory_last_name"
                                                                    name="directory_last_name" value="{{ $extension->directory_last_name }}"/>
                                                                <div class="text-danger directory_last_name_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <input type="hidden" name="extension" value="{{ $extension->extension }}">
                                                                <label for="extension" class="form-label">Extension number <span class="text-danger">*</span></label>
                                                                <input class="form-control" type="text" placeholder="xxxx" id="extension"
                                                                    name="extension" value="{{ $extension->extension }}" @if (!userCheckPermission('extension_extension')) disabled @endif/>
                                                                <div class="text-danger error-text extension_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @if (userCheckPermission('voicemail_edit'))
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="voicemail_mail_to" class="form-label">Email Address </label>
                                                                <input class="form-control" type="email" placeholder="Enter email" id="voicemail_mail_to"
                                                                    @if ($extension->exists && (!$extension->voicemail || !$extension->voicemail->voicemail_uuid))
                                                                        disabled
                                                                    @endif
                                                                    name="voicemail_mail_to" value="{{ $extension->voicemail->voicemail_mail_to ?? '' }}"/>
                                                                <div class="text-danger error-text voicemail_mail_to_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </div> <!-- end row -->



                                                    @if (userCheckPermission('extension_directory'))
                                                    <div class="row">
                                                        <div class="col-lg-4 col-sm-8">
                                                            <div class="mb-3">
                                                                <label class="form-label">
                                                                    Display contact in the company's dial by name directory&nbsp;
                                                                    <span class="info-icon">
                                                                        <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                            data-bs-content="This user will appear in the company's dial by name directory">
                                                                            <i class="uil uil-info-circle"></i>
                                                                        </a>
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="directory_visible" value="false">
                                                                <input type="checkbox" id="directory_visible" name="directory_visible"
                                                                @if ($extension->directory_visible == "true") checked @endif
                                                                data-switch="primary"/>
                                                                <label for="directory_visible" data-on-label="On" data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->

                                                    <div class="row">
                                                        <div class="col-lg-4 col-sm-8">
                                                            <div class="mb-3">
                                                                <label class="form-label">
                                                                    Announce extension in the dial by name directory&nbsp;
                                                                    <span class="info-icon">
                                                                        <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                            data-bs-content="Announce user's extension when calling the dial by name directory">
                                                                            <i class="uil uil-info-circle"></i>
                                                                        </a>
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="directory_exten_visible" value="false">
                                                                <input type="checkbox" id="directory_exten_visible" name="directory_exten_visible"
                                                                @if ($extension->directory_exten_visible == "true") checked @endif
                                                                data-switch="primary"/>
                                                                <label for="directory_exten_visible" data-on-label="On" data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @else
                                                        <input type="hidden" name="directory_visible" value="true">
                                                        <input type="hidden" name="directory_exten_visible" value="true">
                                                    @endif

                                                    @if (userCheckPermission('extension_enabled'))
                                                    <div class="row">
                                                        <div class="col-lg-4 col-sm-8">
                                                            <div class="mb-3">
                                                                <label  class="form-label">Enabled </label>
                                                                <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                    data-bs-content="This prevents devices from registering using this extension">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="enabled" value="false">
                                                                <input type="checkbox" id="enabled-switch" name="enabled"
                                                                @if ($extension->enabled == "true") checked @endif
                                                                data-switch="primary"/>
                                                                <label for="enabled-switch" data-on-label="On" data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @else
                                                        <input type="hidden" name="enabled" value="{{ $extension->enabled }}">
                                                    @endif

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="description" class="form-label">Description</label>
                                                                <input class="form-control" type="text" placeholder="" id="description" name="description" autocomplete="off"
                                                                    value="{{ $extension->description }}"/>
                                                                    <div class="text-danger description_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->

                                                    <div class="row">
                                                        <div class="col-lg-4 col-sm-8">
                                                            <div class="mb-3">
                                                                <label  class="form-label">Suspended </label>
                                                                <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                    data-bs-content="This prevents users from making or receiving calls except for emergency calls. The most common reason for suspensions is billing issues.">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="suspended" value="false">
                                                                <input type="checkbox" id="suspended-switch" name="suspended"
                                                                @if ($extension->suspended) checked @endif
                                                                @if (!userCheckPermission('extension_suspended')) disabled @endif
                                                                data-switch="warning"/>
                                                                <label for="suspended-switch" data-on-label="On" data-off-label="Off"></label>

                                                                @if (!userCheckPermission('extension_suspended'))
                                                                    <input type="hidden" name="suspended" value="{{ $extension->suspended ? 'on' : 'false' }}">
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    
                                            </div>

                                        </div> <!-- end row-->

                                    </div>
                                    <!-- Caller ID Content-->
                                    <div class="tab-pane fade" id="v-pills-callerid" role="tabpanel" aria-labelledby="v-pills-callerid-tab">
                                            <div class="row">
                                                @if (userCheckPermission('outbound_caller_id_number'))
                                                <div class="col-lg-12">
                                                    <h4 class="mt-2">External Caller ID</h4>

                                                    <p class="text-muted mb-3">Define the External Caller ID that will be displayed on the recipient's device when dialing outside the company.</p>

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Phone Number</label>
                                                                    <select data-toggle="select2" title="Outbound Caller ID" name="outbound_caller_id_number">
                                                                        <option value="">Main Company Number</option>
                                                                        @foreach ($destinations as $destination)
                                                                            <option value="{{ $destination->destination_number }}"
                                                                                @if ($destination->isCallerID)
                                                                                    selected
                                                                                @endif>
                                                                                {{ $destination->label }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->
                                                </div>
                                                @else
                                                    <input type="hidden" name="outbound_caller_id_number" value="">
                                                @endif

                                                @if (userCheckPermission('effective_caller_id_name') || userCheckPermission('effective_caller_id_number'))
                                                <div class="col-lg-12">
                                                    <h4 class="mt-4">Internal Caller ID</h4>

                                                    <p class="text-muted mb-3">Define the Internal Caller ID that will be displayed on the recipient's device when dialing inside the company.</p>

                                                    @if (userCheckPermission('effective_caller_id_name'))
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="callerid-first-name" class="form-label">First Name</label>
                                                                <input class="form-control" type="text" placeholder="Enter first name" disabled
                                                                    id="callerid-first-name" value="{{ $extension->directory_first_name }}" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="callerid-last-name" class="form-label">Last Name</label>
                                                                <input class="form-control" type="text" placeholder="Enter last name" disabled
                                                                    id="callerid-last-name" value="{{ $extension->directory_last_name }}" />
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif

                                                    @if (userCheckPermission('effective_caller_id_number'))
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="effective_caller_id_number" class="form-label">Extension number</label>
                                                                <input class="form-control" type="text" placeholder="xxxx"  disabled id="effective_caller_id_number"
                                                                name="effective_caller_id_number" value="{{ $extension->effective_caller_id_number }}"/>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif
                                                </div>
                                                @endif

                                                @if (userCheckPermission('emergency_caller_id_number'))
                                                <div class="col-lg-12">
                                                    <h4 class="mt-4">Emergency Caller ID</h4>

                                                    <p class="text-muted mb-3">Define the Emergency Caller ID that will be displayed when dialing emergency services.</p>

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Phone Number</label>
                                                                    <select data-toggle="select2" title="Emergency Caller ID" name="emergency_caller_id_number">
                                                                        <option value="">Main Company Number</option>
                                                                        @foreach ($destinations as $destination)
                                                                            <option value="{{ $destination->destination_number }}"
                                                                                @if (($destination->isEmergencyCallerID))
                                                                                    selected
                                                                                @endif>
                                                                                {{ $destination->label }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->
                                                </div>
                                                @else
                                                    <input type="hidden" name="emergency_caller_id_number" value="">
                                                @endif

                                            </div> <!-- end row-->
                                        <!-- End Caller ID Content-->
                                    </div>
                                    @if (userCheckPermission('voicemail_option_edit') && $extension->exists)
                                    <div class="tab-pane fade" id="v-pills-voicemail" role="tabpanel" aria-labelledby="v-pills-voicemail-tab">
                                        <!-- Voicemail Content-->
                                        <div class="tab-pane show active">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mt-2">Voicemail settings</h4>

                                                    <p class="text-muted mb-4">Voicemail settings allow you to update your voicemail access PIN, personalize, maintain and update your voicemail greeting to inform your friends, customers, or colleagues of your status.</p>

                                                    <input type="hidden" id="voicemail_id" name="voicemail_id"
                                                        data-uuid="{{ $extension->voicemail->voicemail_uuid ?? ''}}"
                                                        data-extensionuuid="{{ $extension->extension_uuid ?? ''}}"
                                                        value="{{ $extension->extension }}">

                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Voicemail enabled </label>
                                                                @if ($extension->suspended)
                                                                    <p class="text-danger mb-2">This option is unavailable because the extension is suspended.</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="voicemail_enabled" value="false">
                                                                <input type="checkbox" id="voicemail_enabled" name="voicemail_enabled"
                                                                @if ($extension->voicemail->exists && $extension->voicemail->voicemail_enabled == "true") checked @endif
                                                                @if ($extension->suspended) disabled @endif
                                                                data-switch="primary"/>
                                                                <label for="voicemail_enabled" data-on-label="On" data-off-label="Off"></label>
                                                                <div class="text-danger voicemail_enabled_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->

                                                    @if ($extension->voicemail->exists)

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">If no answer, send to voicemail after</label>
                                                                <select data-toggle="select2" title="If no answer, send to voicemail after" name="call_timeout">
                                                                    @for ($i = 1; $i < 21; $i++)
                                                                        <option value="{{ $i * 5 }}" @if ($extension->call_timeout == $i*5) selected @endif>
                                                                            {{ $i }} @if ($i >1 ) Rings @else Ring @endif - {{ $i * 5 }} Sec
                                                                        </option>
                                                                    @endfor
                                                                </select>
                                                            <div class="text-danger call_timeout_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->


                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="voicemail_password" class="form-label">Set voicemail PIN <span class="text-danger">*</span></label>
                                                                <div class="input-group input-group-merge">
                                                                    <input type="password" id="voicemail_password" class="form-control" placeholder="xxxx"
                                                                    value="{{ $extension->voicemail->voicemail_password ?? ''}}" name="voicemail_password">
                                                                    <div class="input-group-text" data-password="false">
                                                                        <span class="password-eye"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="text-danger voicemail_password_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Notification type</label>
                                                                <select data-toggle="select2" title="Notification Type" name="voicemail_file">
                                                                    <option value="attach" @if (isset($voicemail) && $voicemail->voicemail_file == "attach") selected @endif>
                                                                        Email with audio file attachment
                                                                    </option>
                                                                    <option value="link" @if (isset($voicemail) && $voicemail->exists && ($voicemail->voicemail_file == "link" || $voicemail->voicemail_file == "")) selected @endif>
                                                                        Email with download link
                                                                    </option>
                                                                </select>
                                                                <div class="text-danger voicemail_file_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="vm-email-address" class="form-label">Email Address</label>
                                                                <input class="form-control" type="email" disabled placeholder="Enter email" id="vm-email-address"
                                                                value="{{ $extension->voicemail->voicemail_mail_to ?? ''}}"/>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->

                                                    @if (userCheckPermission('voicemail_transcription_edit'))
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Enable voicemail transcription </label>
                                                                <a href="#"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                    data-bs-content="Send a text trancsript. Accuracy may vary based on call quality, accents, vocabulary, etc. ">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="voicemail_transcription_enabled" value="false">
                                                                <input type="checkbox" id="voicemail_transcription_enabled" data-switch="primary" name="voicemail_transcription_enabled"
                                                                @if ($extension->voicemail->voicemail_transcription_enabled == "true") checked @endif />
                                                                <label for="voicemail_transcription_enabled" data-on-label="On" data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif

                                                    @if (userCheckPermission('voicemail_local_after_email'))
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Delete voicemail after sending email </label>
                                                                <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                    data-bs-content="Enables email-only voicemail. Disables storing of voicemail messages for this mailbox in the cloud.">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="voicemail_local_after_email" value="false">
                                                                <input type="checkbox" id="voicemail_local_after_email" data-switch="primary" name="voicemail_local_after_email"
                                                                @if (isset($extension->voicemail) && $extension->voicemail->voicemail_local_after_email == "false") checked @endif />
                                                                <label for="voicemail_local_after_email" data-on-label="On" data-off-label="Off"></label>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif


                                                    <div class="row mb-4">
                                                        <div class="col-lg-6">
                                                            <h4 class="mt-2">Unavailable greeting</h4>

                                                            <p class="text-muted mb-2">This plays when you do not pick up the phone.</p>
                                                            <p class="text-black-50 mb-1">Play the default, upload or record a new message.</p>

                                                            <audio id="voicemail_unavailable_audio_file"
                                                                @if ($vm_unavailable_file_exists)
                                                                src="{{ route('getVoicemailGreeting', ['voicemail' => $extension->voicemail->voicemail_uuid,'filename' => 'greeting_1.wav'] ) }}"
                                                                @endif
                                                            >
                                                            </audio>
                                                            <p class="text-muted mb-1">File name: <span id='voicemailUnavailableFilename'>
                                                                <strong>
                                                                    @if ($vm_unavailable_file_exists) greeting_1.wav
                                                                    @else generic greeting
                                                                    @endif
                                                                </strong></span></p>
                                                            <button type="button" class="btn btn-light" id="voicemail_unavailable_play_button"
                                                                @if (!$vm_unavailable_file_exists) disabled @endif
                                                                title="Play"><i class="uil uil-play"></i>
                                                            </button>

                                                            <button type="button" class="btn btn-light" id="voicemail_unavailable_pause_button" title="Pause"><i class="uil uil-pause"></i> </button>

                                                            <button id="voicemail_unavailable_upload_file_button" data-url="{{ route("uploadVoicemailGreeting", $extension->voicemail->voicemail_uuid) }}" type="button" class="btn btn-light" title="Upload">
                                                                <span id="voicemail_unavailable_upload_file_button_icon" ><i class="uil uil-export"></i> </span>
                                                                <span id="voicemail_unavailable_upload_file_button_spinner" hidden class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                            </button>
                                                            <input id="voicemail_unavailable_upload_file" type="file" hidden/>

                                                            <a href="{{ route('downloadVoicemailGreeting', [
                                                                'voicemail' => $extension->voicemail->voicemail_uuid,
                                                                'filename' => 'greeting_1.wav'
                                                                ] ) }}">
                                                                    <button id="voicemail_unavailable_download_file_button" type="button" class="btn btn-light" title="Download"
                                                                    @if (!$vm_unavailable_file_exists) disabled @endif>
                                                                    <i class="uil uil-down-arrow"></i>
                                                                </button>
                                                            </a>

                                                            <button id="voicemail_unavailable_delete_file_button" type="button" class="btn btn-light" title="Delete"
                                                                data-url="{{ route('deleteVoicemailGreeting', ['voicemail' => $extension->voicemail->voicemail_uuid,'filename' => 'greeting_1.wav'] ) }}"
                                                                @if (!$vm_unavailable_file_exists) disabled @endif>
                                                                <span id="voicemail_unavailable_delete_file_button_icon" ><i class="uil uil-trash-alt"></i> </span>
                                                                <span id="voicemail_unavailable_delete_file_button_spinner" hidden class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                            </button>


                                                            <div class="text-danger" id="voicemailUnvaialableGreetingError"></div>

                                                        </div>

                                                        <div class="col-lg-6">
                                                            <h4 class="mt-2">Name greeting</h4>

                                                            <p class="text-muted mb-2">This plays to identify your extension in the company's dial by name directory.</p>
                                                            <p class="text-black-50 mb-1">Play the default, upload or record a new message.</p>
                                                            <audio id="voicemail_name_audio_file"
                                                                @if ($vm_name_file_exists)
                                                                src="{{ route('getVoicemailGreeting', ['voicemail' => $extension->voicemail->voicemail_uuid,'filename' => 'recorded_name.wav'] ) }}"
                                                                @endif >
                                                            </audio>
                                                            <p class="text-muted mb-1">File name: <span id='voicemailNameFilename'>
                                                                <strong>
                                                                    @if ($vm_name_file_exists) recorded_name.wav
                                                                    @else generic greeting
                                                                    @endif
                                                                </strong></span></p>
                                                            <button type="button" class="btn btn-light" id="voicemail_name_play_button"
                                                                @if (!$vm_name_file_exists) disabled @endif
                                                                title="Play"><i class="uil uil-play"></i>
                                                            </button>

                                                            <button type="button" class="btn btn-light" id="voicemail_name_pause_button" title="Pause"><i class="uil uil-pause"></i> </button>

                                                            <button id="voicemail_name_upload_file_button" data-url="{{ route("uploadVoicemailGreeting", $extension->voicemail->voicemail_uuid) }}" type="button" class="btn btn-light" title="Upload">
                                                                <span id="voicemail_name_upload_file_button_icon" ><i class="uil uil-export"></i> </span>
                                                                <span id="voicemail_name_upload_file_button_spinner" hidden class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                            </button>
                                                            <input id="voicemail_name_upload_file" type="file" hidden data-url="{{ route("uploadVoicemailGreeting", $extension->voicemail->voicemail_uuid) }}"/>

                                                            <a href="{{ route('downloadVoicemailGreeting', [
                                                                'voicemail' => $extension->voicemail->voicemail_uuid,
                                                                'filename' => 'recorded_name.wav'
                                                                ] ) }}">
                                                                    <button id="voicemail_name_download_file_button" type="button" class="btn btn-light" title="Download"
                                                                    @if (!$vm_name_file_exists) disabled @endif>
                                                                    <i class="uil uil-down-arrow"></i>
                                                                </button>
                                                            </a>

                                                            <button id="voicemail_name_delete_file_button" type="button" class="btn btn-light" title="Delete"
                                                                data-url="{{ route('deleteVoicemailGreeting', ['voicemail' => $extension->voicemail->voicemail_uuid,'filename' => 'recorded_name.wav'] ) }}"
                                                                @if (!$vm_name_file_exists) disabled @endif>
                                                                <span id="voicemail_name_delete_file_button_icon" ><i class="uil uil-trash-alt"></i> </span>
                                                                <span id="voicemail_name_delete_file_button_spinner" hidden class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                            </button>

                                                            <div class="text-danger" id="voicemailNameGreetingError"></div>

                                                        </div>

                                                    </div> <!-- end row-->

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="voicemail_alternate_greet_id" class="form-label">Alternative greet ID</label>
                                                                <input class="form-control" type="text" placeholder="" id="voicemail_alternate_greet_id" name="voicemail_alternate_greet_id"/>
                                                                <span class="help-block"><small>An alternative greet id used in the default greeting.</small></span>
                                                                <div class="text-danger voicemail_alternate_greet_id_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="voicemail_description" class="form-label">Description</label>
                                                                <input class="form-control" type="text" placeholder="" id="voicemail_description" name="voicemail_description"
                                                                value="{{ $extension->voicemail->voicemail_description }}"/>
                                                                <div class="text-danger voicemail_description_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->


                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Play voicemail tutorial </label>
                                                                <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                    data-bs-content="Play the voicemail tutorial after the next voicemail login.">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="voicemail_tutorial" value="false">
                                                                <input type="checkbox" id="voicemail_tutorial" data-switch="primary" name="voicemail_tutorial"
                                                                @if ($extension->voicemail->voicemail_tutorial == "true") checked @endif />
                                                                <label for="voicemail_tutorial" data-on-label="On" data-off-label="Off"></label>
                                                                <div class="text-danger voicemail_tutorial_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->

                                                    @if (userCheckPermission('voicemail_forward'))
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="additional-destinations-select" class="form-label">Forward voicemail messages to additional destinations.</label>
                                                                <!-- Multiple Select -->
                                                                <select class="select2 form-control select2-multiple" data-toggle="select2" multiple="multiple" data-placeholder="Choose ..."
                                                                id="additional-destinations-select" name="voicemail_destinations[]">
                                                                    @foreach ($domain_voicemails as $domain_voicemail)
                                                                        <option value="{{ $domain_voicemail->voicemail_uuid }}"
                                                                            @if($extension->voicemail->forward_destinations()->contains($domain_voicemail))
                                                                                selected
                                                                            @endif>
                                                                            @if (isset($domain_voicemail->extension->directory_first_name) ||
                                                                                isset($domain_voicemail->extension->directory_last_name))
                                                                                    {{ $domain_voicemail->extension->directory_first_name ?? ""}}

                                                                                    {{ $domain_voicemail->extension->directory_last_name ?? ""}}
                                                                                (ext {{ $domain_voicemail->voicemail_id }})
                                                                            @elseif ($domain_voicemail->voicemail_description)
                                                                                {{ $domain_voicemail->voicemail_description }} (ext {{ $domain_voicemail->voicemail_id }})
                                                                            @else
                                                                                Voicemail (ext {{ $domain_voicemail->voicemail_id }})
                                                                            @endif
                                                                        </option>
                                                                    @endforeach
                                                            </select>
                                                            <div class="text-danger voicemail_destinations_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif

                                                    <h4 class="mt-2">Exiting voicemail options</h4>

                                                    <div class="row">
                                                        <div class="col-1">
                                                            <div class="mb-3">
                                                                <label for="voicemail-option" class="form-label">Option</label>
                                                                <input class="form-control" type="email" placeholder="" id="voicemail-option" />
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3">
                                                                <label class="form-label">Destination type</label>
                                                                <select data-toggle="select2" title="Destination Type">
                                                                    <option value=""></option>
                                                                    <option value="AF"></option>
                                                                    <option value="AL"></option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3">
                                                                <label for="voicemail-option-destination" class="form-label">Destination</label>
                                                                <input class="form-control" type="text" placeholder="" id="voicemail-option-destination" />
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="mb-3">
                                                                <label class="form-label">Order</label>
                                                                <select data-toggle="select2" title="Order">
                                                                    <option value=""></option>
                                                                    <option value="AF"></option>
                                                                    <option value="AL"></option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label for="voicemail-option-description" class="form-label">Description</label>
                                                                <input class="form-control" type="email" placeholder="" id="voicemail-option-description" />
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif
                                                </div> <!-- end row-->
                                            </div>
                                        </div>
                                        <!-- End Voicemail Content-->
                                    </div>
                                    @else
                                        <input type="hidden" name="call_timeout" value="25">
                                    @endif

                                    <div class="tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                                        <!-- Settings Content-->
                                        <div class="tab-pane show active">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mt-2 mb-3">Settings</h4>

                                                    <div class="row">
                                                        @if (userCheckPermission('extension_domain'))
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Domain</label>
                                                                <select data-toggle="select2" title="Domain" id="domain_uuid" name="domain_uuid">
                                                                    @foreach (Session::get("domains") as $domain))
                                                                    <option value="{{ $domain->domain_uuid }}"
                                                                        @if($domain->domain_uuid == $extension->domain_uuid)
                                                                        selected
                                                                        @endif>
                                                                        {{ $domain->domain_name }}
                                                                    </option>
                                                                    @endforeach
                                                                </select>
                                                                <div class="text-danger domain_uuid_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @else
                                                            <input type="hidden" name="domain_uuid" value="{{ Session::get('domain_uuid') }}">
                                                        @endif

                                                        @if (userCheckPermission('extension_user_context'))
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="context" class="form-label">Context <span class="text-danger">*</span></label>
                                                                <input class="form-control" type="text" placeholder="" id="user_context"
                                                                    name="user_context" value="{{ $extension->user_context}}"/>
                                                                <div class="text-danger user_context_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @else
                                                            <input type="hidden" name="user_context" value="{{ Session::get('domain_name') }}">
                                                        @endif
                                                    </div> <!-- end row -->

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="users-select" class="form-label">Users</label>
                                                                <!-- Multiple Select -->
                                                                <select class="select2 form-control select2-multiple form-select form-select-sm" data-toggle="select2" multiple="multiple" data-placeholder="Choose ..."
                                                                    id="users-select" @if (!userCheckPermission('extension_user_edit')) disabled @endif name="users[]">

                                                                        @foreach ($domain_users as $domain_user)
                                                                            <option value="{{ $domain_user->user_uuid }}"
                                                                                @if(isset($extension_users) && $extension_users->contains($domain_user))
                                                                                    selected
                                                                                @endif>
                                                                                @if (isset($domain_user->user_adv_fields->first_name) || isset($domain_user->user_adv_fields->last_name))
                                                                                    @if ($domain_user->user_adv_fields->first_name)
                                                                                        {{ $domain_user->user_adv_fields->first_name }}
                                                                                    @endif
                                                                                    @if ($domain_user->user_adv_fields->last_name)
                                                                                        {{ $domain_user->user_adv_fields->last_name }}
                                                                                    @endif
                                                                                @elseif ($domain_user->description)
                                                                                    {{ $domain_user->description }}
                                                                                @else
                                                                                    {{ $domain_user->username }}
                                                                                @endif
                                                                            </option>
                                                                        @endforeach
                                                                </select>
                                                                <div class="text-danger users_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->

                                                    <div class="row">
                                                        @if (userCheckPermission('number_alias'))
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="number-alias" class="form-label">Number Alias</label>
                                                                <input class="form-control" type="text" placeholder="" id="number_alias"
                                                                name="number_alias" value="{{ $extension->number_alias}}"/>
                                                                <span class="help-block"><small>If the extension is numeric then number alias is optional.</small></span>
                                                                <div class="text-danger number_alias_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @endif

                                                        @if (userCheckPermission('extension_accountcode'))
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="accountcode" class="form-label">Account Code</label>
                                                                <input class="form-control" type="text" placeholder="" id="accountcode"
                                                                    name="accountcode" value="{{ $extension->accountcode}}"/>
                                                                <span class="help-block"><small>Enter the account code here.</small></span>
                                                                <div class="text-danger accountcode_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @else
                                                            <input type="hidden" name="accountcode" value="{{ Session::get('domain_name') }}">
                                                        @endif
                                                    </div> <!-- end row -->

                                                    <div class="row">
                                                        @if (userCheckPermission('extension_max_registrations'))
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="max_registrations" class="form-label">Total allowed registrations</label>
                                                                <input class="form-control" type="text" placeholder="" id="max_registrations"
                                                                    name="max_registrations"  value="{{ $extension->max_registrations}}"/>
                                                                <span class="help-block"><small>Enter the maximum registration allowed for this user</small></span>
                                                                <div class="text-danger error-text max_registrations_err error_message"></div>
                                                            </div>

                                                        </div>
                                                        @endif

                                                        @if (userCheckPermission('extension_toll'))
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="toll_allow" class="form-label">Toll Allow</label>
                                                                <input class="form-control" type="text" placeholder="" id="toll_allow"
                                                                    name="toll_allow" value="{{ $extension->toll_allow}}"/>
                                                                <span class="help-block"><small>Enter the toll allow value here. (Examples: domestic,international,local)</small></span>
                                                                <div class="text-danger toll_allow_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </div> <!-- end row -->

                                                    @if (userCheckPermission('extension_limit'))
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="limit_destination" class="form-label">Limit Destination</label>
                                                                <input class="form-control" type="text" placeholder="" id="limit_destination"
                                                                    name="limit_destination" value="{{ $extension->limit_destination}}"/>
                                                                <span class="help-block"><small>Enter the destination to send the calls when the max number of outgoing calls has been reached.</small></span>
                                                                <div class="text-danger limit_destination_err error_message"></div>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="limit_max" class="form-label">Total allowed outbound calls</label>
                                                                <input class="form-control" type="text" placeholder="" id="limit_max"
                                                                    name="limit_max" value="{{ $extension->limit_max}}"/>
                                                                <span class="help-block"><small>Enter the max number of outgoing calls for this user.</small></span>
                                                                <div class="text-danger limit_max_err error_message"></div>
                                                            </div>
                                                        </div>

                                                    </div> <!-- end row -->
                                                    @else
                                                        <input type="hidden" name="limit_destination" value="!USER_BUSY">
                                                        <input type="hidden" name="limit_max" value="5">
                                                    @endif


                                                    <div class="row">
                                                        @if (userCheckPermission('extension_call_group'))
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label for="call_group" class="form-label">Call Group</label>
                                                                <input class="form-control" type="text" placeholder="" id="call_group"
                                                                    name="call_group" value="{{ $extension->call_group}}"/>
                                                                <span class="help-block"><small>Enter the user call group here. Groups available by default: sales, support, billing.</small></span>
                                                                <div class="text-danger call_group_err error_message"></div>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </div> <!-- end row -->

                                                    @if (userCheckPermission('extension_call_screen'))
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Enable call screening</label>
                                                                <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                    data-bs-content="You can use Call Screen to find out who’s calling and why before you pick up a call. ">
                                                                    <i class="uil uil-info-circle"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="mb-3 text-sm-end">
                                                                <input type="hidden" name="call_screen_enabled" value="false">
                                                                <input type="checkbox" id="call_screen_enabled" name="call_screen_enabled"
                                                                @if ($extension->call_screen_enabled == "true") checked @endif
                                                                data-switch="primary"/>
                                                                <label for="call_screen_enabled" data-on-label="On" data-off-label="Off"></label>
                                                                <div class="text-danger call_screen_enabled_err error_message"></div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif

                                                    @if (userCheckPermission('extension_user_record'))
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Call recording</label>
                                                                <select data-toggle="select2" title="Call recording" name="user_record">
                                                                    <option value="">Disabled</option>
                                                                    <option value="all"
                                                                        @if ($extension->user_record == 'all')
                                                                        selected
                                                                        @endif>
                                                                        All
                                                                    </option>
                                                                    <option value="local"
                                                                        @if ($extension->user_record == 'local')
                                                                        selected
                                                                        @endif>
                                                                        Local
                                                                    </option>
                                                                    <option value="inbound"
                                                                        @if ($extension->user_record == 'inbound')
                                                                        selected
                                                                        @endif>
                                                                        Inbound
                                                                    </option>
                                                                    <option value="outbound"
                                                                        @if ($extension->user_record == 'outbound')
                                                                        selected
                                                                        @endif>
                                                                        Outbound
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif

                                                    @if (userCheckPermission('extension_hold_music'))
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Select custom music on hold</label>
                                                                <select data-toggle="select2" title="Select custom music on hold" name="hold_music">
                                                                    <option value="">Not selected</option>
                                                                    @if (!$moh->isEmpty())
                                                                    <optgroup label="Music on Hold">
                                                                        @foreach ($moh as $music)
                                                                        <option value="local_stream://{{ $music->music_on_hold_name }}"
                                                                            @if("local_stream://" . $music->music_on_hold_name == $extension->hold_music)
                                                                            selected
                                                                            @endif>
                                                                            {{ $music->music_on_hold_name }}
                                                                        </option>
                                                                        @endforeach
                                                                    </optgroup>
                                                                    @endif

                                                                    @if (!$recordings->isEmpty())
                                                                    <optgroup label="Recordings">
                                                                        @foreach ($recordings as $recording)
                                                                        <option value="{{ getDefaultSetting('switch','recordings'). "/" . Session::get('domain_name') . "/" . $recording->recording_filename }}"
                                                                            @if(getDefaultSetting('switch','recordings'). "/" . Session::get('domain_name') . "/" . $recording->recording_filename == $extension->hold_music)
                                                                            selected
                                                                            @endif>
                                                                            {{ $recording->recording_name }}
                                                                        </option>
                                                                        @endforeach
                                                                    </optgroup>
                                                                    @endif
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end row -->
                                                    @endif

                                                    @if (userCheckPermission('extension_advanced'))
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="auth_acl" class="form-label">Auth ACL</label>
                                                                    <input class="form-control" type="text" placeholder="" id="auth_acl"
                                                                        name="auth_acl" value="{{ $extension->auth_acl}}"/>
                                                                    <span class="help-block"><small>Enter the Auth ACL here.</small></span>
                                                                    <div class="text-danger auth_acl_err error_message"></div>
                                                                </div>
                                                            </div>
                                                            @if (userCheckPermission('extension_cidr'))
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="cidr" class="form-label">CIDR</label>
                                                                    <input class="form-control" type="text" placeholder="" id="cidr"
                                                                        name="cidr" value="{{ $extension->cidr}}"/>
                                                                    <span class="help-block"><small>Enter allowed address/ranges in CIDR notation (comma separated).</small></span>
                                                                    <div class="text-danger cidr_err error_message"></div>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        </div> <!-- end row -->

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">SIP Force Contact</label>
                                                                    <select data-toggle="select2" title="SIP Force Contact" name="sip_force_contact">
                                                                        <option value="">Disabled</option>
                                                                        <option value="NDLB-connectile-dysfunction"
                                                                            @if ($extension->sip_force_contact == 'NDLB-connectile-dysfunction')
                                                                            selected
                                                                            @endif>
                                                                            Rewrite Contact IP and Port
                                                                        </option>
                                                                        <option value="NDLB-connectile-dysfunction-2.0"
                                                                            @if ($extension->sip_force_contact == 'NDLB-connectile-dysfunction-2.0')
                                                                            selected
                                                                            @endif>
                                                                            Rewrite Contact IP and Port 2.0
                                                                        </option>
                                                                        <option value="NDLB-tls-connectile-dysfunction"
                                                                            @if ($extension->sip_force_contact == 'NDLB-tls-connectile-dysfunction')
                                                                            selected
                                                                            @endif>
                                                                            Rewrite TLS Contact Port
                                                                        </option>
                                                                    </select>
                                                                    <div class="text-danger sip_force_contact_err error_message"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="sip_force_expires" class="form-label">SIP Force Expires</label>
                                                                    <input class="form-control" type="text" placeholder="" id="sip_force_expires"
                                                                        name="sip_force_expires" value="{{ $extension->sip_force_expires}}"/>
                                                                    <span class="help-block"><small>To prevent stale registrations SIP Force expires can override the client expire.</small></span>
                                                                    <div class="text-danger sip_force_expires_err error_message"></div>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="mwi_account" class="form-label">Monitor MWI Account</label>
                                                                    <input class="form-control" type="text" placeholder="" id="mwi_account"
                                                                        name="mwi_account" value="{{ $extension->mwi_account}}"/>
                                                                    <span class="help-block"><small>MWI Account with user@domain of the voicemail to monitor.</small></span>
                                                                    <div class="text-danger mwi_account_err error_message"></div>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">SIP Bypass Media</label>
                                                                    <select data-toggle="select2" title="SIP Bypass Media" name="sip_bypass_media">
                                                                        <option value="">Disabled</option>
                                                                        <option value="bypass-media"
                                                                            @if ($extension->sip_bypass_media == 'bypass-media')
                                                                            selected
                                                                            @endif>
                                                                            Bypass Media
                                                                        </option>
                                                                        <option value="bypass-media-after-bridge"
                                                                            @if ($extension->sip_bypass_media == 'bypass-media-after-bridge')
                                                                            selected
                                                                            @endif>
                                                                            Bypass Media After Bridge
                                                                        </option>
                                                                        <option value="proxy-media"
                                                                            @if ($extension->sip_bypass_media == 'proxy-media')
                                                                            selected
                                                                            @endif>
                                                                            Proxy Media
                                                                        </option>
                                                                    </select>
                                                                    <div class="text-danger sip_bypass_media_err error_message"></div>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->

                                                        @if (userCheckPermission('extension_absolute_codec_string'))
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="absolute_codec_string" class="form-label">Absolute Codec String</label>
                                                                    <input class="form-control" type="text" placeholder="" id="absolute_codec_string"
                                                                        name="absolute_codec_string" value="{{ $extension->absolute_codec_string}}"/>
                                                                    <span class="help-block"><small>Absolute Codec String for the extension</small></span>
                                                                    <div class="text-danger absolute_codec_string_err error_message"></div>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->
                                                        @endif

                                                        @if (userCheckPermission('extension_force_ping'))
                                                        <div class="row">
                                                            <div class="col-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Force ping </label>
                                                                    <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                        data-bs-content="Use OPTIONS to detect if extension is reachable">
                                                                        <i class="uil uil-info-circle"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="mb-3 text-sm-end">
                                                                    <input type="hidden" name="force_ping" value="false">
                                                                    <input type="checkbox" id="force_ping" name="force_ping"
                                                                    @if ($extension->force_ping == "true") checked @endif
                                                                    data-switch="primary"/>
                                                                    <label for="force_ping" data-on-label="On" data-off-label="Off"></label>
                                                                    <div class="text-danger force_ping_err error_message"></div>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->
                                                        @endif

                                                        @if (userCheckPermission('extension_dial_string'))
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="dial_string" class="form-label">Dial String</label>
                                                                    <input class="form-control" type="text" placeholder="" id="dial_string"
                                                                        name="dial_string" value="{{ $extension->dial_string}}"/>
                                                                    <span class="help-block"><small>Location of the endpoint.</small></span>
                                                                    <div class="text-danger dial_string_err error_message"></div>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->
                                                        @endif

                                                        @if ($extension->exists)
                                                        <div class="row">
                                                            <div class="col-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Exclude this user from the App Stale Users report</label>
                                                                    <a href="javascript://"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                                        data-bs-content="If enabled, this user will not appear in the App Stale Users report, preventing them from being flagged as inactive.">
                                                                        <i class="uil uil-info-circle"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="mb-3 text-sm-end">
                                                                    <input type="hidden" name="exclude_from_ringotel_stale_users" value="false">
                                                                    <input type="checkbox" id="exclude_from_ringotel_stale_users" name="exclude_from_ringotel_stale_users"
                                                                    @if ($extension->mobile_app && $extension->mobile_app->exclude_from_stale_report) checked @endif
                                                                    data-switch="primary"/>
                                                                    <label for="exclude_from_ringotel_stale_users" data-on-label="On" data-off-label="Off"></label>
                                                                    <div class="text-danger exclude_from_ringotel_stale_users_err error_message"></div>
                                                                </div>
                                                            </div>
                                                        </div> <!-- end row -->
                                                        @endif
                                                    @endif

                                                </div>

                                            </div> <!-- end row-->

                                        </div>
                                        <!-- End Settings Content-->
                                    </div>

                                    @if ($extension->exists)
                                        <div class="tab-pane fade" id="v-pills-device" role="tabpanel" aria-labelledby="v-pills-device-tab">
                                            <!-- Devices Content-->
                                            @include ('layouts.extensions.device_tab')
                                        </div>
                                    @endif

                                    <div class="tab-pane fade" id="v-pills-callforward" role="tabpanel" aria-labelledby="v-pills-callforward-tab">
                                        <!-- Settings Content-->
                                        <div class="tab-pane show active">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mb-2 mt-0">Forward all calls</h4>
                                                    <p class="text-muted mb-2">Ensure customers and colleagues can reach you, regardless of your physical location. Automatically redirect all incoming calls to another phone number of your choice.</p>
                                                    <div class="row">
                                                        <div class="mb-2">
                                                            <input type="hidden" name="forward_all_enabled" value="false">
                                                            <input type="checkbox" id="forward_all_enabled" value="true" name="forward_all_enabled" data-option="forward_all" class="forward_checkbox"
                                                                   @if ($extension->isForwardAllEnabled()) checked @endif
                                                                   data-switch="primary"/>
                                                            <label for="forward_all_enabled" data-on-label="On" data-off-label="Off"></label>
                                                            <div class="text-danger forward_all_enabled_err error_message"></div>
                                                        </div>
                                                    </div>
                                                    <div id="forward_all_phone_number" class="row @if(!$extension->isForwardAllEnabled()) d-none @endif">
                                                        <div class="col-md-12">
                                                            <p>
                                                                @include('layouts.partials.destinationSelector', [
                                                                                    'type' => 'forward',
                                                                                    'id' => 'all',
                                                                                    'value' => $extension->forward_all_destination,
                                                                                    'extensions' => $extensions
                                                                ])
                                                                <div class="text-danger forward_all_destination_err error_message"></div>
                                                            </p>
{{--
                                                            @if(empty($extension->forward_all_destination))
                                                                <span id="forward_all_label">No destination selected.</span>
                                                            @else
                                                                <span id="forward_all_label">Selected destination: {{ $extension->forward_all_destination }}</span>
                                                            @endif
                                                                <span class="mx-2"><a href="javascript:openForwardDestinationModal('Edit destination to forward all calls', 'all');">Edit</a></span>
                                                            @if(!empty($extension->forward_all_destination))
                                                                <span class="clear-dest ml-2"><a href="javascript:confirmClearDestinationAction('{{ route('extensions.clear-callforward-destination', ['extension' => $extension->extension_uuid, 'type' => 'all']) }}', 'all');">Clear destination</a></span>
                                                            @endif
                                                            </p>

                                                            <input type="hidden" id="forward_all_destination" name="forward_all_destination" value="{{ $extension->forward_all_destination }}" />
--}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mb-2 mt-0">When user is busy</h4>
                                                    <p class="text-muted mb-2">Automatically redirect incoming calls to a different phone number if the phone is busy or Do Not Disturb is enabled.</p>
                                                    <div class="row">
                                                        <div class="mb-2">
                                                            <input type="hidden" name="forward_busy_enabled" value="false">
                                                            <input type="checkbox" id="forward_busy_enabled" value="true" name="forward_busy_enabled" data-option="forward_busy" class="forward_checkbox"
                                                                   @if ($extension->isForwardBusyEnabled()) checked @endif
                                                                   data-switch="primary"/>
                                                            <label for="forward_busy_enabled" data-on-label="On" data-off-label="Off"></label>
                                                            <div class="text-danger forward_busy_enabled_err error_message"></div>
                                                        </div>
                                                    </div>
                                                    <div id="forward_busy_phone_number" class="row @if(!$extension->isForwardBusyEnabled()) d-none @endif">
                                                        <div class="col-md-12">
                                                            <p>
                                                                @include('layouts.partials.destinationSelector', [
                                                                                    'type' => 'forward',
                                                                                    'id' => 'busy',
                                                                                    'value' => $extension->forward_busy_destination,
                                                                                    'extensions' => $extensions
                                                                ])
                                                                <div class="text-danger forward_busy_destination_err error_message"></div>
                                                            </p>
                                                            {{--
                                                            <p>
                                                            @if(empty($extension->forward_busy_destination))
                                                                <span id="forward_busy_label">No destination selected.</span>
                                                            @else
                                                                <span id="forward_busy_label">Selected destination: {{ $extension->forward_busy_destination }}</span>
                                                            @endif
                                                                <span class="mx-2"><a href="javascript:openForwardDestinationModal('Edit destination to forward call when user is busy', 'busy');">Edit</a></span>
                                                            @if(!empty($extension->forward_busy_destination))
                                                                <span class="clear-dest ml-2"><a href="javascript:confirmClearDestinationAction('{{ route('extensions.clear-callforward-destination', ['extension' => $extension->extension_uuid, 'type' => 'busy']) }}', 'busy');">Clear destination</a></span>
                                                            @endif
                                                            </p>
                                                            <div class="text-danger forward_busy_destination_err error_message"></div>
                                                            <input type="hidden" id="forward_busy_destination" name="forward_busy_destination" value="{{ $extension->forward_busy_destination }}" />
                                                            --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mb-2 mt-0">When user does not answer the call</h4>
                                                    <p class="text-muted mb-2">Automatically redirect incoming calls to a different phone number if no answer.</p>
                                                    <div class="row">
                                                        <div class="mb-2">
                                                            <input type="hidden" name="forward_no_answer_enabled" value="false">
                                                            <input type="checkbox" id="forward_no_answer_enabled" value="true" name="forward_no_answer_enabled" data-option="forward_no_answer" class="forward_checkbox"
                                                                   @if ($extension->isForwardNoAnswerEnabled()) checked @endif
                                                                   data-switch="primary"/>
                                                            <label for="forward_no_answer_enabled" data-on-label="On" data-off-label="Off"></label>
                                                            <div class="text-danger forward_no_answer_enabled_err error_message"></div>
                                                        </div>
                                                    </div>
                                                    <div id="forward_no_answer_phone_number" class="row @if(!$extension->isForwardNoAnswerEnabled()) d-none @endif">
                                                        <div class="col-md-12">
                                                            <p>
                                                                @include('layouts.partials.destinationSelector', [
                                                                                    'type' => 'forward',
                                                                                    'id' => 'no_answer',
                                                                                    'value' => $extension->forward_no_answer_destination,
                                                                                    'extensions' => $extensions
                                                                ])
                                                                <div class="text-danger forward_no_answer_destination_err error_message"></div>
                                                            </p>
                                                            {{--
                                                            <p>

                                                            @if(empty($extension->forward_no_answer_destination))
                                                                <span id="forward_no_answer_label">No destination selected.</span>
                                                            @else
                                                                <span id="forward_no_answer_label">Selected destination: {{ $extension->forward_no_answer_destination }}</span>
                                                            @endif
                                                                <span class="mx-2"><a href="javascript:openForwardDestinationModal('Edit destination to forward call when user does not answer', 'no_answer');">Edit</a></span>
                                                            @if(!empty($extension->forward_no_answer_destination))
                                                                <span class="clear-dest ml-2"><a href="javascript:confirmClearDestinationAction('{{ route('extensions.clear-callforward-destination', ['extension' => $extension->extension_uuid, 'type' => 'no_answer']) }}', 'no_answer');">Clear destination</a></span>
                                                            @endif
                                                            </p>
                                                            <div class="text-danger forward_no_answer_destination_err error_message"></div>
                                                            <input type="hidden" id="forward_no_answer_destination" name="forward_no_answer_destination" value="{{ $extension->forward_no_answer_destination }}" />
                                                            --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mb-2 mt-0">When internet connection is down</h4>
                                                    <p class="text-muted mb-2">Automatically redirect incoming calls to a different phone number if no user registered.</p>
                                                    <div class="row">
                                                        <div class="mb-2">
                                                            <input type="hidden" name="forward_user_not_registered_enabled" value="false">
                                                            <input type="checkbox" id="forward_user_not_registered_enabled" value="true" name="forward_user_not_registered_enabled" data-option="forward_user_not_registered" class="forward_checkbox"
                                                                   @if ($extension->isForwardUserNotRegisteredEnabled()) checked @endif
                                                                   data-switch="primary"/>
                                                            <label for="forward_user_not_registered_enabled" data-on-label="On" data-off-label="Off"></label>
                                                            <div class="text-danger forward_user_not_registered_enabled_err error_message"></div>
                                                        </div>
                                                    </div>
                                                    <div id="forward_user_not_registered_phone_number" class="row @if(!$extension->isForwardUserNotRegisteredEnabled()) d-none @endif">
                                                        <div class="col-md-12">
                                                            <p>
                                                            @include('layouts.partials.destinationSelector', [
                                                                                'type' => 'forward',
                                                                                'id' => 'user_not_registered',
                                                                                'value' => $extension->forward_user_not_registered_destination,
                                                                                'extensions' => $extensions
                                                            ])
                                                            <div class="text-danger forward_not_registered_destination_err error_message"></div>
                                                            </p>
                                                            {{--
                                                            <p>
                                                            @if(empty($extension->forward_user_not_registered_destination))
                                                                <span id="forward_user_not_registered_label">No destination selected.</span>
                                                            @else
                                                                <span id="forward_user_not_registered_label">Selected destination: {{ $extension->forward_user_not_registered_destination }}</span>
                                                            @endif
                                                                <span class="mx-2"><a href="javascript:openForwardDestinationModal('Edit destination to forward call when internet connection is down', 'user_not_registered');">Edit</a></span>
                                                            @if(!empty($extension->forward_user_not_registered_destination))
                                                                <span class="clear-dest ml-2"><a href="javascript:confirmClearDestinationAction('{{ route('extensions.clear-callforward-destination', ['extension' => $extension->extension_uuid, 'type' => 'user_not_registered']) }}', 'user_not_registered');">Clear destination</a></span>
                                                            @endif
                                                            </p>
                                                            <div class="text-danger forward_user_not_registered_destination_err error_message"></div>
                                                            <input type="hidden" id="forward_user_not_registered_destination" name="forward_user_not_registered_destination" value="{{ $extension->forward_user_not_registered_destination }}" />
                                                            --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mb-2 mt-0">Sequential ring</h4>
                                                    <p class="text-muted mb-2">List and determine the order of up to 10 phone numbers or SIP URI addresses you would like to ring after your primary phone when you receive a call.</p>
                                                    <div class="row">
                                                        <div class="mb-2">
                                                            <input type="hidden" name="follow_me_enabled" value="false">
                                                            <input type="checkbox" id="follow_me_enabled" value="true" name="follow_me_enabled" data-option="follow_me" class="forward_checkbox"
                                                                   @if ($extension->isFollowMeEnabled()) checked @endif
                                                                   data-switch="primary"/>
                                                            <label for="follow_me_enabled" data-on-label="On" data-off-label="Off"></label>
                                                            <div class="text-danger follow_me_enabled_err error_message"></div>
                                                        </div>
                                                    </div>
                                                    <div id="follow_me_phone_number" class="row @if(!$extension->isFollowMeEnabled()) d-none @endif">
                                                        <div class="col-md-12">
                                                            <div class="row mb-3">
                                                                <div class="col-5">
                                                                    <label class="form-label" style="padding-top: 10px;">Ring my main phone first for </label>
                                                                </div>
                                                                <div class="col-2">
                                                                    <select data-toggle="select2" title="Ring my main phone first" name="follow_me_ring_my_phone_timeout">
                                                                        <option value="">Disabled</option>
                                                                        @for ($i = 1; $i < 20; $i++)
                                                                            <option value="{{ $i * 5 }}" @if ($follow_me_ring_my_phone_timeout == $i*5) selected @endif>
                                                                                {{ $i }} @if ($i >1 ) Rings @else Ring @endif - {{ $i * 5 }} Sec
                                                                            </option>
                                                                        @endfor
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-5">
                                                                    <label class="form-label" style="padding-top: 2px;">Continue ringing sequence if main number is busy</label>
                                                                </div>
                                                                <div class="col-2">
                                                                    <input type="hidden" name="follow_me_ignore_busy" value="false">
                                                                    <input type="checkbox" id="follow_me_ignore_busy" name="follow_me_ignore_busy" value="true"
                                                                           @if ($extension->getFollowMe() && $extension->getFollowMe()->follow_me_ignore_busy == "false") checked @endif
                                                                           data-switch="primary">
                                                                    <label for="follow_me_ignore_busy" data-on-label="On" data-off-label="Off"></label>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <h4 class="mt-2">Sequential order</h4>
                                                                <p class="text-muted mb-2">You can drag-n-drop lines to adjust current sequential.</p>
                                                                <table class="table table-centered table-responsive table-sm mb-0 sequential-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th style="width: 20px;">Order</th>
                                                                            <th>Destination</th>
                                                                            <th style="width: 150px">Delay</th>
                                                                            <th style="width: 150px">Number of rings</th>
                                                                            <th style="width: 130px;">Answer confirmation required</th>
                                                                            <th>Action</th>
                                                                        </tr>
                                                                    </thead>
                                                                    @php $b = 0 @endphp
                                                                    <tbody id="destination_sortable">
                                                                    @foreach($follow_me_destinations as $destination)
                                                                        <tr id="row{{$destination->follow_me_destination_uuid}}">
                                                                            @php $b++ @endphp
                                                                            <td class="drag-handler"><i class="mdi mdi-drag"></i> <span>{{ $b }}</span></td>
                                                                            <td>
                                                                                @include('layouts.partials.destinationSelector', [
                                                                                    'type' => 'follow_me_destinations',
                                                                                    'id' => $destination->follow_me_destination_uuid,
                                                                                    'value' => $destination->follow_me_destination,
                                                                                    'extensions' => $extensions
                                                                                ])
                                                                            </td>
                                                                            <td>
                                                                                <select id="destination_delay_{{$destination->follow_me_destination_uuid}}" name="follow_me_destinations[{{$destination->follow_me_destination_uuid}}][delay]">
                                                                                    @for ($i = 0; $i < 20; $i++)
                                                                                        <option value="{{ $i * 5 }}" @if ($destination->follow_me_delay == $i*5) selected @endif>
                                                                                            {{ $i }} @if ($i >1 ) Rings @else Ring @endif - {{ $i * 5 }} Sec
                                                                                        </option>
                                                                                    @endfor
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <select id="destination_timeout_{{$destination->follow_me_destination_uuid}}" name="follow_me_destinations[{{$destination->follow_me_destination_uuid}}][timeout]">
                                                                                    @for ($i = 1; $i < 21; $i++)
                                                                                        <option value="{{ $i * 5 }}" @if ($destination->follow_me_timeout == $i*5) selected @endif>
                                                                                            {{ $i }} @if ($i >1 ) Rings @else Ring @endif - {{ $i * 5 }} Sec
                                                                                        </option>
                                                                                    @endfor
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <input type="hidden" name="follow_me_destinations[{{$destination->follow_me_destination_uuid}}][prompt]" value="false">
                                                                                <input type="checkbox" id="destination_prompt_{{$destination->follow_me_destination_uuid}}" value="true" name="follow_me_destinations[{{$destination->follow_me_destination_uuid}}][prompt]"
                                                                                       @if ($destination->follow_me_prompt == "1") checked @endif
                                                                                       data-switch="primary"/>
                                                                                <label for="destination_prompt_{{$destination->follow_me_destination_uuid}}" data-on-label="On" data-off-label="Off"></label>
                                                                            </td>
                                                                            <td>
                                                                                <div id="tooltip-container-actions">
                                                                                    <a href="javascript:confirmDeleteDestinationAction('row{{$destination->follow_me_destination_uuid}}');" class="action-icon">
                                                                                        <i class="mdi mdi-delete" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
                                                                                    </a>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                    </tbody>
                                                                </table>
                                                                <div id="addDestinationBar" class="my-1" @if($extension->getFollowMeDestinations()->count() >= 10) style="display: none;" @endif>
                                                                    <a href="javascript:addDestinationAction(this);" class="btn btn-success">
                                                                        <i class="mdi mdi-plus" data-bs-container="#addDestinationBar" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Add destination"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4 class="mb-2 mt-0">Do not disturb</h4>
                                                    <p class="text-muted mb-2">Avoid calls to the extension.</p>
                                                    @if ($extension->suspended)
                                                        <p class="text-danger mb-2">This option is unavailable because the extension is suspended.</p>
                                                    @endif
                                                    
                                                    <div class="row">
                                                        <div class="mb-2">
                                                            <input type="hidden" name="do_not_disturb" value="false">
                                                            <input type="checkbox" id="do_not_disturb" value="true" name="do_not_disturb"
                                                                   @if ($extension->do_not_disturb == "true") checked @endif
                                                                   @if ($extension->suspended) disabled @endif
                                                                   data-switch="danger"/>
                                                            <label for="do_not_disturb" data-on-label="On" data-off-label="Off"></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Settings Content-->
                                    </div>
                                </div> <!-- end tab-content-->
                            </div> <!-- end col-->
                    </div>
                    <!-- end row-->
                    </form>

                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>
    <!-- end row-->

</div> <!-- container -->

{{-- Modal --}}
<div class="modal " id="loader" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            {{-- <div class="modal-header">
                <h4 class="modal-title" id="myCenterModalLabel">Center modal</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div> --}}
            <div class="modal-body text-center">
                <div class="spinner-grow text-secondary" role="status"></div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center">
                    <i class="uil uil-times-circle h1 text-danger"></i>
                    <h3 class="mt-3">Are you sure?</h3>
                    <p class="mt-3">Do you really want to delete this? This process cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary confirm-delete-btn" data-href="">Delete</button>
            </div>
        </div>
    </div>
</div>

@if($extension->exists)
<div class="modal fade" id="createDeviceModal" role="dialog" aria-labelledby="createDeviceModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDeviceModalLabel">Create New Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('layouts.devices.form', [
                    'action' => route('extensions.store-device', $extension->extension_uuid),
                    'device' => false,
                    'extensions' => false,
                    'extension_uuid' => $extension->extension_uuid,
                    'vendors' => $vendors,
                    'profiles' => $profiles
                ])
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        .input-group > .select2-container {
            width: auto !important;
            flex: 1 1 auto;
        }
        .select2-container--open {
            z-index:10000;
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
            width: 75px;
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
<script  >
    document.addEventListener('DOMContentLoaded', function() {
        // $("#template-select").select2({
        //     dropdownParent: $("#createDeviceModal")
        // });

        $('a[data-bs-toggle="pill"]').on('show.bs.tab', function(e) {
            localStorage.setItem('activeTab', $(e.target).attr('href'));
        });

        var activeTab = localStorage.getItem('activeTab');
        if(activeTab){
            $('#extensionNavPills a[href="' + activeTab + '"]').tab('show');
        }

        applyDestinationSelect2()

        $('#submitFormButton').on('click', function(e) {
            e.preventDefault();
            $('.loading').show();

            //Reset error messages
            $('.error_message').text("");

            var url = $('#extensionForm').attr('action');

            $.ajax({
                type : "POST",
                url : url,
                cache: false,
                data : $('#extensionForm').serialize(),
            })
            .done(function(response) {
                // console.log(response);
                // $('.loading').hide();

                if (response.error){
                    $('.loading').hide();
                    printErrorMsg(response.error);

                } else {
                    $.NotificationApp.send("Success",response.message,"top-right","#10c469","success");
                    if(response.redirect_url){
                        window.location=response.redirect_url;
                    } else {
                        $('.loading').hide();
                        setTimeout(function() {
                            window.location.reload();
                            $('.loading').hide();
                        }, 2000);
                        
                    }

                }
            })
            .fail(function (jqXHR, testStatus, error) {
                // console.log(error);
                $('.loading').hide();
                printErrorMsg(error);
            });
        });

        if($('#extensionNavPills #v-pills-device-tab').hasClass('active')) {
            $('#action-buttons').hide();
        }

        $('#extensionNavPills .nav-link').on('click', function(e) {
            e.preventDefault();
            if($(this).attr('id') == 'v-pills-device-tab') {
                $('#action-buttons').hide();
            } else {
                $('#action-buttons').show();
            }
        });

        $(document).on('click', '.forward_checkbox', function (e) {
            var checkbox = $(this);
            var cname = checkbox.data('option');
            // console.log(cname)
            if(checkbox.is(':checked')) {
                $('#'+cname+'_phone_number').removeClass('d-none');
            } else {
                $('#'+cname+'_phone_number').addClass('d-none');
                $('#'+cname+'_phone_number').find('.mx-1').find('select').val('internal');
                $('#'+cname+'_phone_number').find('.mx-1').find('select').trigger('change');
            }
        });

        $(document).on('click', '.save-device-btn', function(e){
            e.preventDefault();

            var btn = $(this);
            var form = btn.closest('form');
            var method = 'POST'
            var action = form.attr('action')

            if(form.find('#device_address').attr('readonly') !== undefined) {
                method = 'PUT'
                action = '{{route('extensions.update-device', ['extension' => ':extension', 'device' => ':device'])}}'.replace(':extension', form.find('#extension_uuid').val()).replace(':device', form.find('#device_uuid').val())
            }
//
            $.ajax({
                url: action,
                type: method,
                data: form.serialize(),
                dataType: 'json',
                beforeSend: function() {
                    //Reset error messages
                    form.find('.error').text('');

                    $('.error_message').text("");
                    $('.btn').attr('disabled', true);
                    $('.loading').show();
                },
                complete: function (xhr,status) {
                    $('.btn').attr('disabled', false);
                    $('.loading').hide();
                },
                success: function(result) {
                    form[0].reset();
                    $('#createDeviceModal').modal('hide');
                    /*$('#device-select').append(
                        $('<option></option>').val(result.device.device_uuid).html(result.device.device_address + ' - ' + result.device.device_template)
                    );*/
                    $.NotificationApp.send("Success",result.message,"top-right","#10c469","success");
                    window.location.reload();
                },
                error: function(error) {
                    $('.loading').hide();
                    $('.btn').attr('disabled', false);
                    if(error.status == 422){
                        if(error.responseJSON.errors) {
                            $.each( error.responseJSON.errors, function( key, value ) {
                                if (value != '') {
                                    form.find('#'+key+'_error').text(value);
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
            });
        });

        $('#createDeviceModal').on('shown.bs.modal', function(e){
            if(typeof e.relatedTarget.dataset.href !== 'undefined') {
                $('#createDeviceModalLabel').text('Edit Device')
                // Edit device
                $.ajax({
                    url: e.relatedTarget.dataset.href,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function () {
                        $('.loading').show();
                    },
                    complete: function (xhr, status) {
                        $('.btn').attr('disabled', false);
                        $('.loading').hide();
                    },
                    success: function (result) {
                        $('#device_address').attr('readonly', true).val(result.device.device_address)
                        $('#template-select').val(result.device.device_template).trigger('change')
                        $('#profile-select').val(result.device.device_profile_uuid).trigger('change')
                        $('#device_uuid').val(result.device.device_uuid)
                    }
                });
            } else {
                $('#createDeviceModalLabel').text('Create New Device')
                $('#device_address').attr('readonly', false).val('')
                $('#device_uuid').val('')
                $('#template-select').val('').trigger('change')
                $('#profile-select').val('').trigger('change')
            }
        });

        $('#device-select').select2({
            //sorter: data => data.sort((a, b) => b.text.localeCompare(a.text)),
        });

        $('#profile-select').select2({
            //sorter: data => data.sort((a, b) => b.text.localeCompare(a.text)),
        });

        $('#template-select').select2({
            //sorter: data => data.sort((a, b) => b.text.localeCompare(a.text)),
        });

        @if ($extension->exists)
            $(document).on('click', '.assign-device-btn', function(e){
                e.preventDefault();

                var btn = $(this);
                var data = {
                    'line_number' : btn.closest('.card').find('#line_number').val(),
                    'device_uuid' : btn.closest('.card').find('#device-select').val(),
                    '_token' : $('meta[name="csrf-token"]').attr('content')
                }
                // console.log(btn.closest('.card').find('#device-select'));
                // console.log(data);

                $.ajax({
                    url: "{{route('extensions.assign-device', [$extension->extension_uuid])}}",
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    beforeSend: function() {
                        //Reset error messages
                        btn.closest('.card').find('.error').text('');

                        $('.error_message').text("");
                        $('.btn').attr('disabled', true);
                        $('.loading').show();
                    },
                    complete: function (xhr,status) {
                        $('.btn').attr('disabled', false);
                        $('.loading').hide();
                    },
                    success: function(result) {
                        if(result.status == 'success') {
                            $.NotificationApp.send("Success",result.message,"top-right","#10c469","success");
                            location.reload();
                        } else {
                            $.NotificationApp.send("Warning",result.message,"top-right","#ebb42a","error");
                        }
                        $('.loading').hide();
                    },
                    error: function(error) {
                        $('.loading').hide();
                        $('.btn').attr('disabled', false);
                        if(error.status == 422){
                            if(error.responseJSON.errors) {
                                $.each( error.responseJSON.errors, function( key, value ) {
                                    if (value != '') {
                                        btn.closest('.card').find('#'+key+'_error').text(value);
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
                });
            })
        @endif

        $('#deleteModal').on('shown.bs.modal', function (event) {
            var btn = $(event.relatedTarget)
            var modal = $(this);
            let action = btn.data('href');
            let table = btn.data('table');
            modal.find('.modal-body input').val(action)
            modal.find('.confirm-delete-btn').attr('data-href', action);
            modal.find('.confirm-delete-btn').attr('data-href', action);
        });

        $(document).off('click', '.confirm-delete-btn').on('click', '.confirm-delete-btn', function () {
            var btn = $(this);

            var token = $("meta[name='csrf-token']").attr("content");
            $.ajax({
                url: btn.attr('data-href'),
                type: 'DELETE',
                dataType: 'json',
                data:{'_token' : token},
                beforeSend: function() {
                    $('.btn').attr('disabled', true);
                },
                complete: function (xhr,status) {
                    $('.btn').attr('disabled', false);
                },
                success: function(result) {
                    $.NotificationApp.send("Success",result.message,"top-right","#10c469","success");
                    $('#deleteModal').modal('hide');
                    location.reload();
                },
                error(error) {
                    printErrorMsg(error.responseJSON.message);
                }
            });
        });

        //Extension Page
        // Copy email to voicmemail_email
        $('#voicemail-email-address').change(function() {
            $('#vm-email-address').val($(this).val());
        });

        //Extension Page
        // Copy first name to caller ID first name
        $('#directory_first_name').change(function() {
            $('#callerid-first-name').val($(this).val());
        });

        //Extension Page
        // Copy last name to caller ID last name
        $('#directory_last_name').change(function() {
            $('#callerid-last-name').val($(this).val());
        });

        //Extension Page
        // Copy extension to caller ID extension
        $('#extension').change(function() {
            $('#effective_caller_id_number').val($(this).val());
        });

        // Extension Page
        // Sort Select2 for users
        // $('#users-select').select2({
        //     sorter: data => data.sort((a, b) => a.text.localeCompare(b.text)),
        // });

        // Extension Page
        // Sort Select2 for voicemail destinations
        $('#additional-destinations-select').select2({
            sorter: data => data.sort((a, b) => a.text.localeCompare(b.text)),
        });


        // Upload voicemail unavailable file
        $('#voicemail_unavailable_upload_file_button').on('click', function() {
            $('#voicemail_unavailable_upload_file').trigger('click');
        });

        $('#voicemail_unavailable_upload_file').on('change', function(e) {
            e.preventDefault();

            var formData = new FormData();
            formData.append('voicemail_unavailable_upload_file', $(this)[0].files[0]);
            formData.append('greeting_type', 'unavailable');

            // Add spinner
            $("#voicemail_unavailable_upload_file_button_icon").hide();
            $("#voicemail_unavailable_upload_file_button_spinner").attr("hidden", false);

            var url = $('#voicemail_unavailable_upload_file_button').data('url');

            $.ajax({
                type : "POST",
                url : url,
                data : formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
            })
            .done(function(response) {
                // remove the spinner and change button to default
                $("#voicemail_unavailable_upload_file_button_icon").show();
                $("#voicemail_unavailable_upload_file_button_spinner").attr("hidden", true);

                //Enable play button
                $("#voicemail_unavailable_play_button").attr("disabled", false);
                //Enable download button
                $("#voicemail_unavailable_download_file_button").attr("disabled", false);
                //Enable delete button
                $("#voicemail_unavailable_delete_file_button").attr("disabled", false);

                @if($extension->exists && $extension->voicemail->exists)
                //Update audio file
                $("#voicemail_unavailable_audio_file").attr("src",
                    "{{ route('getVoicemailGreeting', ['voicemail' => $extension->voicemail->voicemail_uuid,'filename' => 'greeting_1.wav'] ) }}"
                );
                @endif

                $("#voicemail_unavailable_audio_file")[0].pause();
                $("#voicemail_unavailable_audio_file")[0].load();

                $("#voicemailUnavailableFilename").html('<strong>' + response.filename + '</strong>');

                if (response.error){
                    $.NotificationApp.send("Warning","There was a error uploading this greeting","top-right","#ff5b5b","error")
                    $("#voicemailUnvaialableGreetingError").text(response.message);
                } else {
                    $.NotificationApp.send("Success","The greeeting has been uploaded successfully","top-right","#10c469","success")
                }
            })
            .fail(function (response){
                //
            });
        });


        // Upload voicemail name file
        $('#voicemail_name_upload_file_button').on('click', function() {
            $('#voicemail_name_upload_file').trigger('click');
        });

        $('#voicemail_name_upload_file').on('change', function(e) {
            e.preventDefault();

            var formData = new FormData();
            formData.append('voicemail_name_upload_file', $(this)[0].files[0]);
            formData.append('greeting_type', 'name');

            // Add spinner
            $("#voicemail_name_upload_file_button_icon").hide();
            $("#voicemail_name_upload_file_button_spinner").attr("hidden", false);

            var url = $('#voicemail_name_upload_file').data('url');

            $.ajax({
                type : "POST",
                url : url,
                data : formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
            })
            .done(function(response) {
                // remove the spinner and change button to default
                $("#voicemail_name_upload_file_button_icon").show();
                $("#voicemail_name_upload_file_button_spinner").attr("hidden", true);

                //Enable play button
                $("#voicemail_name_play_button").attr("disabled", false);
                //Enable download button
                $("#voicemail_name_download_file_button").attr("disabled", false);
                //Enable delete button
                $("#voicemail_name_delete_file_button").attr("disabled", false);

                @if($extension->exists && $extension->voicemail->exists)
                //Update audio file
                $("#voicemail_name_audio_file").attr("src",
                    "{{ route('getVoicemailGreeting', ['voicemail' => $extension->voicemail->voicemail_uuid,'filename' => 'recorded_name.wav'] ) }}"
                );
                @endif

                $("#voicemail_name_audio_file")[0].pause();
                $("#voicemail_name_audio_file")[0].load();

                $("#voicemailNameFilename").html('<strong>' + response.filename + '</strong>');

                if (response.error){
                    $.NotificationApp.send("Warning","There was a error uploading this greeting","top-right","#ff5b5b","error")
                    $("#voicemailNameGreetingError").text(response.message);
                } else {
                    $.NotificationApp.send("Success","The greeting has been uploaded successfully","top-right","#10c469","success")
                }
            })
            .fail(function (response){
                //
            });
        });


        // Delete unavailable voicemail file
        $('#voicemail_unavailable_delete_file_button').on('click', function(e) {
            e.preventDefault();

            var url = $('#voicemail_unavailable_delete_file_button').data('url');

            // Add spinner
            $("#voicemail_unavailable_delete_file_button_icon").hide();
            $("#voicemail_unavailable_delete_file_button_spinner").attr("hidden", false);

            $.ajax({
                type : "GET",
                url : url,
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
            })
            .done(function(response) {
                // remove the spinner and change button to default
                $("#voicemail_unavailable_delete_file_button_icon").show();
                $("#voicemail_unavailable_delete_file_button_spinner").attr("hidden", true);

                //Disable play button
                $("#voicemail_unavailable_play_button").attr("disabled", true);
                //Disable download button
                $("#voicemail_unavailable_download_file_button").attr("disabled", true);
                //Disable delete button
                $("#voicemail_unavailable_delete_file_button").attr("disabled", true);

                $("#voicemailUnavailableFilename").html('<strong>generic greeeting</strong>');

                if (response.error){
                    $.NotificationApp.send("Warning","There was a error deleting this greeting","top-right","#ff5b5b","error")
                    $("#voicemailGreetingError").text(response.message);
                } else {
                    $.NotificationApp.send("Success","The greeeting has been deleted successfully","top-right","#10c469","success")
                }
            })
            .fail(function (response){
                //
            });
        });

        // Delete name voicemail file
        $('#voicemail_name_delete_file_button').on('click', function(e) {
            e.preventDefault();

            var url = $('#voicemail_name_delete_file_button').data('url');

            // Add spinner
            $("#voicemail_name_delete_file_button_icon").hide();
            $("#voicemail_name_delete_file_button_spinner").attr("hidden", false);

            $.ajax({
                type : "GET",
                url : url,
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
            })
            .done(function(response) {
                // remove the spinner and change button to default
                $("#voicemail_name_delete_file_button_icon").show();
                $("#voicemail_name_delete_file_button_spinner").attr("hidden", true);

                //Disable play button
                $("#voicemail_name_play_button").attr("disabled", true);
                //Disable download button
                $("#voicemail_name_download_file_button").attr("disabled", true);
                //Disable delete button
                $("#voicemail_name_delete_file_button").attr("disabled", true);

                $("#voicemailNameFilename").html('<strong>generic greeeting</strong>');

                if (response.error){
                    $.NotificationApp.send("Warning","There was a error deleting this greeting","top-right","#ff5b5b","error")
                    $("#voicemailGreetingError").text(response.message);
                } else {
                    $.NotificationApp.send("Success","The greeeting has been deleted successfully","top-right","#10c469","success")
                }
            })
            .fail(function (response){
                //
            });
        });

        // hide pause button
        $('#voicemail_unavailable_pause_button').hide();
        $('#voicemail_name_pause_button').hide();

        // Play unavailable audio file
        $('#voicemail_unavailable_play_button').click(function(){
            var audioElement = document.getElementById('voicemail_unavailable_audio_file');
            $(this).hide();
            $('#voicemail_unavailable_pause_button').show();
            audioElement.play();
            audioElement.addEventListener('ended', function() {
                $('#voicemail_unavailable_pause_button').hide();
                $('#voicemail_unavailable_play_button').show();
            });
        });

         // Pause unavailable audio file
         $('#voicemail_unavailable_pause_button').click(function(){
            var audioElement = document.getElementById('voicemail_unavailable_audio_file');
            $(this).hide();
            $('#voicemail_unavailable_play_button').show();
            audioElement.pause();
        });

        // Play name audio file
        $('#voicemail_name_play_button').click(function(){
            var audioElement = document.getElementById('voicemail_name_audio_file');
            $(this).hide();
            $('#voicemail_name_pause_button').show();
            audioElement.play();
            audioElement.addEventListener('ended', function() {
                $('#voicemail_name_pause_button').hide();
                $('#voicemail_name_play_button').show();
            });
        });

         // Pause name audio file
         $('#voicemail_name_pause_button').click(function(){
            var audioElement = document.getElementById('voicemail_name_audio_file');
            $(this).hide();
            $('#voicemail_name_play_button').show();
            audioElement.pause();
        });

        $('#voicemail_enabled').change(function() {
            if(this.checked == true){
                //check if voicemail already exists. If not create it
                if($('#voicemail_id').data('uuid') == ""){
                    //Create voicemail box
                    $('.loading').show();

                    var url = '{{ route("voicemails.store") }}';

                    $.ajax({
                        type: 'POST',
                        url: url,
                        cache: false,
                        data: {
                            extension: $('#voicemail_id').data('extensionuuid'),
                            voicemail_id: $('#voicemail_id').val(),
                            voicemail_password: $('#voicemail_id').val(),
                            voicemail_enabled: "true",
                            voicemail_transcription_enabled: "true",
                            voicemail_attach_file: "true",
                            voicemail_tutorial: "true",
                            voicemail_file: "attach",
                            voicemail_local_after_email: "true",
                        },
                    })
                    .done(function(response) {
                        //console.log(response);
                        $('#settingModal').modal('hide');
                        //$('.loading').hide();

                        if (response.error){
                            printErrorMsg(response.error);

                        } else {
                            $.NotificationApp.send("Success",response.message,"top-right","#10c469","success");
                            setTimeout(function (){
                                    window.location.reload();
                                }, 1000);
                        }
                    })
                    .fail(function (response){
                        $('#settingModal').modal('hide');
                        $('.loading').hide();
                        printErrorMsg(response.error);
                    });
                }
            }

        });

        let sortable = new Sortable(document.getElementById('destination_sortable'), {
            delay: 0, // time in milliseconds to define when the sorting should start
            delayOnTouchOnly: false, // only delay if user is using touch
            touchStartThreshold: 0, // px, how many pixels the point should move before cancelling a delayed drag event
            disabled: false, // Disables the sortable if set to true.
            store: null,  // @see Store
            animation: 150,  // ms, animation speed moving items when sorting, `0` — without animation
            easing: "cubic-bezier(1, 0, 0, 1)", // Easing for animation. Defaults to null. See https://easings.net/ for examples.
            handle: ".drag-handler",  // Drag handle selector within list items
            filter: ".ignore-elements",  // Selectors that do not lead to dragging (String or Function)
            preventOnFilter: true, // Call `event.preventDefault()` when triggered `filter`

            ghostClass: "sortable-ghost",  // Class name for the drop placeholder
            chosenClass: "sortable-chosen",  // Class name for the chosen item
            dragClass: "sortable-drag",  // Class name for the dragging item

            swapThreshold: 1, // Threshold of the swap zone
            invertSwap: false, // Will always use inverted swap zone if set to true
            invertedSwapThreshold: 1, // Threshold of the inverted swap zone (will be set to swapThreshold value by default)
            direction: 'vertical', // Direction of Sortable (will be detected automatically if not given)

            forceFallback: false,  // ignore the HTML5 DnD behaviour and force the fallback to kick in

            fallbackClass: "sortable-fallback",  // Class name for the cloned DOM Element when using forceFallback
            fallbackOnBody: false,  // Appends the cloned DOM Element into the Document's Body
            fallbackTolerance: 0, // Specify in pixels how far the mouse should move before it's considered as a drag.

            dragoverBubble: false,
            removeCloneOnHide: true, // Remove the clone element when it is not showing, rather than just hiding it
            emptyInsertThreshold: 5, // px, distance mouse must be from empty sortable to insert drag element into it


            setData: function (/** DataTransfer */dataTransfer, /** HTMLElement*/dragEl) {
                dataTransfer.setData('Text', dragEl.textContent); // `dataTransfer` object of HTML5 DragEvent
            },

            // Element is chosen
            onChoose: function (/**Event*/evt) {
                evt.oldIndex;  // element index within parent
            },

            // Element is unchosen
            onUnchoose: function(/**Event*/evt) {
                // same properties as onEnd
            },

            // Element dragging started
            onStart: function (/**Event*/evt) {
                evt.oldIndex;  // element index within parent
            },

            // Element dragging ended
            onEnd: function (/**Event*/evt) {
                var itemEl = evt.item;  // dragged HTMLElement
                evt.to;    // target list
                evt.from;  // previous list
                evt.oldIndex;  // element's old index within old parent
                evt.newIndex;  // element's new index within new parent
                evt.oldDraggableIndex; // element's old index within old parent, only counting draggable elements
                evt.newDraggableIndex; // element's new index within new parent, only counting draggable elements
                evt.clone // the clone element
                evt.pullMode;  // when item is in another sortable: `"clone"` if cloning, `true` if moving
                updateDestinationOrder()
            },

            // Element is dropped into the list from another list
            onAdd: function (/**Event*/evt) {
                // same properties as onEnd
            },

            // Changed sorting within list
            onUpdate: function (/**Event*/evt) {
                // same properties as onEnd
            },

            // Called by any change to the list (add / update / remove)
            onSort: function (/**Event*/evt) {
                // same properties as onEnd
            },

            // Element is removed from the list into another list
            onRemove: function (/**Event*/evt) {
                // same properties as onEnd
            },

            // Attempt to drag a filtered element
            onFilter: function (/**Event*/evt) {
                var itemEl = evt.item;  // HTMLElement receiving the `mousedown|tapstart` event.
            },

            // Event when you move an item in the list or between lists
            onMove: function (/**Event*/evt, /**Event*/originalEvent) {
                // Example: https://jsbin.com/nawahef/edit?js,output
                evt.dragged; // dragged HTMLElement
                evt.draggedRect; // DOMRect {left, top, right, bottom}
                evt.related; // HTMLElement on which have guided
                evt.relatedRect; // DOMRect
                evt.willInsertAfter; // Boolean that is true if Sortable will insert drag element after target by default
                originalEvent.clientY; // mouse position
                // return false; — for cancel
                // return -1; — insert before target
                // return 1; — insert after target
                // return true; — keep default insertion point based on the direction
                // return void; — keep default insertion point based on the direction
            },

            // Called when dragging element changes position
            onChange: function(/**Event*/evt) {
                evt.newIndex // most likely why this event is used is to get the dragging element's current index
                // same properties as onEnd
            }
        });

        $(`#forward_target_internal_all`).select2();
        $(`#forward_type_all`).select2();

        $(`#forward_target_internal_busy`).select2();
        $(`#forward_type_busy`).select2();

        $(`#forward_target_internal_no_answer`).select2();
        $(`#forward_type_no_answer`).select2();

        $(`#forward_target_internal_user_not_registered`).select2();
        $(`#forward_type_user_not_registered`).select2();
    });

    function showHideAddDestination() {
        if($('#destination_sortable > tr').length > 9) {
            $('#addDestinationBar').hide();
        } else {
            $('#addDestinationBar').show();
        }
    }

    function applyDestinationSelect2() {
        $('#destination_sortable > tr').each(function (i, el) {
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
    }

    function updateDestinationOrder() {
        $('#destination_sortable > tr').each(function (i, el) {
            $(el).find('.drag-handler').find('span').text(i + 1)
        })
    }

    function addDestinationAction(el){
        let wrapper = $(`#destination_sortable > tr`)
        let count = wrapper.length
        let newCount = (count + 1)
        if(newCount > 10) {
            return false;
        }

        let newRow = `
        <tr id="row__NEWROWID__"><td class="drag-handler"><i class="mdi mdi-drag"></i> <span>__NEWROWID__</span></td>
        <td>
        @include('layouts.partials.destinationSelector', [
            'type' => 'follow_me_destinations',
            'id' => '__NEWROWID__',
            'value' => '',
            'extensions' => $extensions
        ])
        </td>
        <td><select id="destination_delay___NEWROWID__" name="follow_me_destinations[newrow__NEWROWID__][delay]">
        @for ($i = 0; $i < 20; $i++) <option value="{{ $i * 5 }}" @if ($i == 0) selected @endif>
        {{ $i }} @if ($i >1 ) Rings @else Ring @endif - {{ $i * 5 }} Sec</option> @endfor </select></td>
        <td><select id="destination_timeout___NEWROWID__" name="follow_me_destinations[newrow__NEWROWID__][timeout]">
        @for ($i = 1; $i < 21; $i++) <option value="{{ $i * 5 }}" @if ($i == 5) selected @endif>
        {{ $i }} @if ($i >1 ) Rings @else Ring @endif - {{ $i * 5 }} Sec</option> @endfor </select></td><td>
        <input type="hidden" name="follow_me_destinations[newrow__NEWROWID__][prompt]" value="false">
        <input type="checkbox" id="destination_prompt___NEWROWID__" value="true" name="follow_me_destinations[newrow__NEWROWID__][prompt]" data-option="follow_me_enabled" class="forward_checkbox" data-switch="primary"/>
        <label for="destination_prompt___NEWROWID__" data-on-label="On" data-off-label="Off"></label>
        </td><td><div id="tooltip-container-actions"><a href="javascript:confirmDeleteDestinationAction('row__NEWROWID__');" class="action-icon">
        <i class="mdi mdi-delete" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
        </a></div></td></tr>`;
        newRow = newRow.replaceAll('__NEWROWID__', Math.random().toString(16).slice(2))

        $('#destination_sortable').append(newRow)

        showHideAddDestination()
        updateDestinationOrder()
        applyDestinationSelect2()
    }

    function confirmDeleteDestinationAction(el){
        if ($(`#${el}`).data('select2')) {
            $(`#${el}`).select2('destroy').hide()
        }
        $(`#${el}`).remove();
        updateDestinationOrder()
        showHideAddDestination()
    }
</script>
@endpush
