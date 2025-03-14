@extends('layouts.partials.listing.layout', ['pageTitle' => 'Virtual Fax Machines'])

@section('widgets')
    <div class="col-12">
        <div class="card widget-inline">
            <div class="card-body p-0">
                <div class="row g-0 align-items-center">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card rounded-0 shadow-none m-0">
                            <div class="card-body text-center">
                                {{-- <i class="mdi mdi-email-send-outline text-muted font-24"></i> --}}
                                <h3><span>{{ $totalSent }}</span></h3>
                                <p class="text-muted font-15 mb-0">Total faxes sent in the past month</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <div class="card rounded-0 shadow-none m-0 border-start border-light">
                            <div class="card-body text-center">
                                {{-- <i class="mdi mdi-email-receive-outline text-muted font-24"></i> --}}
                                <h3><span>{{ $totalReceived }}</span></h3>
                                <p class="text-muted font-15 mb-0">Total faxes received in the past month</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <div class="card rounded-0 shadow-none m-0 border-start border-light">
                            <div class="card-body text-center">
                                <i class="ri-group-line text-muted font-24"></i>
                                <h3><span>{{ $faxes->total() }}</span></h3>
                                <p class="text-muted font-15 mb-0">Total Fax Numbers</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3 ">
                        <div class="card rounded-0 shadow-none m-0 border-start border-light">
                            <div class="card-body text-center">
                                    <a href="{{ route('faxes.newfax') }}" button type="button" class="btn btn-primary rounded-pill">
                                        <span class=""> <i class="uil uil-plus me-1"></i><span>New Fax</span></span>
                                    </a>
                            </div>
                        </div>
                    </div>

                </div> <!-- end row -->
            </div>
        </div> <!-- end card-box-->
    </div>
@endsection

@section('cards')
<div class="row">
    @if (userCheckPermission("fax_sent_view"))
    <div class="col-xl-6 col-lg-6">
        <div class="card">
            <div class="d-flex card-header justify-content-between align-items-center">
                <h4 class="header-title">Recent Outbound Faxes</h4>
                {{-- <a href="{{ route("faxes.sent.list") }}" class="btn btn-sm btn-light">View More...</a> --}}
            </div>

            <div class="card-body pt-0">

                <div class="table-responsive">
                    <table class="table table-sm table-centered mb-0 font-14">
                        <thead class="table-light">
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($files->count() == 0)
                            @include('layouts.partials.listing.norecordsfound', ['colspan' => 9])
                        @else
                            @foreach ($files as $key=>$file)
                                <tr id="id{{ $file->fax_queue_uuid  }}">
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


                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div> <!-- end table-responsive-->
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    @endif

    @if (userCheckPermission("fax_inbox_view"))
    <div class="col-xl-6 col-lg-6">
        <div class="card">
            <div class="d-flex card-header justify-content-between align-items-center">
                <h4 class="header-title">Recent Inbound Faxes</h4>
            </div>

            <div class="card-body pt-0">

                <div class="table-responsive">
                    <table class="table table-sm table-centered mb-0 font-14">
                        <thead class="table-light">
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($inboundFaxes->count() == 0)
                            @include('layouts.partials.listing.norecordsfound', ['colspan' => 9])
                        @else
                            @foreach ($inboundFaxes as $key=>$file)
                                <tr id="id{{ $file->fax_file_uuid  }}">

                                    <td>
                                        @if($file->fax_caller_id_name!='')
                                            <span class="text-body fw-bold">{{ $file->fax_caller_id_name ?? ''}}</span><br />
                                        @endif
                                        @if($file->fax_caller_id_number!='')
                                            <span class="text-body fw-bold ">{{ $file->fax_caller_id_number ?? ''}}</span>
                                        @endif
                                    </td>
                                    {{-- <td>{{ $file->fax_file_type }}</td> --}}
                                    <td>
                                        {{ $file->fax->fax_caller_id_number ?? '' }}
                                    </td>
                                    <td>
                                        <span class="text-body text-nowrap">{{ $file->fax_date->format('D, M d, Y ')}}</span>
                                        <span class="text-body text-nowrap">{{ $file->fax_date->format('h:i:s A') }}</span>
                                    </td>

                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div> <!-- end table-responsive-->
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    @endif


</div>
@endsection

@section('pagination')
    @include('layouts.partials.listing.pagination', ['collection' => $faxes])
@endsection

@section('actionbar')
    @if ($permissions['add_new'])
        <a href="{{ route('faxes.create') }}" class="btn btn-sm btn-success mb-2 me-2"><i class="uil uil-plus me-1"></i>New
            Fax Machine</a>
    @endif
    @if ($permissions['delete'])
        <a href="javascript:confirmDeleteAction('{{ route('faxes.destroy', ':id') }}');" id="deleteMultipleActionButton"
            class="btn btn-danger btn-sm mb-2 me-2 disabled">Delete Selected</a>
    @endif
@endsection

@section('searchbar')
    <form id="filterForm" method="GET" action="{{ url()->current() }}?page=1"
        class="row gy-2 gx-2 align-items-center justify-content-xl-start justify-content-between">
        <div class="col-auto">
            <label for="search" class="visually-hidden">Search</label>
            <div class="input-group input-group-merge">
                <input type="search" class="form-control" name="search" id="search" value="{{ $searchString ?? '' }}"
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
        <th style="width: 425px;">Fax Email Address</th>
        <th class="text-center">Tools</th>
        <th style="width: 125px;">Action</th>
    </tr>
@endsection

@section('table-body')
    @if ($faxes->count() == 0)
        @include('layouts.partials.listing.norecordsfound', ['colspan' => 6])
    @else
        @foreach ($faxes as $key => $fax)
            <tr id="id{{ $fax->fax_uuid }}">
                <td>
                    @if ($permissions['delete'])
                        <div class="form-check">
                            <input type="checkbox" name="action_box[]" value="{{ $fax->fax_uuid }}"
                                class="form-check-input action_checkbox">
                            <label class="form-check-label">&nbsp;</label>
                        </div>
                    @endif
                </td>
                <td>
                    @if ($permissions['edit'])
                        <a href="{{ route('faxes.edit', $fax) }}" class="text-body fw-bold">
                            {{ $fax->fax_name }}
                        </a>
                    @else
                        <span class="text-body fw-bold">
                            {{ $fax->fax_name }}
                        </span>
                    @endif
                </td>
                <td>
                    {{ $fax->fax_extension }}
                </td>
                <td>
                    @foreach ($fax->fax_email as $email)
                        <span
                            class="m-1 mt-0 mb-2 btn btn-outline-primary rounded-pill btn-sm emailButton">{{ $email }}</span>
                    @endforeach
                </td>

                <td class="text-center">

                    <div id="tooltip-container-actions text-center">
                        @if ($permissions['fax_send'])
                            <a href="{{ route('faxes.newfax', ['id' =>  $fax->fax_uuid]) }}"
                                class="btn btn-sm btn-link text-muted ps-2" title="New">
                                <i class="mdi mdi-plus me-1" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="New">New Fax</i>
                            </a>
                        @endif

                        @if ($permissions['fax_inbox_view'])
                            <a href="{{ url('faxes/inbox/') . '/' . $fax->fax_uuid }}"
                                class="btn btn-sm btn-link text-muted ps-2">
                                <i class="mdi mdi-inbox me-1" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Inbox">Inbox</i>
                            </a>
                        @endif
                        @if ($permissions['fax_sent_view'])
                            <a href="{{ url('faxes/sent/') . '/' . $fax->fax_uuid }}"
                                class="btn btn-sm btn-link text-muted ps-2" title="Sent">
                                <i class="mdi mdi-send-check" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Sent">Sent</i>
                            </a>
                        @endif
                        @if ($permissions['fax_log_view'])
                            <a href="{{ url('faxes/log/') . '/' . $fax->fax_uuid }}"
                                class="btn btn-sm btn-link text-muted ps-2" title="Logs">
                                <i class="mdi mdi-fax" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Logs">Logs</i>
                            </a>
                        @endif
                        {{-- @if ($permissions['fax_active_view'])
                        <a href="" class="action-icon" title="Active">
                            <i class="mdi mdi-check" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Active"></i>
                        </a>
                        @endif --}}
                    </div>


                    {{-- <i class="mdi mdi-plus"></i>
                    <i class="mdi mdi-inbox"></i>
                    <i class="mdi mdi-send-check"></i>
                    <i class="mdi mdi-fax"></i> --}}
                </td>
                <td>
                    {{-- Action Buttons --}}
                    <div id="tooltip-container-actions">
                        @if ($permissions['edit'])
                            <a href="{{ route('faxes.edit', $fax) }}" class="action-icon" title="Edit">
                                <i class="mdi mdi-lead-pencil" data-bs-container="#tooltip-container-actions"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit"></i>
                            </a>
                        @endif

                        @if ($permissions['delete'])
                            <a href="javascript:confirmDeleteAction('{{ route('faxes.destroy', ':id') }}','{{ $fax->fax_uuid }}');"
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
@vite(["node_modules/daterangepicker/daterangepicker.scss", "node_modules/daterangepicker/daterangepicker.js?commonjs-entry"])

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

            $('#clearSearch').on('click', function() {
                $('#search').val('');
                var location = window.location.protocol + "//" + window.location.host + window.location
                    .pathname;
                location += '?page=1';
                window.location.href = location;
            })
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
