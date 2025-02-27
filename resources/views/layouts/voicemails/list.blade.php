@extends('layouts.partials.listing.layout', ['pageTitle' => 'Voicemails', 'breadcrumbs' => []])

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $voicemails])
@endsection

@section('actionbar')
    @if ($permissions['add_new'])
        <a href="{{ route('voicemails.create') }}" class="btn btn-sm btn-success mb-2 me-2">
            <i class="uil uil-plus me-1"></i>
            Add New
        </a>
    @endif
    @if ($permissions['delete'])
        <a href="javascript:confirmDeleteAction('{{ route('voicemails.destroy', ':id') }}');"
            id="deleteMultipleActionButton" class="btn btn-danger btn-sm mb-2 me-2 disabled">Delete Selected</a>
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
        <th>Voicemail ID</th>
        <th>Email Address</th>
        <th>Messages</th>
        <th>Enabled</th>
        <th>Description</th>
        <th style="width: 125px;">Action</th>
    </tr>
@endsection

@section('table-body')
    @if ($voicemails->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 9])
    @else
        @foreach ($voicemails as $key => $voicemail)
            <tr id="id{{ $voicemail->voicemail_uuid }}">
                <td>
                    @if ($permissions['delete'])
                        <div class="form-check">
                            <input type="checkbox" name="action_box[]" value="{{ $voicemail->voicemail_uuid }}"
                                class="form-check-input action_checkbox">
                            <label class="form-check-label">&nbsp;</label>
                        </div>
                    @endif
                </td>
                <td>
                    @if ($permissions['edit'])
                        <a href="{{ route('voicemails.edit', $voicemail) }}" class="text-body fw-bold">
                            {{ $voicemail->voicemail_id }}
                        </a>
                    @else
                        <span class="text-body fw-bold">
                            {{ $voicemail->voicemail_id }}
                        </span>
                    @endif
                </td>
                <td>
                    {{ $voicemail['voicemail_mail_to'] }}
                </td>

                <td>
                    @if ($permissions['voicemail_message_view'])
                        <a href="{{ route('voicemails.messages.index', $voicemail) }}" class="text-body fw-bold">
                            Show Messages ({{ $voicemail->messages()->count() }})
                        </a>
                    @else
                        {{ $voicemail->messages()->count() }}
                    @endif
                </td>

                <td>
                    @if ($voicemail['voicemail_enabled'] == 'true')
                        <h5><span class="badge bg-success"></i>Enabled</span></h5>
                    @else
                        <h5><span class="badge bg-warning">Disabled</span></h5>
                    @endif
                </td>

                <td>
                    {{ $voicemail['voicemail_description'] }}
                </td>


                <td>
                    {{-- Action Buttons --}}
                    <div id="tooltip-container-actions">
                        @if ($permissions['edit'])
                            <a href="{{ route('voicemails.edit', $voicemail) }}" class="action-icon" title="Edit">
                                <i class="mdi mdi-lead-pencil" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit voicemail"></i>
                            </a>
                        @endif

                        @if ($permissions['delete'])
                            <a href="javascript:confirmDeleteAction('{{ route('voicemails.destroy', ':id') }}','{{ $voicemail->voicemail_uuid }}');"
                                class="action-icon">
                                <i class="mdi mdi-delete" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
                            </a>
                        @endif
                    </div>
                    {{-- End of action buttons --}}
                </td>
            </tr>
        @endforeach
    @endif
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
