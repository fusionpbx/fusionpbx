@extends('layouts.partials.listing.layout', [
    'pageTitle' => 'Contacts',
    'breadcrumbs' => [
        'Home' => 'dashboard',
        'Contacts' => '',
    ],
])

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $contactPhones])
@endsection

@section('actionbar')
    @if ($permissions['import'])
        <button type="button" class="btn btn-sm btn-outline-info mb-2 me-2" data-bs-toggle="modal"
            data-bs-target="#contact-upload-modal"><i class="uil uil-upload me-1"></i>Import</button>
    @endif

    @if ($permissions['delete'])
        <a href="javascript:confirmDeleteAction('{{ route('contacts.destroy', ':id') }}');" id="deleteMultipleActionButton"
            class="btn btn-danger btn-sm mb-2 me-2 disabled">Delete Selected</a>
    @endif
    {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
@endsection

@section('searchbar')
    <form id="filterForm" method="GET" action="{{ url()->current() }}?page=1"
        class="row gy-2 gx-2 align-items-center justify-content-xl-start justify-content-between">
        <div class="col-auto">
            <label for="search" class="visually-hidden">Search</label>
            <div class="input-group input-group-merge">
                <input type="search" class="form-control" name="search" id="search" value="{{ $searchString }}"
                    placeholder="Search..." />
                <input type="button" class="btn btn-light" name="clear" id="clearSearch" value="Clear" />
            </div>
        </div>

        <div class="d-none"><input type="submit" name="submit" value="Ok" /></div>
    </form>
@endsection

@section('table-head')
    <tr>
        <th style="width: 20px;">
            @if ($permissions['delete'])
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="selectallCheckbox">
                    <label class="form-check-label" for="selectallCheckbox">&nbsp;</label>
                </div>
            @endif
        </th>
        <th>Organization</th>
        <th>Phone Number</th>
        <th>Speed Dial</th>
        <th>Assigned Users</th>
        <th style="width: 125px;">Actions</th>
    </tr>
@endsection

@section('table-body')
    @if ($contactPhones->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 9])
    @else
        @foreach ($contactPhones as $key => $contactPhone)
            <tr id="id{{ $contactPhone->contact_phone_uuid }}">
                <td>
                    @if ($permissions['delete'])
                        <div class="form-check">
                            <input type="checkbox" name="action_box[]" value="{{ $contactPhone->contact_phone_uuid }}"
                                class="form-check-input action_checkbox">
                            <label class="form-check-label">&nbsp;</label>
                        </div>
                    @endif
                </td>
                <td>
                    <span class="text-body fw-bold ">{{ $contactPhone->contact->contact_organization ?? '' }}</span>
                </td>
                <td>{{ $contactPhone->phone_number ?? '' }}</td>
                <td>
                    {{ $contactPhone->phone_speed_dial ?? '' }}
                </td>

                <td>
                    {{-- @dd($contactPhone->contact->contact_users); --}}
                    @foreach ($contactPhone->contact->contact_users as $contact_user)
                        @if ($contact_user->user)
                            <span class="badge bg-light text-dark">{{ $contact_user->user->username }}</span>
                        @endif
                    @endforeach
                </td>

                <td>
                    <div id="tooltip-container-actions">
                        @if ($permissions['delete'])
                            <a href="javascript:confirmDeleteAction('{{ route('contacts.destroy', ':id') }}','{{ $contactPhone->contact_phone_uuid }}');"
                                class="action-icon">
                                <i class="mdi mdi-delete" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    @endif
@endsection

@section('includes')
    @include('layouts.contacts.contactsUploadModal')
    @include('layouts.contacts.contactsUploadResultModal')
@endsection

@push('scripts')
    @vite(['resources/js/ui/component.fileupload.js', 'resources/js/hyper-syntax.js'])
    <script>
        document.addEventListener('dropzoneSuccessEvent', function() {
            // Handle success event here
            $('#contact-upload-modal').modal("hide");
            $('#contactsUploadResultModal').modal("show");
            $('#dropzoneSuccess').show();
            $('#dropzoneError').hide();

            // Successful Notification
            $.NotificationApp.send("Success", "Extensions have been successfully imported", "top-right", "#10c469",
                "success");

            setTimeout(function() {
                window.location.reload();
            }, 2000);
        });

        document.addEventListener('dropzoneErrorEvent', function(event) {
            // Handle error event here
            $('#contact-upload-modal').modal("hide");
            $('#contactsUploadResultModal').modal("show");
            $('#dropzoneError').html(event.detail.errorMessage);
            $('#dropzoneError').show();
            $('#dropzoneSuccess').hide();

            // Warning Notification
            $.NotificationApp.send("Warning", event.detail.errorMessage, "top-right", "#ff5b5b", "error");
        });


        document.addEventListener('DOMContentLoaded', function() {

            localStorage.removeItem('activeTab');

            $('#selectallCheckbox').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.action_checkbox').prop('checked', true);
                } else {
                    $('.action_checkbox').prop('checked', false);
                }
            });

            $('#clearSearch').on('click', function() {
                $('#search').val('');
                var location = window.location.protocol + "//" + window.location.host + window.location
                    .pathname;
                location += '?page=1';
                window.location.href = location;
            })

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
