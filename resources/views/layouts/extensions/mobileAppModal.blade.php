<!-- MobileAppModal -->
<div id="MobileAppModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="MobileAppModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="MobileAppModalLabel">Mobile App Settings</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
                <div class="modal-body">

                    <div class="card">
                        <div class="card-body">
                            <div class="dropdown float-end">
                                {{-- <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-horizontal"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" style="">
                                    <!-- item-->
                                    <a href="javascript:void(0);" class="dropdown-item">View Profile</a>
                                    <!-- item-->
                                    <a href="javascript:void(0);" class="dropdown-item">Project Info</a>
                                </div> --}}
                            </div>

                            <div class="text-center">
                                {{-- <img src="assets/images/users/avatar-1.jpg" class="rounded-circle avatar-md img-thumbnail" alt="friend"> --}}
                                <h3 class="mt-3 my-1"><span id="mobileAppName"></span> </h3>
                                <p class="mb-0 text-muted"></i>Ext: <span id="mobileAppExtension"></span></p>
                                <hr class="bg-dark-lighten my-3">
                                <h5 class="mt-3 mb-3 fw-semibold text-muted">Select an action below</h5>
                            
                                <a href="javascript:appUserSetStatusAction('{{ route('appsSetStatus', ':id') }}');" id="appUserSetStatusButton" class="btn btn-warning me-2 btn-sm">
                                    <i class="mdi mdi-power-plug-off me-1"></i> <span>Deactivate</span>
                                </a>
                                
                                <a href="javascript:appUserResetPasswordAction('{{ route('appsResetPassword', ':id') }}');" id="appUserResetPasswordButton" class="btn btn-primary me-2 btn-sm">
                                    <i class="uil-lock-alt me-1"></i> <span>Reset password</span>
                                </a>

                                <a href="javascript:appUserDeleteAction('{{ route('appsDeleteUser', ':id') }}');" id="appUserDeleteButton" class="btn btn-danger btn-sm">
                                    <i class="uil uil-multiply me-1"></i><span>Delete</span>
                                </a>
                            </div>

                            <div class="alert alert-danger mt-3" id="appMobileAppError" style="display:none">
                                <ul></ul>
                            </div>

                            <div class="alert alert-success mt-3" id="appMobileAppSuccess" style="display:none">
                                <ul></ul>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->