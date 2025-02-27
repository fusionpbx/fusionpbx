<!-- createMobileAppDeactivatedSuccessModal -->
<div id="createMobileAppDeactivatedSuccessModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="createMobileAppDeactivatedSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="createMobileAppDeactivatedSuccessModalLabel">Create mobile app user</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
                <div class="modal-body">

                    <h3 class="text-success">Success</h3>
                    <p class="mb-3">You successfully created an unactivated user. To register with {{ config('app.name', 'Laravel') }} apps, please activate the user. 
                        Unactivated users are visible in the contacts list in {{ config('app.name', 'Laravel') }} apps.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->