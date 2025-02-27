<!-- createMobileAppModal -->
<div id="contactsUploadResultModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="extensionUploadResultModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="contactsUploadResultModalLabel">Contacts Import</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
                <div class="modal-body">

                <!-- <h3 class="text-success">Success</h3> -->
                    <p class="mb-3">Congratulations! You have successfully imported contacts to your account. Below you will find the results.</p>
                    <div class="alert alert-success" role="alert" id="dropzoneSuccess">
                        <i class="ri-check-line me-2"></i> Your import was successfully.
                    </div>
                    <div class="alert alert-warning" role="alert" id="dropzoneError">
                        <i class="ri-alert-line me-2"></i> This is a <strong>warning</strong> alert - check it out!
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
