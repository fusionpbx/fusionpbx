@push('scripts')
    <!-- dropzone js -->
    {{-- <script src="{{ asset('assets/libs/dropzone/dropzone.min.js') }}"></script> --}}

    @vite(['resources/js/ui/component.fileupload.js', 'resources/js/hyper-syntax.js'])

    <script>
        document.addEventListener('dropzoneSuccessEvent', function() {
            // Handle success event here
            $('#extension-upload-modal').modal("hide");
            $('#extensionUploadResultModal').modal("show");
            $('#dropzoneSuccess').show();
            $('#dropzoneError').hide();

            // Successful Notification
            $.NotificationApp.send("Success", "Extensions have been successfully imported", "top-right", "#10c469",
                "success");

            setTimeout(function() {
                window.location.reload();
            }, 1000);
        });

        document.addEventListener('dropzoneErrorEvent', function(event) {
            // Handle error event here
            $('#extension-upload-modal').modal("hide");
            $('#extensionUploadResultModal').modal("show");
            $('#dropzoneError').html(event.detail.errorMessage);
            $('#dropzoneError').show();
            $('#dropzoneSuccess').hide();
            // Warning Notification
            $.NotificationApp.send("Warning", event.detail.errorMessage, "top-right", "#ff5b5b", "error");
        });



        document.addEventListener('DOMContentLoaded', function() {

            Livewire.on('userCreationCompleted', extensionUuid => {
                // Successful Notification
                $.NotificationApp.send("Success", "New User Added", "top-right",
                    "#10c469",
                    "success");

                $('#options' + extensionUuid).dropdown("toggle");
            })

            Livewire.on('userCreationFailed', (params) => {
                let extensionUuid = params[0];
                let error = params[1];

                printErrorMsg(error);

                $('#options' + extensionUuid).dropdown("toggle");
            });


            $("#connectionSelect2").select2({
                dropdownParent: $("#createMobileAppModal")
            });

            localStorage.removeItem('activeTab');

            // Open Modal with mobile app settings
            $('.mobileAppButton').on('click', function(e) {
                e.preventDefault();
                let href = $(this).attr('data-attr');
                //Hide error message
                $("#appMobileAppError").find("ul").html('');
                $("#appMobileAppError").css('display', 'none');
                //Hide success message
                $("#appMobileAppSuccess").find("ul").html('');
                $("#appMobileAppSuccess").css('display', 'none');
                $('.loading').show();
                //Reset buttons to default
                $("#appUserDeleteButton").html('');
                $("#appUserDeleteButton").append('<i class="uil uil-multiply"></i> <span>Delete</span>');
                $("#appUserDeleteButton").prop("disabled", false);
                $("#appUserResetPasswordButton").html('');
                $("#appUserResetPasswordButton").append(
                    '<i class="uil-lock-alt me-1"></i> <span>Reset password</span>');
                $("#appUserResetPasswordButton").prop("disabled", false);
                $("#activate").prop('checked', true);

                $.ajax({
                        type: "POST",
                        url: href,
                        cache: false,
                    })
                    .done(function(response) {
                        // console.log(response);
                        if (response.error) {
                            $('.loading').hide();
                            printErrorMsg(response.error);
                        } else {
                            $('.loading').hide();
                            if (response.mobile_app) {
                                if (!response.mobile_app.status || response.mobile_app.status == 2 ||
                                    response.mobile_app.status == -1) {
                                    $("#appUserSetStatusButton").html('');
                                    $("#appUserSetStatusButton").append(
                                        '<i class="mdi mdi-power-plug-off me-1"></i> <span>Activate</span>'
                                    );
                                    $("#appUserSetStatusButton").addClass('btn-success');
                                    $("#appUserSetStatusButton").removeClass('btn-warning');
                                    $("#appUserSetStatusButton").prop("disabled", false);
                                    $('#appUserResetPasswordButton').hide();
                                } else if (response.mobile_app.status == 1) {
                                    $("#appUserSetStatusButton").html('');
                                    $("#appUserSetStatusButton").append(
                                        '<i class="mdi mdi-power-plug-off me-1"></i> <span>Deactivate</span>'
                                    );
                                    $("#appUserSetStatusButton").addClass('btn-warning');
                                    $("#appUserSetStatusButton").removeClass('btn-success');
                                    $("#appUserSetStatusButton").prop("disabled", false);
                                    $('#appUserResetPasswordButton').show();
                                }
                                let dataObj = new Object();
                                dataObj.mobile_app = response.mobile_app;
                                $('#MobileAppModal').data(dataObj).modal("show");
                                $('#mobileAppName').text(response.name);
                                $('#mobileAppExtension').text(response.extension);

                            } else {
                                $('#createMobileAppModal').modal("show");

                                response.connections.forEach(function(connection) {
                                    var newOption = new Option(connection.name, connection.id,
                                        false, false);
                                    $('#connectionSelect2').append(newOption).trigger('change');
                                });

                                $('#org_id').val(response.org_id);
                                $('#app_domain').val(response.app_domain);
                                $('#extension_uuid').val(response.extension_uuid);

                            }
                        }
                    })
                    .fail(function(jqXHR, testStatus, error) {
                        //console.log(error);
                        printErrorMsg(error);
                        $('.loading').hide();

                    });
            });


            // Open Modal to show SIP credentials
            $('.sipCredentialsButton').on('click', function(e) {
                e.preventDefault();
                let href = $(this).attr('data-attr');
                $('.loading').show();

                $.ajax({
                        type: "GET",
                        url: href,
                        cache: false,
                    })
                    .done(function(response) {
                        //console.log(response);
                        if (response.error) {
                            $('.loading').hide();
                            printErrorMsg(response.error);
                        } else {
                            $('#sipCredentialsModal').modal("show");

                            $('#sip_username').val(response.username);
                            $('#sip_password').val(response.password);
                            $('#sip_domain').val(response.domain);

                            $('.loading').hide();

                        }
                    })
                    .fail(function(jqXHR, testStatus, error) {
                        // console.log(error);
                        $('#loader').hide();
                        printErrorMsg(error);
                    });
            });

            // Copy to clipboard
            $('#copyUsernameToClipboardButton').on('click', function(e) {
                e.preventDefault();
                navigator.clipboard.writeText($('#sip_username').val()).then(
                    function() {
                        /* clipboard successfully set */
                        $.NotificationApp.send("Success", "The username was copied to your clipboard",
                            "top-right", "#10c469", "success");
                    },
                    function() {
                        /* clipboard write failed */
                        $.NotificationApp.send("Warning",
                            'Opps! Your browser does not support the Clipboard API', "top-right",
                            "#ff5b5b", "error");
                    });
            });

            // Copy to clipboard
            $('#copyDomainToClipboardButton').on('click', function(e) {
                e.preventDefault();
                navigator.clipboard.writeText($('#sip_domain').val()).then(
                    function() {
                        /* clipboard successfully set */
                        $.NotificationApp.send("Success", "The domain was copied to your clipboard",
                            "top-right", "#10c469", "success");
                    },
                    function() {
                        /* clipboard write failed */
                        $.NotificationApp.send("Warning",
                            'Opps! Your browser does not support the Clipboard API', "top-right",
                            "#ff5b5b", "error");
                    });
            });

            // Copy to clipboard
            $('#copyPasswordToClipboardButton').on('click', function(e) {
                e.preventDefault();
                navigator.clipboard.writeText($('#sip_password').val()).then(
                    function() {
                        /* clipboard successfully set */
                        $.NotificationApp.send("Success", "The password was copied to your clipboard",
                            "top-right", "#10c469", "success");
                    },
                    function() {
                        /* clipboard write failed */
                        $.NotificationApp.send("Warning",
                            'Opps! Your browser does not support the Clipboard API', "top-right",
                            "#ff5b5b", "error");
                    });
            });


            // Submit form to create a new user
            $('#createUserForm').on('submit', function(e) {
                e.preventDefault();
                //Change button to spinner
                $("#appUserCreateSubmitButton").html('');
                $("#appUserCreateSubmitButton").append(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
                );
                $("#appUserCreateSubmitButton").prop("disabled", true);

                //Hide error message
                $("#appUserError").find("ul").html('');
                $("#appUserError").css('display', 'none');

                var url = '{{ route('appsCreateUser') }}';

                $.ajax({
                        type: "POST",
                        url: url,
                        data: $(this).serialize(),
                    })
                    .done(function(response) {
                        // console.log(response);
                        // remove the spinner and change button to default
                        $("#appUserCreateSubmitButton").html('');
                        $("#appUserCreateSubmitButton").append('Create User');
                        $("#appUserCreateSubmitButton").prop("disabled", false);

                        if (response.error) {
                            $("#appUserError").find("ul").html('');
                            $("#appUserError").css('display', 'block');
                            $("#appUserError").find("ul").append('<li>' + response.error.message +
                                '</li>');

                        } else {
                            $('#createMobileAppModal').modal("hide");
                            if (response.user.status == 1) {
                                $('#createMobileAppSuccessModal').modal("show");
                                $('#createMobileAppSuccessModalLabel').text("Create mobile app user");
                                $('#createMobileAppSuccessModalTitle').text(
                                    'New mobile app user was successfully created.');
                                $('#usernameSpan').text(response.user.username);
                                $('#extensionSpan').text(response.user.username);
                                if(response.user.password != null) {
                                    $('#passwordSpan').text(response.user.password);
                                } else if(response.user.password_url != null) {
                                    $('#passwordSpan').html(`<a href="${response.user.password_url}" target="_blank">Get password</a>`);
                                } else {
                                    $('#passwordSpan').text('Check your email for the password');
                                }
                                $('#domainSpan').text(response.user.domain);
                                if(response.qrcode !== null) {
                                    $('#qrCode').html('<img src="data:image/png;base64, ' + response.qrcode + '" />');
                                }
                            } else if (response.user.status == -1) {
                                $('#createMobileAppDeactivatedSuccessModal').modal("show");
                            }

                        }
                    })
                    .fail(function(jqXHR, testStatus, error) {
                        // console.log(error);
                        printErrorMsg(error);
                    });
            });

        });


        // Submit request to delete mobile user
        function appUserDeleteAction(url, id = '') {
            var mobile_app = $("#MobileAppModal").data("mobile_app");
            url = url.replace(':id', mobile_app.extension_uuid);

            //Change button to spinner
            $("#appUserDeleteButton").html('');
            $("#appUserDeleteButton").append(
                '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
            );
            $("#appUserDeleteButton").prop("disabled", true);

            // //Hide error message
            $("#appMobileAppError").find("ul").html('');
            $("#appMobileAppError").css('display', 'none');
            $("#appMobileAppSuccess").find("ul").html('');
            $("#appMobileAppSuccess").css('display', 'none');

            $.ajax({
                    type: "POST",
                    url: url,
                    data: {
                        'mobile_app': mobile_app,
                        '_method': 'DELETE',
                    },
                })
                .done(function(response) {
                    // console.log(response);
                    // remove the spinner and change button to default
                    $("#appUserDeleteButton").html('');
                    $("#appUserDeleteButton").append('<i class="uil uil-multiply"></i> <span>Delete</span>');
                    $("#appUserDeleteButton").prop("disabled", false);

                    if (response.error) {
                        $("#appMobileAppError").find("ul").html('');
                        $("#appMobileAppError").css('display', 'block');
                        $("#appMobileAppError").find("ul").append('<li>' + response.error.message + '</li>');

                    } else {
                        $('#MobileAppModal').modal("hide");
                        $.NotificationApp.send("Success", response.success.message, "top-right", "#10c469", "success");

                    }
                })
                .fail(function(jqXHR, testStatus, error) {
                    // console.log(error);
                    printErrorMsg(error);
                });
        };

        // Submit event notify
        function sendEventNotify(url, extension_id = '') {
            //var setting_id = $("#confirmDeleteModal").data("setting_id");
            //$('#confirmDeleteModal').modal('hide');
            //$('.loading').show();

            if (extension_id == '') {
                extension_id = [];
                $('.action_checkbox').each(function(key, val) {
                    if ($(this).is(':checked')) {
                        extension_id.push($(this).val());
                    }
                });
            }

            //Check if we received an array with multiple IDs
            if (Array.isArray(extension_id)) {
                extension_id.forEach(function(item) {
                    // var url = $("#confirmDeleteModal").data("url");
                    url = url.replace(':id', item);
                    $.ajax({
                            type: 'POST',
                            url: url,
                            cache: false,
                        })
                        .done(function(response) {
                            //console.log(response);
                            //$('.loading').hide();

                            if (response.error) {
                                if (response.message) {
                                    $.NotificationApp.send("Warning", response.message, "top-right", "#ff5b5b",
                                        "error");
                                }
                                if (response.error.message) {
                                    $.NotificationApp.send("Warning", response.error.message, "top-right",
                                        "#ff5b5b", "error");
                                }

                            } else {
                                if (response.message) {
                                    $.NotificationApp.send("Success", response.message, "top-right", "#10c469",
                                        "success");
                                }

                                if (response.success && response.success.message) {
                                    $.NotificationApp.send("Success", response.success.message, "top-right",
                                        "#10c469", "success");
                                }

                            }
                        })
                        .fail(function(jqXHR, testStatus, error) {
                            $('.loading').hide();
                            printErrorMsg(error);
                        });
                });

            } else {
                //var url = $("#confirmDeleteModal").data("url");
                url = url.replace(':id', extension_id);

                $.ajax({
                        type: 'POST',
                        url: url,
                        cache: false,

                    })
                    .done(function(response) {
                        //$('.loading').hide();

                        if (response.error) {
                            if (response.message) {
                                $.NotificationApp.send("Warning", response.message, "top-right", "#ff5b5b", "error");
                            }
                            if (response.error.message) {
                                $.NotificationApp.send("Warning", response.error.message, "top-right", "#ff5b5b",
                                    "error");
                            }

                        } else {
                            if (response.message) {
                                $.NotificationApp.send("Success", response.message, "top-right", "#10c469", "success");
                            }

                            if (response.success && response.success.message) {
                                $.NotificationApp.send("Success", response.success.message, "top-right", "#10c469",
                                    "success");
                            }

                        }
                    })
                    .fail(function(jqXHR, testStatus, error) {
                        $('.loading').hide();
                        printErrorMsg(error);
                    });
            }
        };


        // Submit request to reset password for mobile user
        function appUserResetPasswordAction(url, id = '') {
            var mobile_app = $("#MobileAppModal").data("mobile_app");
            url = url.replace(':id', mobile_app.extension_uuid);

            //Change button to spinner
            $("#appUserResetPasswordButton").html('');
            $("#appUserResetPasswordButton").append(
                '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
            );
            $("#appUserResetPasswordButton").prop("disabled", true);

            // //Hide error message
            $("#appMobileAppError").find("ul").html('');
            $("#appMobileAppError").css('display', 'none');
            $("#appMobileAppSuccess").find("ul").html('');
            $("#appMobileAppSuccess").css('display', 'none');

            $.ajax({
                    type: "POST",
                    url: url,
                    data: {
                        'mobile_app': mobile_app,
                    },
                })
                .done(function(response) {
                    //console.log(response);
                    // remove the spinner and change button to default
                    $("#appUserResetPasswordButton").html('');
                    $("#appUserResetPasswordButton").append(
                        '<i class="uil-lock-alt me-1"></i> <span>Reset password</span>');
                    $("#appUserResetPasswordButton").prop("disabled", false);

                    if (response.error) {
                        $("#appMobileAppError").find("ul").html('');
                        $("#appMobileAppError").css('display', 'block');
                        $("#appMobileAppError").find("ul").append('<li>' + response.error.message + '</li>');

                    } else {
                        $('#MobileAppModal').modal("hide");
                        // $("#appMobileAppSuccess").find("ul").html('');
                        // $("#appMobileAppSuccess").css('display','block');
                        // $("#appMobileAppSuccess").find("ul").append('<li>'+response.success.message+'</li>');
                        $('#createMobileAppSuccessModal').modal("show");
                        $('#createMobileAppSuccessModalLabel').text("Reset Password");
                        $('#createMobileAppSuccessModalTitle').text('Success');

                        $('#usernameSpan').text(response.user.username);
                        $('#extensionSpan').text(response.user.username);
                        if(response.user.password != null) {
                            $('#passwordSpan').text(response.user.password);
                        } else if(response.user.password_url != null) {
                            $('#passwordSpan').html(`<a href="${response.user.password_url}" target="_blank">Get password</a>`);
                        } else {
                            $('#passwordSpan').text('Check your email for the password');
                        }
                        $('#domainSpan').text(response.user.domain);
                        if(response.qrcode !== null) {
                            $('#qrCode').html('<img src="data:image/png;base64, ' + response.qrcode + '" />');
                        }
                    }
                })
                .fail(function(jqXHR, testStatus, error) {
                    // console.log(error);
                    printErrorMsg(error);
                });
        };


        // Submit request to reset password for mobile user
        function appUserSetStatusAction(url, id = '') {
            var mobile_app = $("#MobileAppModal").data("mobile_app");
            url = url.replace(':id', mobile_app.extension_uuid);
            // console.log (mobile_app.status);
            // Set new status
            if (!mobile_app.status || mobile_app.status == -1) {
                mobile_app.status = 1;
            } else {
                mobile_app.status = -1
            }

            //Change button to spinner
            $("#appUserSetStatusButton").html('');
            $("#appUserSetStatusButton").append(
                '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
            );
            $("#appUserSetStatusButton").prop("disabled", true);

            // //Hide error message
            $("#appMobileAppError").find("ul").html('');
            $("#appMobileAppError").css('display', 'none');
            $("#appMobileAppSuccess").find("ul").html('');
            $("#appMobileAppSuccess").css('display', 'none');

            $.ajax({
                    type: "POST",
                    url: url,
                    data: {
                        'mobile_app': mobile_app,
                    },
                })
                .done(function(response) {
                    // console.log(response);
                    if (response.error) {

                        // remove the spinner and change button to default
                        if (mobile_app.status == -1) {
                            $("#appUserSetStatusButton").html('');
                            $("#appUserSetStatusButton").append(
                                '<i class="mdi mdi-power-plug-off me-1"></i> <span>Deactivate</span>');
                            $("#appUserSetStatusButton").addClass('btn-warning');
                            $("#appUserSetStatusButton").removeClass('btn-success');
                            $("#appUserSetStatusButton").prop("disabled", false);
                            $('#appUserResetPasswordButton').show();
                            mobile_app.status = 1;
                        }
                        if (mobile_app.status == 1) {
                            $("#appUserSetStatusButton").html('');
                            $("#appUserSetStatusButton").append(
                                '<i class="mdi mdi-power-plug-off me-1"></i> <span>Activate</span>');
                            $("#appUserSetStatusButton").addClass('btn-success');
                            $("#appUserSetStatusButton").removeClass('btn-warning');
                            $("#appUserSetStatusButton").prop("disabled", false);
                            $('#appUserResetPasswordButton').hide();
                            mobile_app.status = -1;
                        }
                        dataObj = new Object();
                        dataObj.mobile_app = mobile_app;
                        $('#MobileAppModal').data(dataObj);

                        $("#appMobileAppError").find("ul").html('');
                        $("#appMobileAppError").css('display', 'block');
                        $("#appMobileAppError").find("ul").append('<li>' + response.error.message + '</li>');

                    } else {
                        // remove the spinner and change button to default
                        if (mobile_app.status == 1) {
                            $("#appUserSetStatusButton").html('');
                            $("#appUserSetStatusButton").append(
                                '<i class="mdi mdi-power-plug-off me-1"></i> <span>Deactivate</span>');
                            $("#appUserSetStatusButton").addClass('btn-warning');
                            $("#appUserSetStatusButton").removeClass('btn-success');
                            $("#appUserSetStatusButton").prop("disabled", false);
                            $('#appUserResetPasswordButton').show();
                        }
                        if (mobile_app.status == -1) {
                            $("#appUserSetStatusButton").html('');
                            $("#appUserSetStatusButton").append(
                                '<i class="mdi mdi-power-plug-off me-1"></i> <span>Activate</span>');
                            $("#appUserSetStatusButton").addClass('btn-success');
                            $("#appUserSetStatusButton").removeClass('btn-warning');
                            $("#appUserSetStatusButton").prop("disabled", false);
                            $('#appUserResetPasswordButton').hide();
                        }

                        $("#appMobileAppSuccess").find("ul").html('');
                        $("#appMobileAppSuccess").css('display', 'block');
                        $("#appMobileAppSuccess").find("ul").append('<li>' + response.success.message + '</li>');

                    }
                })
                .fail(function(jqXHR, testStatus, error) {
                    // console.log(error);
                    printErrorMsg(error);
                });

        };
    </script>
@endpush
