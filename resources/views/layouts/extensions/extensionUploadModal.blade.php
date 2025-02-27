<!-- Extension upload modal -->
<div id="extension-upload-modal" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="extension-upload-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="extension-upload-modalLabel">Import Extensions</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">

                <!-- File Upload -->
                <form action="{{ route('extensions.import') }}" method="post" class="dropzone" id="file_dropzone" data-plugin="dropzone"
                    data-previews-container="#file-previews" data-upload-preview-template="#uploadPreviewTemplate"
                    data-auto-process-queue="false" data-upload-multiple="false" data-parallel-uploads="5" data-max-filesize="5" data-max-files="1"
                    data-thumbnail-width="200" data-accepted-files=".csv,.xls,.xlsx">
                    <div class="fallback">
                        <input name="file" type="file" multiple />
                    </div>

                    <div class="dz-message needsclick dropzone-select">
                        <i class="h1 text-muted mdi mdi-cloud-upload-outline"></i>
                        <h3>Drop files here or click to upload.</h3>
                        <div class="mb-1"><a class="dropzone-select btn btn-sm btn-primary me-2">Browse files</a>
                        </div>
                        <span class="text-muted font-13">Supported file types: .csv, .xls, .xlsx</span>

                    </div>
                </form>

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
                                    <a href="javascript:void(0);" class="text-muted fw-bold" data-dz-name></a>
                                    <p class="mb-0" data-dz-size></p>
                                </div>
                                <div class="col-auto">
                                    <!-- Button -->
                                    <a href="" class="btn btn-link btn-lg text-muted" data-dz-remove>
                                        <i class="ri-close-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-danger files_err error_message"></div>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" id="dropzoneSubmit" class="btn btn-primary">Next</button>
                {{-- <button type="submit" class="btn btn-primary">Next</button> --}}

            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
