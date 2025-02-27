@extends('layouts.app', ['page_title' => 'Dashboard'])
@section('content')
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">Dashboard</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            @if (isSuperAdmin())
                <div class="col-sm-6 col-xl-4">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-primary-lighten text-primary rounded">
                                                    <i class="mdi mdi-office-building font-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mt-0 mb-1">Total Domains</h5>
                                            <p class="mb-0">{{ $domain_count }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-success-lighten text-success rounded">
                                                    <i class="mdi mdi-deskphone font-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mt-0 mb-1">Global Extensions</h5>
                                            <div class="float-end mb-0">Total: {{ $extension_count }}</div>
                                            <div class="mb-0">Online: {{ $global_reg_count }}</div>
                                            {{-- <p class="mb-0">{{ $global_reg_count }} out of {{ $extension_count }} online</p> --}}
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: {{ round(($global_reg_count / $extension_count) * 100) }}%"
                                                    aria-valuenow="{{ round(($global_reg_count / $extension_count) * 100) }}"
                                                    aria-valuemin="0" aria-valuemax="{{ $extension_count }}"></div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>



                <div class="col-sm-6 col-xl-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="flex-grow-1 mb-2">
                                <div class="float-end mb-0 font-13">
                                    {{ round($diskused, 2) . '/' . round($disktotal, 2) . ' GB (' . $diskusage . '%)' }}
                                </div>
                                <div class="mb-0">Disk Usage</div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar {{ $diskusagecolor }}" role="progressbar"
                                        style="width: {{ $diskusage }}%" aria-valuenow="{{ $diskusage }}"
                                        aria-valuemin="0" aria-valuemax="{{ $disktotal }}"></div>
                                </div>
                            </div>

                            <div class="flex-grow-1 mb-2">
                                <div class="float-end mb-0 font-13">
                                    {{ round($ramused, 2) . '/' . round($ramtotal, 2) . ' GB (' . $ramusage . '%)' }}</div>
                                <div class="mb-0">Memory Usage</div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar {{ $ramusagecolor }}" role="progressbar"
                                        style="width: {{ $ramusage }}%" aria-valuenow="{{ $ramusage }}"
                                        aria-valuemin="0" aria-valuemax="{{ $ramtotal }}"></div>
                                </div>
                            </div>

                            <div class="flex-grow-1 mb-3">
                                <div class="float-end mb-0 font-13">
                                    {{ round($swapused, 2) . '/' . round($swaptotal, 2) . ' GB (' . $swapusage . '%)' }}
                                </div>
                                <div class="mb-0">Swap Usage</div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar {{ $swapusagecolor }}" role="progressbar"
                                        style="width: {{ $swapusage }}%" aria-valuenow="{{ $swapusage }}"
                                        aria-valuemin="0" aria-valuemax="{{ $swaptotal }}"></div>
                                </div>
                            </div>

                            <div class="float-end mb-0"><span class="badge bg-primary">{{ $core_count }}</span></div>
                            <div class="mb-1">Core Count</div>
                            <div class="float-end mb-0">
                                <span class="badge bg-primary">{{ $cpuload['now'] }}</span>
                                <span class="badge bg-primary">{{ $cpuload['5min'] }}</span>
                                <span class="badge bg-primary">{{ $cpuload['15min'] }}</span>
                            </div>
                            <div class="mb-0">CPU Load</div>

                            {{-- </div> --}}
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center border border-light rounded p-2 mb-2">
                                <div class="flex-grow-1">
                                    <h5 class="fw-semibold my-0">Horizon Status</h5>
                                    <p class="mb-0">{{ ucfirst($horizonStatus) }}</p>
                                </div>
                                <div class="flex-shrink-0 me-2">
                                    @if ($horizonStatus == 'running')
                                        <svg viewBox="0 0 20 20" style="width: 2rem; height: 2rem; fill: #10c469;">
                                            <path
                                                d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z">
                                            </path>
                                        </svg>
                                    @elseif ($horizonStatus == 'paused')
                                        <svg viewBox="0 0 20 20" style="width: 2rem; height: 2rem; fill: #f9c851">
                                            <path
                                                d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM7 6h2v8H7V6zm4 0h2v8h-2V6z">
                                            </path>
                                        </svg>
                                    @elseif ($horizonStatus == 'inactive')
                                        <svg viewBox="0 0 20 20" style="width: 2rem; height: 2rem; fill: #ff5b5b">
                                            <path
                                                d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z">
                                            </path>
                                        </svg>
                                    @endif

                                </div>

                            </div>

                        </div> <!-- end card-->
                    </div>
                </div>
            @endif

        </div>

        <div class="row">
            @if ($permissions['extensions'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/extensions">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Extensions</h6>
                                    <i class="mdi mdi-phone-dial text-info" style="font-size: 2rem;"></i>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="my-2 text-info">{{ $extensions }}</h2>
                                    @if (isSuperAdmin())
                                        <div>
                                            <div class="mb-0 text-muted">
                                                <span class="text-success me-1"><i class="mdi mdi-arrow-up-bold"></i>
                                                    {{ $local_reg_count }}</span>
                                                <span class="text-nowrap">online</span>
                                            </div>
                                            <div class="mb-0 text-muted">
                                                <span class="text-danger me-1"><i class="mdi mdi-arrow-down-bold"></i>
                                                    {{ $extensions - $local_reg_count }}</span>
                                                <span class="text-nowrap">offline</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Extensions</span>
                                </p>
                            </div>
                        </a> <!-- end card-body-->
                    </div>
                </div>
            @endif


            @if ($permissions['phone_number'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/app/destinations/destinations.php">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Phone Numbers</h6>
                                    <i class="mdi mdi-phone text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-primary">{{ $phone_number }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Phone Numbers</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif

            @if ($permissions['ring_groups'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/ring-groups">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Ring Groups</h6>
                                    <i class="mdi mdi-account-group text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-danger">{{ $ring_groups }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Ring Groups</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif

            @if ($permissions['ivr'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/app/ivr_menus/ivr_menus.php">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Virtual Receptionist (IVR)
                                    </h6>
                                    <i class="mdi mdi-face-agent text-info" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-info">{{ $ivr }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total IVRs</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif

            @if ($permissions['users'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/users">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Users</h6>
                                    <i class="uil uil-users-alt text-success" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-success">{{ $users }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Users</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif

            @if ($permissions['time_conditions'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/app/time_conditions/time_conditions.php">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Schedules</h6>
                                    <i class="mdi mdi-calendar-clock text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-primary">{{ $time_conditions }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Time Conditions</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif
            @if ($permissions['devices'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/app/devices/devices.php">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Devices</h6>
                                    <i class="mdi mdi-devices text-success" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-success">{{ $devices }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Devices</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif
            @if ($permissions['cdr'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/call-detail-records">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Call History (CDRs)</h6>
                                    <i class="dripicons-time-reverse text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-danger">{{ $cdr }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Calls Today</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif
            @if ($permissions['voicemails'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/voicemails">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Voicemails</h6>
                                    <i class="mdi mdi-voicemail text-info" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-info">{{ $voicemails }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Voicemails</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif

            @if ($permissions['call_flow_view'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/app/call_flows/call_flows.php">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Call Flows</h6>
                                    <i class="mdi mdi-call-split text-success" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-success">{{ $call_flows }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Call Flows</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif

            @if ($permissions['fax_view'])
                <div class="col-md-4 col-lg-3 col-sm-6">
                    <div class="card">
                        <a href="/faxes">
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center"
                                    style="border-bottom: 1px solid #f1f3fa;">
                                    <h6 class="m-0" style="color: #6c757d;font-size:20px">Virtual Faxes</h6>
                                    <i class="mdi mdi-account-group text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <h2 class="my-2 text-danger">{{ $faxes }}</h2>
                                <p class="mb-0 text-muted">
                                    <span class="text-nowrap">Total Virtual Faxes</span>
                                </p>
                            </div> <!-- end card-body-->
                        </a>
                    </div>
                </div>
            @endif

            @stack('dashboard_tiles_end')


        </div>


    </div>
@endsection

@push('scripts')
    <script></script>
@endpush
