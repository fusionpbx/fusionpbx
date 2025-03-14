<!-- bundle -->
@yield('script')
@yield('script-bottom')
@stack('scripts')

@vite(['node_modules/select2/dist/scss/select2.min.scss', 'resources/js/ui/component.toastr.js', 'node_modules/jquery-toast-plugin/dist/jquery.toast.min.scss'])


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set the csrf token, and set dataType: 'json' for all ajax requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json'
        });

        // Domain search
        $("#domainSearchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#domainSearchList *").filter(function() {
                $(this).parent('.listgroup').toggle($(this).text().toLowerCase().indexOf(
                    value) > -1)
            });
        });

        // Fire an event when right side bar is toggled
        $('#right-modal').on('shown.bs.modal', function(e) {
            $('#domainSearchInput').focus();
        });

        $('#status-select').on('change', function() {
            var location = window.location.protocol + "//" + window.location.host + window.location
                .pathname;
            location += '?page=1&' + $('#filterForm').serialize();
            window.location.href = location;
        })

        // Action checkboxes on list pages
        $('#selectallCheckbox').on('change', function() {
            if ($(this).is(':checked')) {
                $('.action_checkbox').prop('checked', true);
            } else {
                $('.action_checkbox').prop('checked', false);
            }
            if (checkIfAnyCheckboxesChecked()) {
                $('#deleteMultipleActionButton').removeClass('disabled');
            } else {
                $('#deleteMultipleActionButton').addClass('disabled');
            }
        });

        $('.action_checkbox').on('change', function() {
            if (!$(this).is(':checked')) {
                $('#selectallCheckbox').prop('checked', false);
            } else {
                if (checkIfAllCheckboxesChecked()) {
                    $('#selectallCheckbox').prop('checked', true);
                }
            }
            if (checkIfAnyCheckboxesChecked()) {
                $('#deleteMultipleActionButton').removeClass('disabled');
            } else {
                $('#deleteMultipleActionButton').addClass('disabled');
            }
        });

        $('#clearSearch').on('click', function() {
            $('#search').val('');
            var location = window.location.protocol + "//" + window.location.host + window.location
                .pathname;
            location += '?page=1';
            window.location.href = location;
        })
    });

    // This function receives IDs and URL for items to be deleted and passes them to the Confirm Delete Modal
    function confirmDeleteAction(url, setting_id = '') {

        dataObj = new Object();
        dataObj.url = url;
        // console.log(dataObj.url);

        if (setting_id == '') {
            setting_id = [];
            $('.action_checkbox').each(function(key, val) {
                if ($(this).is(':checked')) {
                    setting_id.push($(this).val());
                }
            });
        }
        dataObj.setting_id = setting_id;
        $('#confirmDeleteModal').data(dataObj).modal('show');
        // deleteSetting(setting_id);
    }

    //This function sends AJAX request to delete selected items from list pages
    function performConfirmedDeleteAction() {
        var setting_id = $("#confirmDeleteModal").data("setting_id");
        $('#confirmDeleteModal').modal('hide');
        //$('.loading').show();

        //Check if we received an array with multiple IDs
        if (Array.isArray(setting_id)) {
            setting_id.forEach(function(item) {
                var url = $("#confirmDeleteModal").data("url");
                url = url.replace(':id', item);
                $.ajax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_method': 'DELETE',
                        },
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
                            $("#id" + item).fadeOut("slow");
                            // console.log(item);
                            //$(this).closest('tr').fadeOut("fast");
                        }
                    })
                    .fail(function(jqXHR, testStatus, error) {
                        $('.loading').hide();
                        printErrorMsg(error);
                    });
            });

        } else {
            var url = $("#confirmDeleteModal").data("url");
            url = url.replace(':id', setting_id);
            $.ajax({
                    type: 'POST',
                    url: url,
                    cache: false,
                    data: {
                        '_method': 'DELETE',
                    }
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
                        $("#id" + setting_id).fadeOut("slow");

                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        }
                        //$(this).closest('tr').fadeOut("fast");
                    }
                })
                .fail(function(jqXHR, testStatus, error) {
                    $('.loading').hide();
                    printErrorMsg(error);
                });
        }

    }

    // Check if all action checkboxes on the page are checked
    function checkIfAllCheckboxesChecked() {
        var checked = true;
        $('.action_checkbox').each(function(key, val) {
            if (!$(this).is(':checked')) {
                checked = false;
            }
        });
        return checked;
    }

    //Check if at least one action checkbox is checked
    function checkIfAnyCheckboxesChecked() {
        var has = false;
        $('.action_checkbox').each(function(key, val) {
            if ($(this).is(':checked')) {
                has = true;
            }
        });
        return has;
    }

    // Add errors to the page and send alert
    function printErrorMsg(msg) {
        var error_message = "<ul>";
        if (Array.isArray(msg) || typeof msg === 'object') {
            $.each(msg, function(key, value) {
                if (typeof key === 'string' || key instanceof String) {
                    key = key.replace(/\./g, '_')
                    $('.' + key + '_err').text(value);
                    $('.' + key + '_err_badge').attr("hidden", false);
                }
                error_message = error_message + '<li>' + value + '</li>';
            });
        } else {
            error_message = msg;
        }
        error_message = error_message + "</ul>";
        $.NotificationApp.send("Warning", error_message, "top-right", "#ff5b5b", "error")

    }

    // Test if variable is JSON
    function testJSON(text) {
        if (typeof text !== "string") {
            return false;
        }
        try {
            var json = JSON.parse(text);
            return (typeof json === 'object');
        } catch (error) {
            return false;
        }
    }

    function selectDomain(domain_uuid) {
        event.preventDefault();

        axios.post("{{ route('switchDomain') }}", {
                    timeout: 5000, // Set the timeout to 5 seconds (adjust as needed)
                    domain_uuid: domain_uuid,
                })
                .then(response => {
                    const redirectUrl = response.data.redirectUrl;

                    window.location.href = redirectUrl;
                })
                .catch(error => {
                    if (axios.isCancel(error)) {
                        console.error('Request canceled:', error.message);
                    } else {
                        console.error('Error fetching data:', error);
                    }
                });


    }
</script>
