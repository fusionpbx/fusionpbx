@extends('layouts.app', ['page_title' => 'Edit Domain Group'])

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('domaingroups.index') }}">Domain Groups</a></li>
                            @if ($domain_group->exists)
                                <li class="breadcrumb-item active">Edit Domain Group</li>
                            @else
                                <li class="breadcrumb-item active">Create New Domain Group</li>
                            @endif
                        </ol>
                    </div>
                    @if ($domain_group->exists)
                        <h4 class="page-title">Edit Domain Group ({{ $domain_group->group_name ?? '' }})</h4>
                    @else
                        <h4 class="page-title">Create New Domain Group</h4>
                    @endif
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card mt-3">
                    <div class="card-body">
                        <!-- Body Content-->
                        <div class="row">
                            <div class="col-lg-12">
                                @if ($domain_group->exists)
                                    <form method="POST" id="domain_group_form"
                                        action="{{ route('domaingroups.update', $domain_group) }}">
                                        @method('put')
                                    @else
                                        <form method="POST" id="domain_group_form"
                                            action="{{ route('domaingroups.store') }}">
                                @endif
                                @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="group_name" class="form-label">Group Name <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control" type="text"
                                                value="{{ $domain_group->group_name }}" placeholder="Enter group name"
                                                id="group_name" name="group_name" />
                                            <div class="text-danger error_message group_name_err"></div>
                                        </div>
                                    </div>
                                </div> <!-- end row -->

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="domain_select" class="form-label">Assign domains to this group <span
                                                    class="text-danger">*</label>
                                            <!-- Multiple Select -->
                                            <select class="select2 form-control select2-multiple" data-toggle="select2"
                                                multiple="multiple" data-placeholder="Choose ..." id="domain_select"
                                                name="domains[]">
                                                @foreach ($all_domains as $domain)
                                                    <option value="{{ $domain->domain_uuid }}"
                                                        @if (isset($assigned_domains) && $assigned_domains->contains($domain)) selected @endif>
                                                        @if (isset($domain->domain_description))
                                                            {{ $domain->domain_description }}
                                                        @else
                                                            {{ $domain->domain_name }}
                                                        @endif

                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="text-danger error_message reseller_domain_err"></div>
                                        </div>
                                    </div>
                                </div> <!-- end row -->

                                <div class="row mt-4">
                                    <div class="col-sm-12">
                                        <div class="text-sm-end">
                                            <input type="hidden" name="domain_group_uuid"
                                                value="{{ $domain_group->domain_group_uuid }}">
                                            {{-- <input type="hidden" name="contact_id" value="{{base64_encode($contact['contact_uuid'])}}"> --}}
                                            <a href="{{ Route('domaingroups.index') }}"
                                                class="btn btn-light me-2">Cancel</a>
                                            <button id="submitFormButton" class="btn btn-success"
                                                type="submit">Save</button>
                                            {{-- <button class="btn btn-success" type="submit">Save</button> --}}
                                        </div>
                                    </div> <!-- end col -->
                                </div>

                                </form>
                            </div>
                        </div> <!-- end row-->

                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col -->
        </div>
        <!-- end row-->

    </div> <!-- container -->
@endsection


@push('scripts')
    <script>
        var setting_validation;
        document.addEventListener('DOMContentLoaded', function() {

            $('#submitFormButton').on('click', function(e) {
                e.preventDefault();
                $('.loading').show();

                //Reset error messages
                $('.error_message').text("");

                $.ajax({
                        type: "POST",
                        url: $('#domain_group_form').attr('action'),
                        cache: false,
                        data: $("#domain_group_form").serialize(),
                    })
                    .done(function(response) {
                        $('.loading').hide();

                        if (response.error) {
                            printErrorMsg(response.error);

                        } else {
                            $.NotificationApp.send("Success", response.success.message, "top-right",
                                "#10c469", "success");
                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1000);
                        }

                    })
                    .fail(function(response) {
                        $('.loading').hide();
                        printErrorMsg(response.responseText);
                    });

            })

        });
    </script>
@endpush
