@extends('layouts.app', ['page_title' => 'New Fax'])

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('faxes.index') }}">Virtual Fax Machines</a>
                            </li>
                            <li class="breadcrumb-item active">New Fax</li>
                        </ol>
                    </div>
                    <h4 class="page-title">New Fax</h4>
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

                                    <form method="POST" id="new_fax_form" action="{{ route('faxes.sendFax') }}">
                                        @csrf

                                        <div class="row @if ($fax) d-none @endif">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="sender_fax_number" class="form-label">Your Fax Number
                                                        <span
                                                            class="text-danger">*</span></label>

                                                    <select data-toggle="select2" title="Fax Number"
                                                            id="sender_fax_number"
                                                            name="sender_fax_number">

                                                        @foreach ($fax_numbers as $fax_number)
                                                            <option
                                                                value="{{ phone($fax_number->fax_caller_id_number, 'US')->formatE164() }}"
                                                                @if ( $fax &&
                                                                    $fax->fax_caller_id_number &&
                                                                        phone($fax->fax_caller_id_number, 'US')->formatE164() ==
                                                                            phone($fax_number->fax_caller_id_number, 'US')->formatE164()) selected @endif>
                                                                {{ phone($fax_number->fax_caller_id_number, 'US', $national_phone_number_format) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="text-danger error_message sender_fax_number_err"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="recipient" class="form-label me-1">Add Fax Recipient
                                                        <span
                                                            class="text-danger">*</span></label>
                                                    <a href="#" data-bs-toggle="popover" data-bs-placement="bottom"
                                                       data-bs-trigger="focus" title="How to format your fax number?"
                                                       data-bs-content="Enter your number as follows: area code + number.
                                                    For example: the number 1 323-212-6688 should be entered as 3232126688">
                                                        <i class="uil uil-info-circle"></i>
                                                    </a>

                                                    <input class="form-control" type="text"
                                                           placeholder="Enter a fax number" id="recipient"
                                                           name="recipient"/>
                                                    <div class="text-danger recipient_err error_message"></div>
                                                </div>

                                            </div>
                                        </div> <!-- end row -->

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-2">
                                                    <button id="addNoteButton" type="button" class="btn btn-link"
                                                            style="padding:0;">Add Note
                                                    </button>
                                                    <a href="#" data-bs-toggle="popover" data-bs-placement="right"
                                                       data-bs-trigger="focus"
                                                       data-bs-content="Entered text will be used a cover page">
                                                        <i class="uil uil-info-circle"></i>
                                                    </a>

                                                </div>
                                                <div class="mb-3">
                                                    {{-- <label for="noteTextarea" class="form-label">Text area</label> --}}
                                                    <textarea class="form-control" id="fax_message" rows="5"
                                                              style="display: none" name="fax_message"></textarea>
                                                    <div class="text-danger error_message fax_message_err"></div>
                                                </div>

                                            </div>
                                        </div> <!-- end row -->

                                        <div class="row mb-3">
                                            <!--begin::Label-->
                                            <label class="form-label">Upload files</label>
                                            {{-- <label class="col-lg-1 col-form-label text-lg-right">Upload Files:</label> --}}
                                            <!--end::Label-->

                                            <!--begin::Col-->
                                            <div class="col-lg-6">
                                                <!--begin::Dropzone-->
                                                {{--
                                                <div class="dropzone dropzone-queue mb-2" data-plugin="dropzone" data-action="https://fakeurl.com">
                                                    <!--begin::Controls-->
                                                    <div class="dz-message needsclick dropzone-select">
                                                        <i class="h1 text-muted mdi mdi-cloud-upload-outline"></i>
                                                        <h3>Drop fax files here or click to upload.</h3>
                                                        <div class="mb-1"><a
                                                                class="dropzone-select btn btn-sm btn-primary me-2">Browse
                                                                files</a></div>
                                                        <span class="text-muted font-13">(Uploaded files will be faxed to
                                                            the recipient.)</span>

                                                    </div>
                                                    <!--end::Controls-->

                                                    <!--begin::Items-->
                                                    <div class="dropzone-items row">
                                                        <div class="dropzone-item" style="display:none">
                                                            <!--begin::File-->
                                                            <div class="card mb-2 shadow-none border">
                                                                <div class="p-2">
                                                                    <div class="row align-items-center">
                                                                        <div class="col-auto">
                                                                            <div class="avatar-sm">
                                                                                <span
                                                                                    class="avatar-title bg-light text-secondary rounded">
                                                                                    <i class="h1 mdi mdi-file-outline "></i>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="dropzone-filename col" title="">
                                                                            <strong><span
                                                                                    data-dz-name>some_image_file_name.jpg</span></strong>
                                                                            (<span data-dz-size>340kb</span>)
                                                                        </div>

                                                                        <div class="col-2">
                                                                            <!--begin::Progress-->
                                                                            <div class="dropzone-progress">
                                                                                <div class="progress progress-sm">
                                                                                    <div class="progress-bar bg-success"
                                                                                        role="progressbar" aria-valuemin="0"
                                                                                        aria-valuemax="100" aria-valuenow="0"
                                                                                        data-dz-uploadprogress>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <!--end::Progress-->
                                                                        </div>
                                                                        <div class="col-auto">
                                                                            <!-- Button -->
                                                                            <a class="dropzone-delete btn btn-link btn-lg text-danger"
                                                                                data-dz-remove>
                                                                                <i class="uil uil-multiply"></i>
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-danger dropzone-error" data-dz-errormessage>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!--end::File-->

                                                        </div>
                                                    </div>
                                                    <!--end::Items-->
                                                </div>
                                                <!--end::Dropzone--> --}}

                                                <!-- File Upload -->
                                                <div action="{{ route('faxes.sendFax') }}"
                                                      class="dropzone" id="file_dropzone" data-plugin="dropzone"
                                                      data-previews-container="#file-previews"
                                                      data-upload-preview-template="#uploadPreviewTemplate"
                                                      data-auto-process-queue="false" data-upload-multiple="true"
                                                      data-parallel-uploads="5" data-max-filesize="5" data-max-files="5"
                                                      data-thumbnail-width="200" data-accepted-files=".pdf, .doc, .docx, .rtf, .xls, .xlsx, .csv, .txt, .jpg, .jpeg">
                                                    <div class="fallback">
                                                        <input name="file" type="file" multiple/>
                                                    </div>

                                                    <div class="dz-message needsclick dropzone-select">
                                                        <i class="h1 text-muted mdi mdi-cloud-upload-outline"></i>
                                                        <h3>Drop files here or click to upload.</h3>
                                                        <div class="mb-1"><a
                                                                class="dropzone-select btn btn-sm btn-primary me-2">Browse
                                                                files</a>
                                                        </div>
                                                        <span class="text-muted font-13">Supported file types: .pdf, .doc, .docx, .rtf, .xls, .xlsx, .csv, .txt, .jpg</span>

                                                    </div>
                                                   
                                                </div>

                                                <!-- Preview -->
                                                <div class="dropzone-previews mt-3" id="file-previews"></div>

                                                <!-- file preview template -->
                                                <div class="d-none" id="uploadPreviewTemplate">
                                                    <div class="card mt-1 mb-0 shadow-none border">
                                                        <div class="p-2">
                                                            <div class="row align-items-center">
                                                                <div class="col-auto">
                                                                    <div class="avatar-sm">
                                                                        <span class="avatar-title bg-light text-secondary rounded">
                                                                            <i class="h1 mdi mdi-file-outline "></i>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="col ps-0">
                                                                    <a href="javascript:void(0);"
                                                                       class="text-muted fw-bold" data-dz-name></a>
                                                                    <p class="mb-0" data-dz-size></p>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <!-- Button -->
                                                                    <a href="" class="btn btn-link btn-lg text-muted"
                                                                       data-dz-remove>
                                                                        <i class="ri-close-line"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-danger files_err error_message"></div>

                                                <!--begin::Hint-->
                                                <span class="form-text text-muted">Max file size is 5MB and max number of
                                            files is 5.</span>
                                                <!--end::Hint-->
                                                <div class="text-danger files_err error_message"></div>
                                            </div>
                                            <!--end::Col-->
                                        </div>
                                        <!--end::Input group-->


                                        <div class="row d-none">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_resolution" class="form-label">Resolution <span
                                                            class="text-danger">*</span></label>
                                                    <select data-toggle="select2" title="Fax Resolution"
                                                            name="fax_resolution">
                                                        <option value="normal">Normal</option>
                                                        <option value="fine" selected>Fine</option>
                                                        <option value="superfine">Superfine</option>
                                                    </select>
                                                    <div class="text-danger error_message fax_resolution_err"></div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="page_size" class="form-label">Page Size <span
                                                            class="text-danger">*</span></label>
                                                    <select data-toggle="select2" title="Page Size" name="page_size">
                                                        <option value="letter" selected>Letter</option>
                                                        <option value="legal">Legal</option>
                                                        <option value="a4">A4</option>
                                                    </select>
                                                    <div class="text-danger error_message page_size_err"></div>
                                                </div>
                                            </div>


                                        </div> <!-- end row -->


                                        <div class="row d-none">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_footer" class="form-label">Footer </label>
                                                    <textarea class="form-control" type="text" placeholder=""
                                                              id="fax_footer" name="fax_footer"/></textarea>
                                                    <div class="text-danger error_message fax_footer_err"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Send fax confirmation to my email </label>
                                                    <a href="#" data-bs-toggle="popover" data-bs-placement="top"
                                                       data-bs-trigger="focus"
                                                       data-bs-content="You will receive a fax confirmation either when it is successfully sent or if it fails to send.">
                                                        <i class="uil uil-info-circle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="mb-3 text-sm-end">
                                                    <input type="checkbox" id="send_confirmation"
                                                           name="send_confirmation" data-switch="primary"/>
                                                    <label for="send_confirmation" data-on-label="On"
                                                           data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->


                                        <div class="row mt-4">
                                            <div class="col-sm-6">
                                                <div class="text-sm-end">
                                                    @if($fax)
                                                        <input type="hidden" name="fax_uuid"
                                                               value="{{ $fax->fax_uuid }}">
                                                    @endif
                                                    <input type="hidden" name="fax_subject" value="">
                                                    <a href="{{ Route('faxes.index') }}" class="btn btn-light">Close</a>
                                                    <button id="dropzoneSubmit" class="btn btn-success">
                                                        Send
                                                        Fax
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
    <!-- dropzone js -->

    @vite(['resources/js/ui/component.fileupload.js', 'resources/js/hyper-syntax.js'])

    <script>
    
        const link = document.getElementById('addNoteButton');
        const textArea = document.getElementById('fax_message');

        link.addEventListener('click', () => {
            textArea.style.display = (textArea.style.display === 'none') ? 'block' : 'none';
        });

        document.addEventListener('dropzoneSuccessEvent', function() {
            // Handle success event here

            // Successful Notification
            $.NotificationApp.send("Success", "Fax transmission has been successfully scheduled", "top-right", "#10c469",
                "success");

            setTimeout(function() {
                window.location.href = '{{ route("faxes.index") }}';
            }, 1000);
        });

        document.addEventListener('dropzoneErrorEvent', function(event) {
            // Handle error event here

            // Warning Notification
            $.NotificationApp.send("Warning", event.detail.errorMessage, "top-right", "#ff5b5b", "error");
        });

    </script>
@endpush

