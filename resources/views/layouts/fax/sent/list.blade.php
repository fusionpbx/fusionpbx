@extends('layouts.partials.listing.layout', ["pageTitle"=> 'Sent Faxes', 'breadcrumbs' => [
    'Virtual Fax Machines' => 'faxes.index',
    'Sent Faxes' => ''
]])

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $files])
@endsection

@section('actionbar')
    @if ($permissions['delete'])
        <a href="javascript:confirmDeleteAction('{{ route('faxes.file.deleteSentFax', ':id') }}');" id="deleteMultipleActionButton" class="btn btn-danger btn-sm mb-2 me-2 disabled">Delete Selected</a>

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
        <th>From</th>
        <th>To</th>
        {{-- <th>View</th> --}}
        <th>Date</th>
        <th>Status</th>
        <th>Last attempt</th>
        <th>Retry Count</th>
        <th style="width: 125px;">Actions</th>
    </tr>
@endsection

@section('table-body')
    @if($files->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 9])
    @else
        @foreach ($files as $key=>$file)
            <tr id="id{{ $file->fax_queue_uuid  }}">
                <td>
                    @if ($permissions['delete'])
                        <div class="form-check">
                            <input type="checkbox" name="action_box[]" value="{{ $file->fax_queue_uuid }}" class="form-check-input action_checkbox">
                            <label class="form-check-label" >&nbsp;</label>
                        </div>
                    @endif
                </td>
                <td>
                    @if($file->fax_caller_id_name!='')
                        <span class="text-body fw-bold">{{ $file->fax_caller_id_name ?? ''}}</span><br />
                    @endif
                    @if($file->fax_caller_id_number!='')
                        <span class="text-body fw-bold ">{{ phone($file->fax_caller_id_number, "US", $national_phone_number_format) }}</span>
                    @endif
                </td>
                <td>
                    {{ phone($file->fax_number, "US", $national_phone_number_format) }}
                </td>
                {{-- <td>
                    {{ $file->fax_file_type }}
            </td> --}}
                <td>
                    <span class="text-body text-nowrap">{{ $file->fax_date->format('D, M d, Y ')}}</span>
                    <span class="text-body text-nowrap">{{ $file->fax_date->format('h:i:s A') }}</span>
                </td>
                <td>
                    @if ($file->fax_status == "sent")
                        <h5><span class="badge bg-success">Sent</span></h5>
                    @elseif($file->fax_status == "failed")
                        <h5><span class="badge bg-danger">Failed</span></h5>
                    @else
                        <h5><span class="badge bg-info">{{ ucfirst($file->fax_status) }}</span></h5>
                    @endif
                </td>
                <td>
                    @if ($file->fax_retry_date)
                        <span class="text-body text-nowrap">{{ $file->fax_retry_date->format('D, M d, Y ') }}</span>
                        <span class="text-body text-nowrap">{{ $file->fax_retry_date->format('h:i:s A') }}</span>
                    @endif
                </td>
                <td>
                    {{ $file->fax_retry_count }}
                </td>
                <td>
                    <div id="tooltip-container-actions">
                        @if($file->fax_status == 'waiting' or $file->fax_status == 'trying')
                            <a href="{{ route('faxes.file.updateStatus', [$file->fax_queue_uuid]) }}" class="action-icon">
                                <i class="mdi mdi-cancel" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Cancel trying"></i>
                            </a>
                        @else
                            <a href="{{ route('faxes.file.updateStatus', [$file->fax_queue_uuid, 'waiting']) }}" class="action-icon">
                                <i class="mdi mdi-restart" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Retry"></i>
                            </a>
                        @endif
                        @if($file->getFaxFile())
                            <a href="{{ route('downloadSentFaxFile', $file->getFaxFile()->fax_file_uuid ) }}" class="action-icon">
                                <i class="mdi mdi-download" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Download"></i>
                            </a>
                        @endif
                        @if ($permissions['delete'])
                            <a href="javascript:confirmDeleteAction('{{ route('faxes.file.deleteSentFax', ':id') }}','{{ $file->fax_queue_uuid }}');" class="action-icon">
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
@vite(["node_modules/daterangepicker/daterangepicker.css", "node_modules/daterangepicker/daterangepicker.js?commonjs-entry"])
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

        $('#clearSearch').on('click', function () {
            $('#search').val('');
            var location = window.location.protocol +"//" + window.location.host + window.location.pathname;
            location += '?page=1';
            window.location.href = location;
        })

        $('.action_checkbox').on('change',function(){
            if(!$(this).is(':checked')){
                $('#selectallCheckbox').prop('checked',false);
            } else {
                if(checkAllbox()){
                    $('#selectallCheckbox').prop('checked',true);
                }
            }
        });

        $('#status-select').on('change', function () {
            var location = window.location.protocol +"//" + window.location.host + window.location.pathname;
            location += '?page=1&' + $('#filterForm').serialize();
            window.location.href = location;
        })

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
