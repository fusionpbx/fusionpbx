<!-- createMobileAppModal -->
<div id="createMobileAppModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="createMobileAppModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="createMobileAppModalLabel">Create mobile app user</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form class="ps-3 pe-3" action="" id="createUserForm">
                <div class="modal-body">

                    <div class="row mb-3">
                        <div class="col-8">
                            <div class="mb-1">
                                <label class="form-label">Activate and generate app credentials</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-1 text-sm-end">
                                <input type="hidden" name="activate" value="false">
                                <input type="checkbox" id="activate" name="activate" checked
                                    data-switch="primary"/>
                                <label for="activate" data-on-label="On" data-off-label="Off"></label>
                                <div class="text-danger activate_err error_message"></div>
                            </div>
                        </div>
                        <span class="help-block"><small>Turn this setting off if you need to create contact only and don't need to generate user's app credentials at this time</small></span>
                    </div> <!-- end row -->

                    <div class="alert alert-danger" id="appUserError" style="display:none">
                        <ul></ul>
                    </div>

                    <input type="hidden" name="org_id" id="org_id" value="">
                    <input type="hidden" name="app_domain" id="app_domain" value="">
                    <input type="hidden" name="extension_uuid" id="extension_uuid" value="">

                    <div class="row mb-1">
                        <div class="col-12 text-center">
                            <a class="btn btn-link" data-bs-toggle="collapse"
                                href="#advancedOptions" aria-expanded="false"
                                aria-controls="advancedOptions">
                                Advanced
                                <i class="uil uil-angle-down"></i>
                            </a>
                        </div>
                    </div>
                    <div class="collapse" id="advancedOptions">
                        <div class="col-12">
                            <div class="mb-1">
                                <label class="form-label">Choose Connection</label>
                                <select id="connectionSelect2" data-toggle="select2" title="Connection" name="connection">
                                
                                </select>
                                <div class="text-danger connection_err error_message"></div>
                            </div>
                        </div>
                        <span class="help-block"><small>In most cases the default setting will work. Consult with your administrator if you need to change it</small></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button id="appUserCreateSubmitButton" type="submit" class="btn btn-primary">Create user</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->