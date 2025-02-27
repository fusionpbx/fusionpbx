@extends('layouts.partials.listing.layout', ['pageTitle' => 'Ring Groups'])

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $ringGroups])
@endsection

@section('actionbar')
    @if ($permissions['add'])
        <a href="{{ route('ring-groups.create') }}" class="btn btn-success me-2">
            <i class="uil uil-plus me-1"></i>
            Add New
        </a>
    @endif
    @if ($permissions['delete'])
        <a href="javascript:confirmDeleteAction('{{ route('ring-groups.destroy', ':id') }}');" id="deleteMultipleActionButton"
            class="btn btn-danger disabled">
            Delete Selected
        </a>
    @endif
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
        <th>Name</th>
        <th>Extension</th>
        <th>Members</th>
        <th>Description</th>
        <!--th>Status</th-->
        <th>Action</th>
    </tr>
@endsection

@section('table-body')
    @if ($ringGroups->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 7])
    @else
        @foreach ($ringGroups as $key => $ringGroup)
            <tr id="id{{ $ringGroup->ring_group_uuid }}">
                <td>
                    @if ($permissions['delete'])
                        <div class="form-check">
                            <input type="checkbox" name="action_box[]" value="{{ $ringGroup->ring_group_uuid }}"
                                class="form-check-input action_checkbox">
                            <label class="form-check-label">&nbsp;</label>
                        </div>
                    @endif
                </td>
                <td>
                    @if ($permissions['edit'])
                        <a href="{{ route('ring-groups.edit', $ringGroup) }}" class="text-body fw-bold">
                            {{ $ringGroup->ring_group_name }}
                        </a>
                    @else
                        <span class="text-body fw-bold">
                            {{ $ringGroup->ring_group_name }}
                        </span>
                    @endif

                    @if ($ringGroup['ring_group_forward_enabled'] == 'true')
                        <small><span class="badge badge-outline-primary">FWD</span></small>
                    @endif
                </td>
                <td>
                    {{ $ringGroup->ring_group_extension }}
                </td>
                <td>
                    @foreach ($ringGroup->getGroupDestinations() as $destination)
                    <span class="badge bg-light text-dark">{{ $destination->destination_number }}</span>
                    {{-- <span class="badge badge-outline-primary">{{ $destination->destination_number }}</span> --}}
                    @endforeach
                </td>

                <td>
                    {{ $ringGroup->ring_group_description }}
                </td>

                <!--td>
                                        {{ $ringGroup->ring_group_enabled }}
                                    </td-->
                <td>
                    <div id="tooltip-container-actions">
                        @if ($permissions['edit'])
                            <a href="{{ route('ring-groups.edit', $ringGroup) }}" class="action-icon" title="Edit">
                                <i class="mdi mdi-lead-pencil" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit"></i>
                            </a>
                        @endif
                        @if ($permissions['delete'])
                            <a href="javascript:confirmDeleteAction('{{ route('ring-groups.destroy', ':id') }}','{{ $ringGroup->ring_group_uuid }}');"
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
@endpush
