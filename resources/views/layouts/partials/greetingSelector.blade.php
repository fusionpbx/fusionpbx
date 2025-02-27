<label for="{{ $id }}" class="form-label">Greeting</label>
<div class="d-flex flex-row">
    <div class="w-100 me-1">
        <select class="select2 form-control" data-toggle="select2" data-placeholder="Choose ..." id="{{ $id }}"
            name="{{ $id }}">
            <option value="disabled">Disabled</option>
            @if (!$allRecordings->isEmpty())
                <optgroup label="Recordings">
                    @foreach ($allRecordings as $recording)
                        <option value="{{ $recording->recording_filename }}"
                            @if ($recording->recording_filename == $value) selected @endif>
                            {{ $recording->recording_name }}
                        </option>
                    @endforeach
                </optgroup>
            @endif
        </select>
    </div>
    <button type="button" class="btn btn-light me-1 @if ($value == null) d-none @endif"
        id="{{ $id }}_play_pause_button" title="Play/Pause"><i class="uil uil-play"></i></button>
    <button type="button" class="btn btn-light" id="{{ $id }}_manage_greeting_button"
        title="Manage greetings"><i class="uil uil-cog"></i></button>
    <audio id="{{ $id }}_audio_file"
        @if ($value) src="{{ route('recordings.file', ['filename' => $value]) }}" @endif></audio>
</div>
<div class="modal fade" id="{{ $id }}_manage_greeting_modal" role="dialog"
    aria-labelledby="{{ $id }}_manage_greeting_modal" aria-hidden="true">
    <div class="modal-dialog w-50" style="max-width: initial;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Greetings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="{{ $id }}_manage_greeting_modal_body"></div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-dark-subtle border">
                            <div class="card-body mb-1">
                                <h5 class="card-title">Upload new greeting</h5>
                                <input type="file" id="{{ $id }}_filename" name="greeting_filename"
                                    accept=".wav" class="form-control" />
                                <div class="text-danger error_message {{ $id }}_greeting_filename_err"></div>
                            </div> <!-- end card-body-->
                        </div> <!-- end card-->
                    </div> <!-- end col-->

                    <div class="col-md-6">
                        <div class="card border-dark-subtle border">
                            <div class="card-body">
                                <h5 class="card-title">Record new greeting</h5>

                                <div class="mb-1">
                                    <button type="button" id="{{ $id }}_record_button"
                                        class="btn btn-light p-1 px-2 me-1 fs-4" title="Start/Stop recording"><i
                                            class="mdi mdi-record"></i></button>
                                    <button disabled type="button" id="{{ $id }}_recorded_play_pause_button"
                                        class="btn btn-light p-1 px-2 me-1 fs-4" title="Play/Pause recorded audio"><i
                                            class="mdi mdi-play"></i></button>
                                </div>
                                <div class="text-danger error_message {{ $id }}_greeting_recorded_file_err">
                                </div>
                                <div id="{{ $id }}_record_in_progress_status"
                                    class="d-none recording-in-progress">
                                    Recording in progress. Please speak now.
                                </div>
                                <div id="{{ $id }}_record_is_done_status"
                                    class="d-none recording-is-done text-muted">
                                    Recording complete. You can save it or delete and record again.
                                </div>
                                <audio id="{{ $id }}_recorded_audio_file" class="d-none"></audio>
                                <input type="hidden" name="recorded_audio_file_stored"
                                    id="{{ $id }}_recorded_audio_file_stored" value="" />
                            </div> <!-- end card-body-->
                        </div> <!-- end card-->
                    </div> <!-- end col-->

                </div>

                <div class="d-flex justify-content-center">
                    <button type="button" id="{{ $id }}_save_recording_btn" disabled="disabled"
                        class="btn btn-success save-recording-btn">Save new greeting</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@if ($hint ?? false)
    <span class="help-block"><small>{{ $hint }}</small></span>
@endif
<div id="{{ $id }}_err" class="text-danger error_message"></div>
<style>
    .mdi-record {
        color: red;
    }

    .recording-in-progress {
        animation: blinker 1s linear infinite;
        color: red;
    }

    #{{ $id }}_manage_greeting_modal_body {
        height: 300px;
        overflow-y: scroll;
        margin-bottom: 1em;
    }

    #{{ $id }}_manage_greeting_modal_body .table {
        margin-bottom: 0;
    }

    #{{ $id }}_manage_greeting_modal_body::-webkit-scrollbar {
        -webkit-appearance: none;
        width: 10px;
    }

    #{{ $id }}_manage_greeting_modal_body .loading.loading-inline {
        top: 130px;
    }

    #{{ $id }}_manage_greeting_modal_body tr td {
        vertical-align: middle;
    }

    #{{ $id }}_manage_greeting_modal_body tr.blink-it td {
        animation: blinkingBackground 3s ease-in-out;
        box-shadow: none;
    }

    #{{ $id }}_manage_greeting_modal_body::-webkit-scrollbar-thumb {
        border-radius: 5px;
        background-color: rgba(0, 0, 0, .5);
        -webkit-box-shadow: 0 0 1px rgba(255, 255, 255, .5);
    }

    #{{ $id }}_editRecordingModal .modal-dialog,
    #{{ $id }}_confirmDeleteRecordingModal .modal-dialog {
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.5)
    }

    @keyframes blinker {
        50% {
            opacity: 0;
        }
    }

    @keyframes blinkingBackground {
        0% {
            background-color: #c1dcfa;
        }

        100% {
            background-color: white;
        }
    }
</style>
@if ($inlineScripts ?? true)
    @push('scripts')
        @include('layouts.partials.greetingSelectorJs', [
            'id' => $id,
            'entity' => $entity,
            'entityid' => $entityid,
            'showUseRecordingAction' => $showUseRecordingAction ?? true
        ])
    @endpush
@endif

<div class="modal fade" id="{{ $id }}_confirmDeleteRecordingModal" data-bs-backdrop="static"
    data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body p-4">
                <div class="text-center">
                    {{-- <i class=" dripicons-question h1 text-danger"></i> --}}
                    <i class="uil uil-times-circle h1 text-danger"></i>
                    <h3 class="mt-3">Are you sure?</h3>
                    <p class="mt-3">Do you really want to delete this? This process cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger me-2 delete-greeting-btn">Delete</button>
            </div> <!-- end modal footer -->
        </div> <!-- end modal content-->
    </div> <!-- end modal dialog-->
</div> <!-- end modal-->

<div class="modal fade" id="{{ $id }}_editRecordingModal" data-bs-backdrop="static"
    data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Greeting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="greeting_id" id="{{ $id }}_id" />
                <div class="border border-dark-subtle p-3">
                    <div class="mb-2">
                        <label for="{{ $id }}_name" class="form-label">Name <span
                                class="text-danger">*</span></label>
                        <input type="text" id="{{ $id }}_name" name="greeting_name"
                            class="form-control" value="" />
                        <div class="text-danger error_message {{ $id }}_greeting_name_err"></div>
                    </div>
                    <div class="mb-2">
                        <label for="{{ $id }}_description" class="form-label">Description</label>
                        <textarea class="form-control" id="{{ $id }}_description" name="greeting_description" rows="2"></textarea>
                        <div class="text-danger error_message {{ $id }}_greeting_description_err"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success save-description-btn">Save</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div> <!-- end modal dialog-->
</div> <!-- end modal-->
