<!-- createMobileAppSuccessModal -->
<div id="createMobileAppSuccessModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="createMobileAppSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="createMobileAppSuccessModalLabel">Create mobile app user</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
                <div class="modal-body">

                    <h3 class="text-success" id="createMobileAppSuccessModalTitle">New mobile app was user sucessfully created.</h3>
                    <p>You have successfully created mobile app credentials. Please use the generated password to login. 
                        You will not be able to view the password again. However, you can reset the password at any time.</p>
                    <table class="attributes" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td class="attributes_content">
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td class="attributes_item"><strong>Domain:&nbsp;</strong><span class="ms-1" id="domainSpan"></span></td>
                            </tr>
                            <tr>
                              <td class="attributes_item"><strong>Extension:&nbsp;</strong><span class="ms-1" id="extensionSpan"></span></td>
                            </tr>
                            <tr>
                                <td class="attributes_item"><strong>Username:&nbsp;</strong><span class="ms-1" id="usernameSpan"></span></td>
                            </tr>
                            <tr>
                            <td class="attributes_item"><strong>Password:&nbsp;</strong><span class="ms-1" id="passwordSpan"></span></td>
                            </tr>  
                          </table>
                        </td>
                        <td id="qrCode">
                        </td>
                      </tr>
                    </table>

                    <p class="mt-2">If the user has an email on file, credentials will be sent to that address.</p>
                    
                    <h3 class="mt-3">Next steps</h3>
                    <p>Use the links below to download {{ config('app.name', 'Laravel') }} apps. Then log in using the credentials shown above or scan a QR code via the mobile app interface.</p>
                    
                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                          <td align="center">
                            <!-- Border based button https://litmus.com/blog/a-guide-to-bulletproof-buttons-in-email-design -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td align="center">
                                  <table border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                      <td>
                      
                                        <a href="{{ getDefaultSetting('mobile_apps', 'google_play_link') }}">
                                          <img class="max-width" border="0" style="display:block; color:#000000; text-decoration:none; font-family:Helvetica, arial, sans-serif; font-size:16px; height:auto 
                                            !important;" width="189" alt="Download for Android" data-proportionally-constrained="true" data-responsive="true" 
                                            src="https://cdn.mcauto-images-production.sendgrid.net/b9e58e76174a4c84/88af7fc9-c74b-43ec-a1e2-a712cd1d3052/646x250.png">
                                        </a>
                      
                      
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                              </tr>
                            </table>
                          </td>
                          <td>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td align="center">
                                  <table border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                      <td>
                                        <a href="{{ getDefaultSetting('mobile_apps', 'apple_store_link') }}"><img class="max-width" border="0" style="display:block; color:#000000; 
                                          text-decoration:none; font-family:Helvetica, arial, sans-serif; font-size:16px; height:auto !important;" width="174" alt="Download for iOS" data-proportionally-constrained="true" data-responsive="true" 
                                          src="https://cdn.mcauto-images-production.sendgrid.net/b9e58e76174a4c84/bb2daef8-a40d-4eed-8fb4-b4407453fc94/320x95.png">
                                        </a>
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                              </tr>
                            </table>
                      
                          </td>
                        </tr>
                        <tr>
                          <td align="center">
                            <!-- Border based button https://litmus.com/blog/a-guide-to-bulletproof-buttons-in-email-design -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td align="center">
                                  <table border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                      <td>
                                        <a href="{{ $action_url ?? ''}}" class="button button--" target="_blank">Get it for <strong>Windows</strong></a>
                      
                      
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                              </tr>
                            </table>
                          </td>
                          <td>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td align="center">
                                  <table border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                      <td>
                                        <a href="{{ $action_url ?? ''}}" class="button button--" target="_blank">Download for <strong>Mac</strong></a>
                      
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                              </tr>
                            </table>
                      
                          </td>
                        </tr>
                      </table>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->