@extends('layouts.app', ['page_title' => 'Voicemail'])

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('voicemails.index') }}">Voicemail</a></li>
                            @if ($voicemail->exists)
                                <li class="breadcrumb-item active">Edit Voicemail</li>
                            @else
                                <li class="breadcrumb-item active">Create New Voicemail</li>
                            @endif
                        </ol>
                    </div>
                    @if ($voicemail->exists)
                        <h4 class="page-title">Edit Voicemail ({{ $voicemail->voicemail_id ?? '' }})</h4>
                    @else
                        <h4 class="page-title">Create New Voicemail</h4>
                    @endif

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Body Content-->
                            <div class="row">
                                <div class="col-lg-12">

                                    @if ($voicemail->exists)
                                        <form method="POST" id="voicemail_form"
                                            action="{{ route('voicemails.update', $voicemail) }}">
                                            @method('put')
                                        @else
                                            <form method="POST" id="voicemail_form"
                                                action="{{ route('voicemails.store') }}">
                                    @endif
                                    @csrf

                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <label class="form-label">Voicemail enabled </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="mb-3 text-sm-end">

                                                <input type="hidden" name="voicemail_enabled" value="false">
                                                <input type="checkbox" id="voicemail_enabled" data-switch="primary"
                                                    name="voicemail_enabled" value="true"
                                                    @if (isset($voicemail->voicemail_enabled) && $voicemail->voicemail_enabled == 'true') checked @endif />
                                                <label for="voicemail_enabled" data-on-label="On"
                                                    data-off-label="Off"></label>

                                                <div class="text-danger voicemail_enabled_err error_message"></div>
                                            </div>
                                        </div>
                                    </div> <!-- end row -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="voicemail_id" class="form-label">Voicemail Extension <span
                                                        class="text-danger">*</span></label>
                                                <input class="form-control" type="text"
                                                    value="{{ $voicemail->voicemail_id ?? '' }}"
                                                    placeholder="Enter voicemail extension" id="voicemail_id"
                                                    name="voicemail_id" autocomplete="off" />
                                                <div class="text-danger error_message voicemail_id_err"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="voicemail_password" class="form-label">Set voicemail PIN <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <input type="password" id="voicemail_password" class="form-control"
                                                        placeholder="xxxx"
                                                        value="{{ $voicemail->voicemail_password ?? '' }}"
                                                        name="voicemail_password" autocomplete="off">
                                                    <div class="input-group-text" data-password="false">
                                                        <span class="password-eye"></span>
                                                    </div>
                                                </div>
                                                <div class="text-danger voicemail_password_err error_message"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="alternate_greet_id" class="form-label">Alternate Greet ID
                                                </label>
                                                <a href="#" data-bs-toggle="popover" data-bs-placement="top"
                                                    data-bs-trigger="focus"
                                                    data-bs-content="The parameter allows you to override the default extension or phone number spoken by the system in the voicemail greeting. This controls system greetings that read back a phone number, not user recorded greetings.">
                                                    <i class="uil uil-info-circle"></i>
                                                </a>
                                                <input class="form-control" type="text"
                                                    value="{{ $voicemail->voicemail_alternate_greet_id ?? '' }}"
                                                    placeholder="Enter your Alternate Greet ID"
                                                    id="voicemail_alternate_greet_id" name="voicemail_alternate_greet_id"
                                                    autocomplete="off" />
                                                <div class="text-danger error_message voicemail_alternate_greet_id_err">
                                                </div>
                                            </div>
                                        </div>
                                    </div> <!-- end row -->

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">Notification type</label>
                                                <select data-toggle="select2" title="Notification Type"
                                                    name="voicemail_file">
                                                    <option value="attach"
                                                        @if (isset($voicemail) && $voicemail->voicemail_file == 'attach') selected @endif>
                                                        Email with audio file attachment
                                                    </option>
                                                    <option value="link"
                                                        @if (isset($voicemail) &&
                                                                $voicemail->exists &&
                                                                ($voicemail->voicemail_file == 'link' || $voicemail->voicemail_file == '')) selected @endif>
                                                        Email with download link
                                                    </option>
                                                </select>
                                                <div class="text-danger voicemail_file_err error_message"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="vm-email-address" class="form-label">Email
                                                    Address</span></label>
                                                <input class="form-control" type="email" name="voicemail_mail_to"
                                                    placeholder="Enter email" id="vm-email-address"
                                                    value="{{ $voicemail->voicemail_mail_to ?? '' }}"
                                                    autocomplete="off" />
                                            </div>
                                        </div>
                                    </div> <!-- end row -->

                                    @if (userCheckPermission('voicemail_transcription_enabled'))
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Enable voicemail transcription </label>
                                                    <a href="#" data-bs-toggle="popover" data-bs-placement="top"
                                                        data-bs-trigger="focus"
                                                        data-bs-content="Send a text trancsript. Accuracy may vary based on call quality, accents, vocabulary, etc. ">
                                                        <i class="uil uil-info-circle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="mb-3 text-sm-end">
                                                    <input type="hidden" name="voicemail_transcription_enabled"
                                                        value="false">
                                                    <input type="checkbox" id="voicemail_transcription_enabled"
                                                        data-switch="primary" name="voicemail_transcription_enabled"
                                                        value="true" @if ($voicemail->voicemail_transcription_enabled == 'true') checked @endif />
                                                    <label for="voicemail_transcription_enabled" data-on-label="On"
                                                        data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->
                                    @endif

                                    @if (userCheckPermission('voicemail_local_after_email'))
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Delete voicemail after sending email </label>
                                                    <a href="#" data-bs-toggle="popover" data-bs-placement="top"
                                                        data-bs-trigger="focus"
                                                        data-bs-content="Enables email-only voicemail. Disables storing of voicemail messages for this mailbox in the cloud.">
                                                        <i class="uil uil-info-circle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="mb-3 text-sm-end">
                                                    <input type="hidden" name="voicemail_local_after_email"
                                                        value="true">
                                                    <input type="checkbox" id="voicemail_local_after_email"
                                                        data-switch="primary" name="voicemail_local_after_email"
                                                        value="false" @if (isset($voicemail->voicemail_local_after_email) && $voicemail->voicemail_local_after_email == 'false') checked @endif />
                                                    <label for="voicemail_local_after_email" data-on-label="On"
                                                        data-off-label="Off"></label>
                                                </div>
                                            </div>



                                            <div class="col-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Play voicemail tutorial </label>
                                                    <a href="#" data-bs-toggle="popover" data-bs-placement="top"
                                                        data-bs-trigger="focus"
                                                        data-bs-content="Play the voicemail tutorial after the next voicemail login.">
                                                        <i class="uil uil-info-circle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="mb-3 text-sm-end">
                                                    <input type="hidden" name="voicemail_tutorial" value="false">
                                                    <input type="checkbox" id="voicemail_tutorial" data-switch="primary"
                                                        name="voicemail_tutorial" value="true"
                                                        @if ($voicemail->voicemail_tutorial == 'true') checked @endif />
                                                    <label for="voicemail_tutorial" data-on-label="On"
                                                        data-off-label="Off"></label>
                                                    <div class="text-danger voicemail_tutorial_err error_message"></div>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->
                                    @endif

                                    @if ($voicemail->exists)
                                        <div class="row mb-4">
                                            <div class="col-lg-6">
                                                <h4 class="mt-2">Unavailable greeting</h4>

                                                <p class="text-muted mb-2">This plays when you do not pick up the phone.
                                                </p>
                                                <p class="text-black-50 mb-1">Play the default, upload or record a new
                                                    message.</p>

                                                <audio id="voicemail_unavailable_audio_file"
                                                    @if ($vm_unavailable_file_exists) src="{{ route('getVoicemailGreeting', ['voicemail' => $voicemail->voicemail_uuid, 'filename' => 'greeting_1.wav']) }}" @endif>
                                                </audio>
                                                <p class="text-muted mb-1">File name: <span
                                                        id='voicemailUnavailableFilename'>
                                                        <strong>
                                                            @if ($vm_unavailable_file_exists)
                                                                greeting_1.wav
                                                            @else
                                                                generic greeting
                                                            @endif
                                                        </strong></span></p>
                                                <button type="button" class="btn btn-light"
                                                    id="voicemail_unavailable_play_button"
                                                    @if (!$vm_unavailable_file_exists) disabled @endif title="Play"><i
                                                        class="uil uil-play"></i>
                                                </button>

                                                <button type="button" class="btn btn-light"
                                                    id="voicemail_unavailable_pause_button" title="Pause"><i
                                                        class="uil uil-pause"></i> </button>

                                                <button id="voicemail_unavailable_upload_file_button"
                                                    data-url="{{ route('uploadVoicemailGreeting', $voicemail->voicemail_uuid) }}"
                                                    type="button" class="btn btn-light" title="Upload">
                                                    <span id="voicemail_unavailable_upload_file_button_icon"><i
                                                            class="uil uil-export"></i> </span>
                                                    <span id="voicemail_unavailable_upload_file_button_spinner" hidden
                                                        class="spinner-border spinner-border-sm" role="status"
                                                        aria-hidden="true"></span>
                                                </button>
                                                <input id="voicemail_unavailable_upload_file" type="file" hidden />

                                                <a
                                                    href="{{ route('downloadVoicemailGreeting', [
                                                        'voicemail' => $voicemail->voicemail_uuid,
                                                        'filename' => 'greeting_1.wav',
                                                    ]) }}">
                                                    <button id="voicemail_unavailable_download_file_button" type="button"
                                                        class="btn btn-light" title="Download"
                                                        @if (!$vm_unavailable_file_exists) disabled @endif>
                                                        <i class="uil uil-down-arrow"></i>
                                                    </button>
                                                </a>

                                                <button id="voicemail_unavailable_delete_file_button" type="button"
                                                    class="btn btn-light" title="Delete"
                                                    data-url="{{ route('deleteVoicemailGreeting', ['voicemail' => $voicemail->voicemail_uuid, 'filename' => 'greeting_1.wav']) }}"
                                                    @if (!$vm_unavailable_file_exists) disabled @endif>
                                                    <span id="voicemail_unavailable_delete_file_button_icon"><i
                                                            class="uil uil-trash-alt"></i> </span>
                                                    <span id="voicemail_unavailable_delete_file_button_spinner" hidden
                                                        class="spinner-border spinner-border-sm" role="status"
                                                        aria-hidden="true"></span>
                                                </button>


                                                <div class="text-danger" id="voicemailUnvaialableGreetingError"></div>

                                            </div>

                                            <div class="col-lg-6">
                                                <h4 class="mt-2">Name greeting</h4>

                                                <p class="text-muted mb-2">This plays to identify your extension in the
                                                    company's dial by name directory.</p>
                                                <p class="text-black-50 mb-1">Play the default, upload or record a new
                                                    message.</p>
                                                <audio id="voicemail_name_audio_file"
                                                    @if ($vm_name_file_exists) src="{{ route('getVoicemailGreeting', ['voicemail' => $voicemail->voicemail_uuid, 'filename' => 'recorded_name.wav']) }}" @endif>
                                                </audio>
                                                <p class="text-muted mb-1">File name: <span id='voicemailNameFilename'>
                                                        <strong>
                                                            @if ($vm_name_file_exists)
                                                                recorded_name.wav
                                                            @else
                                                                generic greeting
                                                            @endif
                                                        </strong></span></p>
                                                <button type="button" class="btn btn-light"
                                                    id="voicemail_name_play_button"
                                                    @if (!$vm_name_file_exists) disabled @endif title="Play"><i
                                                        class="uil uil-play"></i>
                                                </button>

                                                <button type="button" class="btn btn-light"
                                                    id="voicemail_name_pause_button" title="Pause"><i
                                                        class="uil uil-pause"></i> </button>

                                                <button id="voicemail_name_upload_file_button"
                                                    data-url="{{ route('uploadVoicemailGreeting', $voicemail->voicemail_uuid) }}"
                                                    type="button" class="btn btn-light" title="Upload">
                                                    <span id="voicemail_name_upload_file_button_icon"><i
                                                            class="uil uil-export"></i> </span>
                                                    <span id="voicemail_name_upload_file_button_spinner" hidden
                                                        class="spinner-border spinner-border-sm" role="status"
                                                        aria-hidden="true"></span>
                                                </button>
                                                <input id="voicemail_name_upload_file" type="file" hidden
                                                    data-url="{{ route('uploadVoicemailGreeting', $voicemail->voicemail_uuid) }}" />

                                                <a
                                                    href="{{ route('downloadVoicemailGreeting', [
                                                        'voicemail' => $voicemail->voicemail_uuid,
                                                        'filename' => 'recorded_name.wav',
                                                    ]) }}">
                                                    <button id="voicemail_name_download_file_button" type="button"
                                                        class="btn btn-light" title="Download"
                                                        @if (!$vm_name_file_exists) disabled @endif>
                                                        <i class="uil uil-down-arrow"></i>
                                                    </button>
                                                </a>

                                                <button id="voicemail_name_delete_file_button" type="button"
                                                    class="btn btn-light" title="Delete"
                                                    data-url="{{ route('deleteVoicemailGreeting', ['voicemail' => $voicemail->voicemail_uuid, 'filename' => 'recorded_name.wav']) }}"
                                                    @if (!$vm_name_file_exists) disabled @endif>
                                                    <span id="voicemail_name_delete_file_button_icon"><i
                                                            class="uil uil-trash-alt"></i> </span>
                                                    <span id="voicemail_name_delete_file_button_spinner" hidden
                                                        class="spinner-border spinner-border-sm" role="status"
                                                        aria-hidden="true"></span>
                                                </button>

                                                <div class="text-danger" id="voicemailNameGreetingError"></div>

                                            </div>

                                        </div> <!-- end row-->
                                    @endif


                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="voicemail_description" class="form-label">Description</label>
                                                <textarea class="form-control" type="text" placeholder="" id="voicemail_description"
                                                    name="voicemail_description" />{{ $voicemail->voicemail_description }}</textarea>

                                                <div class="text-danger voicemail_description_err error_message"></div>
                                            </div>
                                        </div>
                                    </div> <!-- end row -->



                                    @if ($voicemail->exists && userCheckPermission('voicemail_forward'))
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label for="additional-destinations-select" class="form-label">Forward
                                                        voicemail messages to additional destinations.</label>
                                                    <!-- Multiple Select -->
                                                    <select class="select2 form-control select2-multiple"
                                                        data-toggle="select2" multiple="multiple"
                                                        data-placeholder="Choose ..." id="additional-destinations-select"
                                                        name="voicemail_destinations[]">
                                                        @foreach ($domain_voicemails as $domain_voicemail)
                                                            <option @if (isset($voicemail_destinations) && $voicemail_destinations->contains($domain_voicemail->voicemail_uuid)) selected @endif
                                                                value="{{ $domain_voicemail->voicemail_uuid }}"
                                                                @if ($voicemail->forward_destinations()->contains($domain_voicemail)) selected @endif>
                                                                @if (isset($domain_voicemail->extension->directory_first_name) ||
                                                                        isset($domain_voicemail->extension->directory_last_name))
                                                                    {{ $domain_voicemail->extension->directory_first_name ?? '' }}

                                                                    {{ $domain_voicemail->extension->directory_last_name ?? '' }}
                                                                    (ext {{ $domain_voicemail->voicemail_id }})
                                                                @elseif ($domain_voicemail->voicemail_description)
                                                                    {{ $domain_voicemail->voicemail_description }} (ext
                                                                    {{ $domain_voicemail->voicemail_id }})
                                                                @else
                                                                    Voicemail (ext {{ $domain_voicemail->voicemail_id }})
                                                                @endif



                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="text-danger voicemail_destinations_err error_message">
                                                    </div>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->
                                    @endif
                                    <div class="row mt-4">
                                        <div class="col-sm-12">
                                            <div class="text-sm-end">
                                                <input type="hidden" name="voicemail_uuid"
                                                    value="{{ $voicemail->voicemail_uuid }}">
                                                <a href="{{ Route('voicemails.index') }}" class="btn btn-light">Close</a>
                                                <button id="submitFormButton" class="btn btn-success" type="submit">Save
                                                </button>
                                            </div>
                                        </div> <!-- end col -->
                                    </div>


                                    </form>
                                </div>
                            </div> <!-- end row-->

                        </div>




                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col -->
        </div>
        <!-- end row-->

    </div> <!-- container -->

@endsection


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {


            $('#submitFormButton').on('click', function(e) {
                e.preventDefault();
                $('.loading').show();

                //Reset error messages
                $('.error_message').text("");

                $.ajax({
                        type: "POST",
                        url: $('#voicemail_form').attr('action'),
                        cache: false,
                        data: $("#voicemail_form").serialize(),
                    })
                    .done(function(response) {
                        $('.loading').hide();

                        if (response.error) {
                            printErrorMsg(response.error);

                        } else {
                            $.NotificationApp.send("Success", response.message, "top-right", "#10c469",
                                "success");
                            location.reload();

                        }
                    })
                    .fail(function(response) {
                        $('.loading').hide();
                        printErrorMsg(response.responseText);
                    });

            })

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
                        type: "POST",
                        url: url,
                        data: formData,
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

                        @if ($voicemail->exists)
                            //Update audio file
                            $("#voicemail_unavailable_audio_file").attr("src",
                                "{{ route('getVoicemailGreeting', ['voicemail' => $voicemail->voicemail_uuid, 'filename' => 'greeting_1.wav']) }}"
                            );
                        @endif

                        $("#voicemail_unavailable_audio_file")[0].pause();
                        $("#voicemail_unavailable_audio_file")[0].load();

                        $("#voicemailUnavailableFilename").html('<strong>' + response.filename +
                            '</strong>');

                        if (response.error) {
                            $.NotificationApp.send("Warning",
                                "There was a error uploading this greeting",
                                "top-right", "#ff5b5b", "error")
                            $("#voicemailUnvaialableGreetingError").text(response.message);
                        } else {
                            $.NotificationApp.send("Success",
                                "The greeeting has been uploaded successfully",
                                "top-right", "#10c469", "success")
                        }
                    })
                    .fail(function(response) {
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
                        type: "POST",
                        url: url,
                        data: formData,
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


                        @if ($voicemail->exists)
                            //Update audio file
                            $("#voicemail_name_audio_file").attr("src",
                                "{{ route('getVoicemailGreeting', ['voicemail' => $voicemail->voicemail_uuid, 'filename' => 'greeting_1.wav']) }}"
                            );
                        @endif
                        $("#voicemail_name_audio_file")[0].pause();
                        $("#voicemail_name_audio_file")[0].load();

                        $("#voicemailNameFilename").html('<strong>' + response.filename + '</strong>');

                        if (response.error) {
                            $.NotificationApp.send("Warning",
                                "There was a error uploading this greeting",
                                "top-right", "#ff5b5b", "error")
                            $("#voicemailNameGreetingError").text(response.message);
                        } else {
                            $.NotificationApp.send("Success",
                                "The greeeting has been uploaded successfully",
                                "top-right", "#10c469", "success")
                        }
                    })
                    .fail(function(response) {
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
                        type: "GET",
                        url: url,
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

                        if (response.error) {
                            $.NotificationApp.send("Warning",
                                "There was a error deleting this greeting",
                                "top-right", "#ff5b5b", "error")
                            $("#voicemailGreetingError").text(response.message);
                        } else {
                            $.NotificationApp.send("Success",
                                "The greeeting has been deleted successfully",
                                "top-right", "#10c469", "success")
                        }
                    })
                    .fail(function(response) {
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
                        type: "GET",
                        url: url,
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

                        if (response.error) {
                            $.NotificationApp.send("Warning",
                                "There was a error deleting this greeting",
                                "top-right", "#ff5b5b", "error")
                            $("#voicemailGreetingError").text(response.message);
                        } else {
                            $.NotificationApp.send("Success",
                                "The greeeting has been deleted successfully",
                                "top-right", "#10c469", "success")
                        }
                    })
                    .fail(function(response) {
                        //
                    });
            });

            // hide pause button
            $('#voicemail_unavailable_pause_button').hide();
            $('#voicemail_name_pause_button').hide();

            // Play unavailable audio file
            $('#voicemail_unavailable_play_button').click(function() {
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
            $('#voicemail_unavailable_pause_button').click(function() {
                var audioElement = document.getElementById('voicemail_unavailable_audio_file');
                $(this).hide();
                $('#voicemail_unavailable_play_button').show();
                audioElement.pause();
            });

            // Play name audio file
            $('#voicemail_name_play_button').click(function() {
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
            $('#voicemail_name_pause_button').click(function() {
                var audioElement = document.getElementById('voicemail_name_audio_file');
                $(this).hide();
                $('#voicemail_name_play_button').show();
                audioElement.pause();
            });


        });
    </script>
@endpush
