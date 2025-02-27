@extends('layouts.app', ['page_title' => 'Apps'])

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">App Provisioning Status</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-xl-8">
                                <form
                                    class="row gy-2 gx-2 align-items-center justify-content-xl-start justify-content-between">
                                    <div class="col-auto">
                                        <label for="inputPassword2" class="visually-hidden">Search</label>
                                        <input type="search" class="form-control" id="inputPassword2"
                                            placeholder="Search...">
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex align-items-center">
                                            <label for="status-select" class="me-2">Status</label>
                                            <select class="form-select" id="status-select">
                                                <option selected>Choose...</option>
                                                <option value="1">Inactive</option>
                                                <option value="2">Provisioned</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-xl-4">
                                <div class="text-xl-end mt-xl-0 mt-2">
                                    <button type="button" class="btn btn-success mb-2 me-2 disabled"
                                        id="appProvisionButton" data-bs-toggle="modal"
                                        data-bs-target="#app-provision-modal">Provision
                                    </button>

                                    <a href="#" data-attr="{{ route('appsGetOrganizations') }}"
                                        class="btn btn-success mb-2 me-2 orgSyncButton" title="Sync">
                                        <i class="mdi mdi-cloud me-1" data-bs-container="#tooltip-container-actions"
                                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Sync"></i>Sync
                                    </a>

                                    {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
                                </div>
                            </div><!-- end col-->
                        </div>

                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20px;">
                                        </th>
                                        <th>Company</th>
                                        <th>Domain</th>
                                        <th>Status</th>
                                        <th>BLFs</th>
                                        <th>SMS</th>
                                        <th style="width: 125px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 1;
                                    @endphp
                                    @foreach ($domains as $domain)
                                        <tr>
                                            {{-- <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input appCompanyCheckbox" id="@php print 'companyCheck'.$i; @endphp"
                                                    value="{{ $company['domain_uuid'] }}">
                                                <label class="form-check-label" for="@php print 'companyCheck'.$i; @endphp">&nbsp;</label>
                                            </div>
                                        </td> --}}
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" name="action_box[]"
                                                        value="{{ $domain->domain_uuid }}"
                                                        class="form-check-input action_company_checkbox">
                                                    <label class="form-check-label">&nbsp;</label>
                                                </div>
                                            </td>
                                            <td><a href=""
                                                    class="text-body fw-bold">{{ $domain->domain_description ?? $domain->domain_name }}</a>
                                            </td>
                                            <td>
                                                {{ $domain->domain_name }}
                                                <input type="hidden" name="dont_send_user_credentials" @if (get_domain_setting('dont_send_user_credentials', $domain->domain_uuid) == "true") value="true" @else value="false" @endif />
                                                <input type="hidden" name="password_url_show" @if (get_domain_setting('password_url_show', $domain->domain_uuid) == "true") value="true" @else value="false" @endif />
                                            </td>
                                            <td>
                                                @if ($domain->status == 'true')
                                                    <h5><span class="badge bg-success">Provisioned</span></h5>
                                                @else
                                                    <h5><span class="badge bg-warning">Inactive</span></h5>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">Coming Soon...</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">Coming Soon...</small>
                                            </td>
                                            <td>
                                                {{-- Action Buttons --}}
                                                <div id="tooltip-container-actions">

                                                    <a href="javascript:syncAppUsers('{{ route('appsSyncUsers', ':id') }}','{{ $domain->domain_uuid }}');"
                                                        class="action-icon" title="Sync Users">
                                                        <i class="uil uil-sync"
                                                            data-bs-container="#tooltip-container-actions"
                                                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                            title="Sync Users"></i>
                                                    </a>

                                                    <a href="javascript:confirmAppDeleteAction('{{ route('appsDestroyOrganization', ':id') }}','{{ $domain->domain_uuid }}');"
                                                        class="action-icon">
                                                        <i class="mdi mdi-delete"
                                                            data-bs-container="#tooltip-container-actions"
                                                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                            title="Delete"></i>
                                                    </a>

                                                </div>
                                                {{-- End of action buttons --}}
                                            </td>
                                        </tr>
                                        @php
                                            $i++;
                                        @endphp
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col -->
        </div>
        <!-- end row -->

    </div> <!-- container -->

    <!-- Provision modal-->
    <div id="app-provision-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-body">

                    <ul class="nav nav-tabs nav-justified nav-bordered mb-3">
                        <li class="nav-item">
                            <a href="#organization-b2" data-bs-toggle="tab" aria-expanded="false" class="nav-link active">
                                <i class="mdi mdi-home-variant d-md-none d-block"></i>
                                <span class="d-none d-md-block">Organization</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#connection-b2" data-bs-toggle="tab" aria-expanded="true" class="nav-link">
                                <i class="mdi mdi-account-circle d-md-none d-block"></i>
                                <span class="d-none d-md-block">Connection</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#result-b2" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                                <span class="d-none d-md-block">Finish</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane show active" id="organization-b2">
                            <form class="ps-3 pe-3" action="" id="createOrganizationForm">

                                <div class="mb-3">
                                    <label for="organization_name" class="form-label">Organization Name</label>
                                    <input class="form-control" type="text" id="organization_name"
                                        name="organization_name" required="" placeholder="">
                                </div>

                                <div class="row">
                                    <div class="col-7">
                                        <div class="mb-3">
                                            <label for="organization_domain" class="form-label">Unique Organization
                                                Domain</label>
                                            <input class="form-control" type="text" id="organization_domain"
                                                name="organization_domain" required="" placeholder="">
                                        </div>
                                    </div>

                                    <div class="col-5">
                                        <div class="mb-3">
                                            <label for="organization_region" class="form-label">Region</label>
                                            <select class="form-select mb-3" id="organization_region" name="organization_region">
                                                <option value="1" {{ $default_region == '1' ? 'selected' : '' }}>US East</option>
                                                <option value="2" {{ $default_region == '2' ? 'selected' : '' }}>US West</option>
                                                <option value="3" {{ $default_region == '3' ? 'selected' : '' }}>Europe (Frankfurt)</option>
                                                <option value="4" {{ $default_region == '4' ? 'selected' : '' }}>Asia Pacific (Singapore)</option>
                                                <option value="5" {{ $default_region == '5' ? 'selected' : '' }}>Europe (London)</option>
                                                <option value="6" {{ $default_region == '6' ? 'selected' : '' }}>India</option>
                                                <option value="7" {{ $default_region == '7' ? 'selected' : '' }}>Australia</option>
                                                <option value="8" {{ $default_region == '8' ? 'selected' : '' }}>Europe (Dublin)</option>
                                                <option value="9" {{ $default_region == '9' ? 'selected' : '' }}>Canada (Central)</option>
                                                <option value="10" {{ $default_region == '10' ? 'selected' : '' }}>South Africa</option>
                                            </select>

                                        </div>
                                    </div>
                                    <input type="hidden" id="organization_uuid" name="organization_uuid">
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <label class="form-label">Mobile app configuration</label>
                                        <div class="mb-3">
                                            <div class="form-check mb-2">
                                                <input type="hidden" name="dont_send_user_credentials" value="false">
                                                <input type="checkbox" class="form-check-input" id="dont_send_user_credentials" name="dont_send_user_credentials" value="true" />
                                                <label class="form-check-label" for="dont_send_user_credentials">Don't send user credentials in a plain text</label>
                                            </div>
                                            <span class="help-block"><small>If this option is enabled, users in this organization must use a one-time link to access their app password.</small></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-danger" id="appOrganizationError" style="display:none">
                                    <ul></ul>
                                </div>

                                <div class="mb-3 text-center">
                                    <button class="btn btn-primary" id="appProvisionNextButton"
                                        type="submit">Next</button>
                                </div>

                            </form>
                        </div>


                        <div class="tab-pane" id="connection-b2">
                            <form class="ps-3 pe-3" action="" id="createConnectionForm">

                                <div class="row">
                                    <div class="col-7">
                                        <div class="mb-3">
                                            <label for="connection_name" class="form-label">Connection Name</label>
                                            <input class="form-control" type="text" id="connection_name"
                                                name="connection_name" required="" placeholder="">
                                            <span class="help-block"><small>Enter a name for this connection</small></span>
                                        </div>
                                    </div>

                                    <div class="col-5">
                                        <div class="mb-3">
                                            <label for="connection_protocol" class="form-label">Protocol</label>
                                            <select class="form-select mb-3" id="connection_protocol"
                                                name="connection_protocol">
                                                <option value="sip">SIP (UDP)</option>
                                                <option value="tcp">SIP (TCP)</option>
                                                <option value="sips">SIPS (TLS/SRTP)</option>
                                            </select>

                                        </div>
                                    </div>
                                    <input type="hidden" id="org_id" name="org_id">
                                    <input type="hidden" id="connection_organization_uuid"
                                        name="connection_organization_uuid">
                                </div>

                                <div class="row">
                                    <div class="col-8">
                                        <div class="mb-3">
                                            <label for="connection_domain" class="form-label">Domain Name or IP
                                                Address</label>
                                            <input class="form-control" type="text" id="connection_domain"
                                                name="connection_domain" required="" placeholder="">
                                            <span class="help-block"><small>e.g. pbx.example.com or
                                                    192.168.1.101</small></span>
                                        </div>
                                    </div>

                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label for="connection_port" class="form-label">Port</label>
                                            <input class="form-control" type="text" id="connection_port"
                                                name="connection_port" value="" required="" placeholder="">
                                            <span class="help-block"><small>SIP Port</small></span>

                                        </div>
                                    </div>
                                </div>


                                <div class="accordion mb-3" id="accordionExample">
                                    <div class="card mb-0">
                                        <div class="card-header" id="headingOne">
                                            <h5 class="m-0">
                                                <a class="custom-accordion-title d-block pt-2 pb-2"
                                                    data-bs-toggle="collapse" href="#collapseOne" aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                    Outbound Proxy
                                                </a>
                                            </h5>
                                        </div>

                                        <div id="collapseOne" class="collapse" aria-labelledby="headingOne"
                                            data-bs-parent="#accordionExample">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="connection_proxy_address"
                                                        class="form-label">Address</label>
                                                    <input class="form-control" type="text"
                                                        id="connection_proxy_address" name="connection_proxy_address"
                                                        value="" placeholder="">
                                                    <span class="help-block"><small>e.g.
                                                            pbx.example.com:5070</small></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mb-0">
                                        <div class="card-header" id="headingTwo">
                                            <h5 class="m-0">
                                                <a class="custom-accordion-title collapsed d-block pt-2 pb-2"
                                                    data-bs-toggle="collapse" href="#collapseTwo" aria-expanded="false"
                                                    aria-controls="collapseTwo">
                                                    Miscellaneous
                                                </a>
                                            </h5>
                                        </div>
                                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                            data-bs-parent="#accordionExample">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-4">
                                                        <div class="mb-3">
                                                            <label for="connection_ttl" class="form-label">Registration
                                                                TTL</label>
                                                            <input class="form-control" type="text"
                                                                id="connection_ttl" name="connection_ttl" value="300"
                                                                required="" placeholder="">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="mb-3">
                                                            <div class="form-check mb-2">
                                                                <input type="checkbox" class="form-check-input"
                                                                    id="connection_private_list"
                                                                    name="connection_private_list">
                                                                <label class="form-check-label"
                                                                    for="connection_private_list">Private user list</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mb-0">
                                        <div class="card-header" id="headingThree">
                                            <h5 class="m-0">
                                                <a class="custom-accordion-title collapsed d-block pt-2 pb-2"
                                                    data-bs-toggle="collapse" href="#collapseThree" aria-expanded="false"
                                                    aria-controls="collapseThree">
                                                    Security
                                                </a>
                                            </h5>
                                        </div>
                                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                                            data-bs-parent="#accordionExample">
                                            <div class="card-body">
                                                ...
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mb-0">
                                        <div class="card-header" id="headingFour">
                                            <h5 class="m-0">
                                                <a class="custom-accordion-title collapsed d-block pt-2 pb-2"
                                                    data-bs-toggle="collapse" href="#collapseFour" aria-expanded="false"
                                                    aria-controls="collapseFour">
                                                    Audio Codecs
                                                </a>
                                            </h5>
                                        </div>
                                        <div id="collapseFour" class="collapse" aria-labelledby="headingFour"
                                            data-bs-parent="#accordionExample">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="connection_codec_u711" name="connection_codec_u711"
                                                            checked>
                                                        <label class="form-check-label" for="connection_codec_u711">G.711
                                                            uLaw</label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="connection_codec_a711" name="connection_codec_a711"
                                                            checked>
                                                        <label class="form-check-label" for="connection_codec_a711">G.711
                                                            aLaw</label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="connection_codec_729" name="connection_codec_729">
                                                        <label class="form-check-label"
                                                            for="connection_codec_729">G.729</label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="connection_codec_opus" name="connection_codec_opus">
                                                        <label class="form-check-label"
                                                            for="connection_codec_opus">OPUS</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <div class="alert alert-danger" id="appConnectionError" style="display:none">
                                    <ul></ul>
                                </div>

                                <div class="mb-3 text-center">
                                    <button class="btn btn-primary" id="appConnectionNextButton"
                                        type="submit">Next</button>
                                </div>

                            </form>
                        </div>
                        <div class="tab-pane" id="result-b2">
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-center">
                                        <h2 class="mt-0"><i class="mdi mdi-check-all"></i></h2>
                                        <h3 class="mt-0">Success !</h3>

                                        <p class="w-75 mb-2 mx-auto">New organization is provisioned and ready to be used.
                                        </p>

                                    </div>
                                </div> <!-- end col -->
                            </div>
                        </div>
                    </div>


                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    {{-- Sync Modal --}}
    <div class="modal fade" id="appSyncModal" tabindex="-1" role="dialog" aria-labelledby="app-sync-modal_title"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal_title">Sync existing organizations from the cloud</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <div class="card-body">

                        <div class="table-responsive">
                            <table id="appsTable" class="table table-centered table-nowrap table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cloud</th>
                                        <th>Local</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div> <!-- end table-responsive-->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button href="#" data-attr="{{ route('appsSyncOrganizations') }}" type="button"
                        class="btn btn-primary" id="appSyncSaveButton">Save changes</button>
                    {{-- <button type="button" class="btn btn-primary" id="appSyncSaveButton">Save changes</button> --}}
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->


    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmAppDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body p-4">
                    <div class="text-center">
                        {{-- <i class=" dripicons-question h1 text-danger"></i> --}}
                        <i class="uil uil-times-circle h1 text-danger"></i>
                        <h3 class="mt-3">Are you sure?</h3>
                        <p class="mt-3">Do you really want to remove this provisioning profile? It will delete all data
                            associated with this profile from the cloud. This process cannot be undone.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                    <a href="javascript:performConfirmedAppDeleteAction();" class="btn btn-danger me-2">Delete</a>
                </div> <!-- end modal footer -->
            </div> <!-- end modal content-->
        </div> <!-- end modal dialog-->
    </div> <!-- end modal-->
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            $(".appDomainSelect2").select2({
                dropdownParent: $("#appSyncModal")
            });
            $("#someSelect2").select2({
                dropdownParent: $("#appSyncModal")
            });

            // Open Organization Sync Modal
            $('.orgSyncButton').on('click', function(e) {
                e.preventDefault();
                let href = $(this).attr('data-attr');

                $('.loading').show();

                $.ajax({
                        type: "GET",
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
                            if (response.cloud_orgs) {
                                $('#appSyncModal').modal("show");

                                var newRows = "";
                                // for (var i = 0; i < response.cloud_orgs.length; i++) {
                                jQuery.each(response.cloud_orgs, function(index, cloud_org) {
                                    newRows += '<tr id="' + cloud_org.id + '">' +
                                        '<td>' +
                                        '<h5 class="font-14 my-1 fw-normal">' + cloud_org.name +
                                        '</h5>' +
                                        '<span class="text-muted font-13">id: ' + cloud_org.id +
                                        ' </span>' +
                                        '</td>' +
                                        '<td>' +
                                        // '<h5 class="font-14 my-1 fw-normal">$79.49</h5>' +
                                        // '<span class="text-muted font-13">Price</span>' +
                                        '<select class="select2 appDomainSelect2" data-toggle="select2" title="AppDomain" name="AppDomain">';
                                    if (cloud_org.domain_uuid) {
                                        newRows += '<option selected value="' + cloud_org
                                            .domain_uuid + '">' + cloud_org.name + ' </option>';
                                    } else {
                                        newRows += '<option selected> Select domain </option>';
                                    }

                                    newRows += '</select>' +
                                        '</td>' +
                                        '</tr>';
                                });
                                $("#appsTable").empty();
                                $("#appsTable").append(newRows);

                                $('.appDomainSelect2').select2({
                                    dropdownParent: $("#appSyncModal"),
                                    sorter: data => data.sort((a, b) => a.text.localeCompare(b
                                        .text)),
                                });

                                response.local_orgs.forEach(function(local_org) {
                                    if (!$('.appDomainSelect2').find("option[value='" +
                                            local_org.domain_uuid + "']").length) {
                                        var newOption = new Option(local_org.domain_description,
                                            local_org.domain_uuid, false, false);
                                        $('.appDomainSelect2').append(newOption).trigger(
                                            'change');
                                    }
                                });


                            }

                        }
                    })
                    .fail(function(jqXHR, status, error) {
                        printErrorMsg(jqXHR.responseJSON.error.message);
                        $('#loader').hide();

                    });
            });


            // Save changes
            $('#appSyncSaveButton').on('click', function(e) {
                e.preventDefault();

                //Change button to spinner
                $("#appSyncSaveButton").html('');
                $("#appSyncSaveButton").append(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
                    );
                $("#appSyncSaveButton").prop("disabled", true);

                let href = $(this).attr('data-attr');
                $.ajax({
                        type: "post",
                        url: href,
                        data: {
                            'app_array': app_array,
                        },
                        cache: false,
                    })
                    .done(function(response) {
                        // console.log(response);

                        // remove the spinner and change button to default
                        $("#appSyncSaveButton").html('');
                        $("#appSyncSaveButton").append('Save Changes');
                        $("#appSyncSaveButton").prop("disabled", false);

                        if (response.error) {
                            printErrorMsg(response.error.message);
                        } else {
                            $('#appSyncModal').modal("hide");
                            $.NotificationApp.send("Success", response.success.message, "top-right",
                                "#10c469", "success");
                            var app_array = {};
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        }
                    })
                    .fail(function(jqXHR, testStatus, error) {
                        //console.log(error);
                        printErrorMsg(error);
                        $('#loader').hide();

                    });
            });


            // Change Provision button status to enabled when at least one organization is selected
            $('.action_company_checkbox').on('change', function() {
                //Uncheck other checkboxes
                $('.action_company_checkbox').not($(this)).prop('checked', false);

                //Toggle the status of Provision button
                var checkBoxes = $('.action_company_checkbox');
                $('#appProvisionButton').toggleClass('disabled', checkBoxes.filter(':checked').length < 1);

                //Prefill the form in the modal with selected values
                $('#organization_name').val($.trim($(this).closest("tr").find('td:eq(1)').text()));
                $('#organization_domain').val($.trim($(this).closest("tr").find('td:eq(1)').text())
                    .toLowerCase().replace(/ /g, '').replace(/[^\w-]+/g, ''));
                $('#organization_uuid').val($.trim($(this).val()));
                $('#connection_domain').val($.trim($(this).closest("tr").find('td:eq(2)').text()));
                $('#connection_name').val($.trim($(this).closest("tr").find('td:eq(1)').text()));

                let dontSendUserCredentialsFlag = $.trim($(this).closest("tr").find('input[name=dont_send_user_credentials]').val()) === 'true'
                $('#dont_send_user_credentials').prop('checked', dontSendUserCredentialsFlag);
                $('#connection_organization_uuid').val($.trim($(this).val()));
            });

            // Provision new organization
            $('#createOrganizationForm').on('submit', function(e) {
                e.preventDefault();
                //Change button to spinner
                $("#appProvisionNextButton").html('');
                $("#appProvisionNextButton").append(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
                    );
                $("#appProvisionNextButton").prop("disabled", true);

                //Hide error message
                $("#appOrganizationError").find("ul").html('');
                $("#appOrganizationError").css('display', 'none');

                var url = '{{ route('appsCreateOrganization') }}';

                $.ajax({
                        type: "POST",
                        url: url,
                        data: $(this).serialize(),
                    })
                    .done(function(response) {
                        // console.log(response);
                        // remove the spinner and change button to default
                        $("#appProvisionNextButton").html('');
                        $("#appProvisionNextButton").append('Next');
                        $("#appProvisionNextButton").prop("disabled", false);

                        if (response.error) {
                            $("#appOrganizationError").find("ul").html('');
                            $("#appOrganizationError").css('display', 'block');
                            $("#appOrganizationError").find("ul").append('<li>' + response.message +
                                '</li>');

                        } else {
                            //Switch to the next tab
                            $('a[href*="connection-b2"] span').trigger("click");
                            // Assign Org ID to a hidden input
                            $("#org_id").val(response.org_id);
                            // Assign other variables
                            $("#connection_port").val(response.connection_port);
                            $("#connection_proxy_address").val(response.outbound_proxy + ":" + response
                                .connection_port);
                            $('#connection_protocol').val(response.protocol);
                            $('#connection_protocol').trigger('change');
                        }
                    })
                    .fail(function(response) {
                        //
                    });
            });


            // Provision new Connection
            $('#createConnectionForm').on('submit', function(e) {
                e.preventDefault();
                //Change button to spinner
                $("#appConnectionNextButton").html('');
                $("#appConnectionNextButton").append(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
                    );
                $("#appConnectionNextButton").prop("disabled", true);

                //Hide error message
                $("#appConnectionError").find("ul").html('');
                $("#appConnectionError").css('display', 'none');

                var url = '{{ route('appsCreateConnection') }}';

                $.ajax({
                        type: "POST",
                        url: url,
                        data: $(this).serialize(),
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                    })
                    .done(function(response) {
                        // remove the spinner and change button to default
                        $("#appConnectionNextButton").html('');
                        $("#appConnectionNextButton").append('Next');
                        $("#appConnectionNextButton").prop("disabled", false);

                        if (response.error) {
                            $("#appConnectionError").find("ul").html('');
                            $("#appConnectionError").css('display', 'block');
                            $("#appConnectionError").find("ul").append('<li>' + response.message +
                                '</li>');

                        } else {
                            //Switch to the next tab
                            $('a[href*="result-b2"] span').trigger("click");

                        }
                    })
                    .fail(function(response) {
                        //
                    });
            });

            // Save all changes to the array on Select2 change
            var app_array = {};
            $(document).on("select2:select", ".appDomainSelect2", function() {
                var name = "name";
                app_array[$.trim($(this).closest("tr").attr('id'))] = $(this).val();

            });

        });

        // This function receives IDs and URL for items to be deleted and passes them to the Confirm Delete Modal
        function confirmAppDeleteAction(url, setting_id = '') {

            dataObj = new Object();
            dataObj.url = url;

            dataObj.setting_id = setting_id;
            $('#confirmAppDeleteModal').data(dataObj).modal('show');

        }

        //This function sends AJAX request to delete selected items from list pages
        function performConfirmedAppDeleteAction() {
            var setting_id = $("#confirmAppDeleteModal").data("setting_id");
            $('#confirmAppDeleteModal').modal('hide');
            $('.loading').show();


            var url = $("#confirmAppDeleteModal").data("url");
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
                    // console.log(response);
                    if (response.error) {
                        $('.loading').hide();
                        printErrorMsg(response.error);
                    } else {
                        $('.loading').hide();
                        // $('#sipCredentialsModal').modal("show");

                        // $('#sip_username').val(response.username);
                        // $('#sip_password').val(response.password);
                        // $('#sip_domain').val(response.domain);
                        $.NotificationApp.send("Success", response.message, "top-right", "#10c469", "success");
                    }
                })
                .fail(function(jqXHR, testStatus, error) {
                    // console.log(error);
                    $('#loader').hide();
                    printErrorMsg(error);

                });
        }

        //This function sends AJAX request to delete selected items from list pages
        function syncAppUsers(url, domain) {
            $('.loading').show();

            url = url.replace(':id', domain);

            $.ajax({
                    type: 'POST',
                    url: url,
                    cache: false,
                })
                .done(function(response) {
                    //console.log(response);
                    if (response.error) {
                        $('.loading').hide();
                        printErrorMsg(response.error);
                    } else {
                        $('.loading').hide();

                        $.NotificationApp.send("Success", response.success.message, "top-right", "#10c469", "success");
                    }
                })
                .fail(function(jqXHR, testStatus, error) {
                    // console.log(error);
                    $('.loading').hide();
                    printErrorMsg(error);

                });

        }
    </script>
@endpush
