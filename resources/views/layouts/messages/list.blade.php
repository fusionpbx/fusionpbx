@extends('layouts.partials.listing.layout', ['pageTitle' => 'Messages', 'breadcrumbs' => []])

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $messages])
@endsection

@section('actionbar')
    @if ($permissions['delete'] ?? false)
        <a href="javascript:confirmDeleteAction('{{ route('faxes.file.deleteFaxLog', ':id') }}');"
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
        <div class="col-auto">
            <div class="d-flex align-items-center">
                <label for="status-select" class="me-2">Period</label>
                <input type="text" style="width: 298px" class="form-control date" id="period" name="period"
                    value="{{ $searchPeriod }}" />
            </div>
        </div>
        <div class="col-auto">
            <div class="d-flex align-items-center">
                <label for="status-select" class="me-2">Status</label>
                <select class="form-select" name="status" id="status-select">
                    @foreach ($statuses as $key => $status)
                        <option value="{{ $key }}" @if ($selectedStatus == $key) selected @endif>
                            {{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-none"><input type="submit" name="submit" value="Ok" /></div>
    </form>
@endsection

@section('table-head')
    <tr>
        <th style="width: 20px;">
            @if ($permissions['delete'] ?? false)
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="selectallCheckbox">
                    <label class="form-check-label" for="selectallCheckbox">&nbsp;</label>
                </div>
            @endif
        </th>
        <th>Date</th>
        <th>In/Out</th>
        <th>Source</th>
        <th>Destination</th>
        <th>Message</th>
        <th>Type</th>
        <th>Status</th>
        <th style="width: 125px;">Action</th>
    </tr>
@endsection

@section('table-body')
    @if ($messages->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 13])
    @else
        @foreach ($messages as $key => $message)
            <tr>
                <td>
                    @if ($permissions['delete'] ?? false)
                        <div class="form-check">
                            <input type="checkbox" name="action_box[]" value="{{ $message->message_uuid }}"
                                class="form-check-input action_checkbox">
                            <label class="form-check-label">&nbsp;</label>
                        </div>
                    @endif
                </td>
                {{-- <td><a href="" class="text-body fw-bold">{{ \Carbon\Carbon::parse($message['created_at'])->format('m/d/Y, h:i:s A') }}</a> </td> --}}
                <td>
                    <a href="" class="text-body fw-bold text-nowrap">{{ $message->date->format('D, M d, Y ') }}</a>
                    <a href="" class="text-body fw-bold text-nowrap">{{ $message->date->format('h:i:s A') }}</a>
                </td>

                <td>
                    {{ $message['direction'] }}
                </td>
                <td class="text-nowrap">
                    {{ $message['source'] }}
                </td>
                <td class="text-nowrap">
                    {{ $message['destination'] }}
                </td>
                <td>
                    {{ $message['message'] }}
                </td>
                <td>
                    {{ $message['type'] }}
                </td>
                <td>
                    @switch($message['status'])
                        @case('success')
                            <h5><span class="badge bg-success">{{ $message['status'] }}</span></h5>
                            @break

                        @case('emailed')
                            <h5><span class="badge bg-primary">{{ $message['status'] }}</span></h5>
                            @break

                        @default
                            <h5><span class="badge bg-warning">{{ $message['status'] }}</span></h5>
                    @endswitch
                </td>
                <td>
                    <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-eye"></i></a>
                    <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-square-edit-outline"></i></a>
                    <a href="javascript:void(0);" class="action-icon"> <i class="mdi mdi-delete"></i></a>
                </td>
            </tr>
        @endforeach
    @endif
@endsection

@push('scripts')
@vite(["node_modules/daterangepicker/daterangepicker.scss", "node_modules/daterangepicker/daterangepicker.js?commonjs-entry"])
<script>
    document.addEventListener('DOMContentLoaded', function() {

        $('#period').daterangepicker({
            timePicker: true,
            startDate: '{{ $searchPeriodStart }}',//moment().subtract(1, 'months').startOf('month'),
            endDate: '{{ $searchPeriodEnd }}',//moment().endOf('day'),
            locale: {
                format: 'MM/DD/YY hh:mm A'
            }
        }).on('apply.daterangepicker', function(e) {
            var location = window.location.protocol +"//" + window.location.host + window.location.pathname;
            location += '?page=1&' + $('#filterForm').serialize();
            window.location.href = location;
        });
    });

</script>
@endpush
