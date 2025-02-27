@extends('layouts.app', ['page_title' => 'Users'])

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">Users</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-xl-4">
                                <label class="form-label">Showing {{ $users->count() ?? 0 }} results for Users</label>
                            </div>
                            <div class="col-xl-8">
                                <div class="text-xl-end mt-xl-0 mt-2">
                                    @if ($permissions['add_new'])
                                        <a href="{{ route('users.create') }}" class="btn btn-success mb-2 me-2">
                                            <i class="mdi mdi-plus-circle me-1"></i>
                                            Add New
                                        </a>
                                    @endif
                                    @if ($permissions['delete'])
                                        <a href="javascript:confirmDeleteAction('{{ route('users.destroy', ':id') }}');"
                                            id="deleteMultipleActionButton" class="btn btn-danger mb-2 me-2 disabled">
                                            Delete Selected
                                        </a>
                                    @endif
                                    {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
                                </div>
                            </div><!-- end col-->
                        </div>

                        <div class="table-responsive">
                            <table class="table table-centered mb-0" id="user_list">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20px;">
                                            @if ($permissions['delete'])
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="selectallCheckbox">
                                                    <label class="form-check-label" for="selectallCheckbox">&nbsp;</label>
                                                </div>
                                            @endif
                                        </th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Roles</th>
                                        <th>Enabled</th>
                                        <th style="width: 125px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($users as $key => $user)
                                        <tr id="id{{ $user->user_uuid }}">
                                            <td>
                                                @if ($permissions['delete'])
                                                    <div class="form-check">
                                                        <input type="checkbox" name="action_box[]"
                                                            value="{{ $user->user_uuid }}"
                                                            class="form-check-input action_checkbox">
                                                        <label class="form-check-label">&nbsp;</label>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($permissions['edit'])
                                                    <a href="{{ route('users.edit', $user) }}" class="text-body fw-bold">
                                                        @if ($user->user_adv_fields)
                                                            {{ $user->user_adv_fields->first_name }}
                                                            {{ $user->user_adv_fields->last_name }}
                                                        @else
                                                            {{ $user->username }}
                                                        @endif
                                                    </a>
                                                @else
                                                    <span class="text-body fw-bold">
                                                        @if ($user->user_adv_fields)
                                                            {{ $user->user_adv_fields->first_name }}
                                                            {{ $user->user_adv_fields->last_name }}
                                                        @else
                                                            {{ $user->username }}
                                                        @endif
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $user['user_email'] }}
                                            </td>

                                            <td>
                                                @foreach ($user->groups() as $group)
                                                    <span
                                                        class="badge bg-primary"></i>{{ ucfirst($group->group_name) }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if ($user['user_enabled'] == 'true')
                                                    <h5><span class="badge bg-success"></i>Enabled</span></h5>
                                                @else
                                                    <h5><span class="badge bg-warning">Disabled</span></h5>
                                                @endif
                                            </td>
                                            <td>

                                                {{-- Action Buttons --}}
                                                <div id="tooltip-container-actions">
                                                    @if ($permissions['edit'])
                                                        <a href="{{ route('users.edit', $user) }}" class="action-icon"
                                                            title="Edit">
                                                            <i class="mdi mdi-lead-pencil"
                                                                data-bs-container="#tooltip-container-actions"
                                                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                                title="Edit user"></i>
                                                        </a>
                                                    @endif

                                                    <a href="javascript:confirmPasswordResetAction('{{ $user->user_email }}');"
                                                        class="action-icon">
                                                        <i class="mdi mdi-account-key-outline"
                                                            data-bs-container="#tooltip-container-actions"
                                                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                            title="Reset Password"></i>
                                                    </a>
                                                    @if ($permissions['delete'])
                                                        <a href="javascript:confirmDeleteAction('{{ route('users.destroy', ':id') }}','{{ $user->user_uuid }}');"
                                                            class="action-icon">
                                                            <i class="mdi mdi-delete"
                                                                data-bs-container="#tooltip-container-actions"
                                                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                                title="Delete"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                                {{-- End of action buttons --}}
                                            </td>
                                        </tr>
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
@endsection


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            localStorage.removeItem('activeTab');

            $('#selectallCheckbox').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.action_checkbox').prop('checked', true);
                } else {
                    $('.action_checkbox').prop('checked', false);
                }
            });

            $('.action_checkbox').on('change', function() {
                if (!$(this).is(':checked')) {
                    $('#selectallCheckbox').prop('checked', false);
                } else {
                    if (checkAllbox()) {
                        $('#selectallCheckbox').prop('checked', true);
                    }
                }
            });
        });

        function checkAllbox() {
            var checked = true;
            $('.action_checkbox').each(function(key, val) {
                if (!$(this).is(':checked')) {
                    checked = false;
                }
            });
            return checked;
        }

        function confirmPasswordResetAction(user_email) {
            $('#confirmPasswordResetModal').data("user_email", user_email).modal('show');

        }

        function performConfirmedPasswordResetAction() {
            var user_email = $("#confirmPasswordResetModal").data("user_email");
            $('#confirmPasswordResetModal').modal('hide');

            $.ajax({
                    type: 'POST',
                    url: "{{ route('users.password.email') }}",
                    cache: false,
                    data: {
                        email: user_email
                    },
                    headers: {
                        'Accept': 'application/json', // Explicitly expect JSON response
                    },
                })
                .done(function(response) {

                    if (response.error) {
                        $.NotificationApp.send("Warning", response.message, "top-right", "#ff5b5b", "error");

                    } else {
                        $.NotificationApp.send("Success", response.message, "top-right", "#10c469", "success");
                        //$(this).closest('tr').fadeOut("fast");
                    }
                })
                .fail(function(jqXHR, testStatus, error) {
                    console.log(error);
                    $.NotificationApp.send("Warning", error, "top-right", "#ff5b5b", "error");
                });
        }


        function checkSelectedBoxAvailable() {
            var has = false;
            $('.action_checkbox').each(function(key, val) {
                if ($(this).is(':checked')) {
                    has = true;
                }
            });
            return has;
        }
    </script>
@endpush
