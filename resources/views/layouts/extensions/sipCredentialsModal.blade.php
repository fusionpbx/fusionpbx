<!-- Sip Credentials modal -->
<div id="sipCredentialsModal"  class="modal fade" id="bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="sipCredentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="sipCredentialsModalLabel">User SIP Credentials</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sip_username" class="form-label">Username</label>
                    <div class="input-group input-group-merge">
                        <input type="username" id="sip_username" name="sip_username" class="form-control" readonly="" placeholder="">
                        <div class="input-group-text" id="copyUsernameToClipboardButton">
                            <span data-bs-container="#copyUsernameToClipboardButton" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Copy to Clipboard" role="button">
                                <i class="mdi mdi-content-copy"></i>
                            </span>
                        </div>
                    </div>
                </div>
                
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="sip_password" class="form-control" placeholder="" readonly="" name="sip_password">
                        <div class="input-group-text" data-password="false" id="showPasswordButton">
                            <span class="password-eye" data-bs-container="#showPasswordButton" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Show Password"></span>
                        </div>
                        <div class="input-group-text" id="copyPasswordToClipboardButton">
                            <span data-bs-container="#copyPasswordToClipboardButton" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Copy to Clipboard" role="button">
                                <i class="mdi mdi-content-copy"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sip_domain" class="form-label">Domain</label>
                    <div class="input-group input-group-merge">
                        <input type="domain" id="sip_domain" name="sip_domain" class="form-control" readonly="" placeholder="">
                        <div class="input-group-text" id="copyDomainToClipboardButton">
                            <span data-bs-container="#copyDomainToClipboardButton" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Copy to Clipboard" role="button">
                                <i class="mdi mdi-content-copy"></i>
                            </span>
                        </div>
                    </div>
                </div>
                  <p>*Do not share these credenatils with anyone</p>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
