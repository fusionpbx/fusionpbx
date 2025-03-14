@extends('layouts.partials.listing.layout', ["pageTitle"=> 'Fax Logs', 'breadcrumbs' => [
    'Virtual Fax Machines' => 'faxes.index',
    'Fax Logs' => ''
]])

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $logs])
@endsection

@section('actionbar')
    @if ($permissions['delete'])

        <a href="javascript:confirmDeleteAction('{{ route('faxes.file.deleteFaxLog', ':id') }}');" id="deleteMultipleActionButton" class="btn btn-danger btn-sm mb-2 me-2 disabled">Delete Selected</a>

    @endif
    {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
@endsection

@section('searchbar')
    <form id="filterForm" method="GET" action="{{url()->current()}}?page=1" class="row gy-2 gx-2 align-items-center justify-content-xl-start justify-content-between">
        <div class="col-auto">
            <label for="search" class="visually-hidden">Search</label>
            <div class="input-group input-group-merge">
                <input type="search" class="form-control" name="search" id="search" value="{{ $searchString }}" placeholder="Search..." />
                <input type="button" class="btn btn-light" name="clear" id="clearSearch" value="Clear" />
            </div>
        </div>
        <div class="col-auto">
            <div class="d-flex align-items-center">
                <label for="status-select" class="me-2">Period</label>
                <input type="text" style="width: 298px" class="form-control date" id="period" name="period" value="{{ $searchPeriod }}" />
            </div>
        </div>
        <div class="col-auto">
            <div class="d-flex align-items-center">
                <label for="status-select" class="me-2">Status</label>
                <select class="form-select" name="status" id="status-select">
                    @foreach ($statuses as $key => $status)
                        <option value="{{ $key }}" @if ($selectedStatus == $key) selected @endif>{{ $status }}</option>
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
            @if ($permissions['delete'])
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="selectallCheckbox">
                    <label class="form-check-label" for="selectallCheckbox">&nbsp;</label>
                </div>
            @endif
        </th>
        <th>Date</th>
        <th>Status</th>
        <th>Code</th>
        <th>Result</th>
        <th>File</th>
        <th>ECM</th>
        <th>Local Station ID</th>
        <th>Bad Rows</th>
        <th>Transfer Rate</th>
        <th>Retry</th>
        <th>Destination</th>
        <th>Action</th>
    </tr>
@endsection

@section('table-body')
    @if($logs->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 13])
    @else
        @foreach ($logs as $key => $log)
            <tr id="id{{ $log->fax_log_uuid  }}">
                <td>
                    @if ($permissions['delete'])
                        <div class="form-check">
                            <input type="checkbox" name="action_box[]" value="{{ $log->fax_log_uuid }}" class="form-check-input action_checkbox">
                            <label class="form-check-label" >&nbsp;</label>
                        </div>
                    @endif
                </td>
                <td>
                    <span class="text-body text-nowrap">{{ $log->fax_date->format('D, M d, Y ')}}</span>
                    <span class="text-body text-nowrap">{{ $log->fax_date->format('h:i:s A') }}</span>
                </td>
                <td>
                    @if ($log->fax_success)
                        <h5><span class="badge bg-success">Success</span></h5>
                    @else
                        <h5><span class="badge bg-danger">Failed</span></h5>
                    @endif
                </td>
                <td>{{ $log->fax_result_code }}</td>
                <td>{{ $log->fax_result_text }}</td>
                <td>{{ substr(basename($log->fax_file), 0, (strlen(basename($log->fax_file)) -4)) }}</td>
                <td>{{ $log->fax_ecm_used }}</td>
                <td>
                    <span class="text-body text-nowrap">{{ $log->fax_local_station_id }}</span>
                </td>
                <td>{{ $log->fax_bad_rows }}</td>
                <td>{{ $log->fax_transfer_rate }}</td>
                <td>{{ $log->fax_retry_attempts }}</td>
                <td>
                    <span class="text-body text-nowrap">{{ $log->fax_uri }}</span>
                </td>
                <td>
                    <div id="tooltip-container-actions">
                        @if ($permissions['delete'])
                            <a href="javascript:confirmDeleteAction('{{ route('faxes.file.deleteFaxLog', ':id') }}','{{ $log->fax_log_uuid }}');" class="action-icon">
                                <i class="mdi mdi-delete" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    @endif
@endsection


@push('scripts')
@vite(["node_modules/daterangepicker/daterangepicker.scss", "node_modules/daterangepicker/daterangepicker.js?commonjs-entry"])
<script>
    document.addEventListener('DOMContentLoaded', function() {

        localStorage.removeItem('activeTab');

        $('#selectallCheckbox').on('change',function(){
            if($(this).is(':checked')){
                $('.action_checkbox').prop('checked',true);
            } else {
                $('.action_checkbox').prop('checked',false);
            }
        });

        $('.action_checkbox').on('change',function(){
            if(!$(this).is(':checked')){
                $('#selectallCheckbox').prop('checked',false);
            } else {
                if(checkAllbox()){
                    $('#selectallCheckbox').prop('checked',true);
                }
            }
        });

        $('#clearSearch').on('click', function () {
            $('#search').val('');
            var location = window.location.protocol +"//" + window.location.host + window.location.pathname;
            location += '?page=1';
            window.location.href = location;
        });

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

    function checkAllbox(){
        var checked=true;
        $('.action_checkbox').each(function(key,val){
            if(!$(this).is(':checked')){
                checked=false;
            }
        });
        return checked;
    }

    function checkSelectedBoxAvailable(){
        var has=false;
        $('.action_checkbox').each(function(key,val){
        if($(this).is(':checked')){
            has=true;
        }});
        return has;
    }



</script>
@endpush
