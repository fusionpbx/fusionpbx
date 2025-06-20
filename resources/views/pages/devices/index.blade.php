@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i>  {{__('Devices Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2 " role="group" aria-label="Group actions">
                @can('device_all')
                <a href="{{ route('devices.index', ['show_all' => 1]) }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-globe" aria-hidden="true"></i> {{ __('Show All') }}
                </a>
                @endcan

                <a href="{{ route('devices_vendors.index') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-fax" aria-hidden="true"></i> {{__('Vendors')}}
                </a>

                @can('device_import')
                <a href="{{route('devices_profiles.index')}}" class="btn btn-primary btn-sm">
                    <i class="fa fa-clone" aria-hidden="true"></i> {{__('Profiles')}}
                </a>
                @endcan

                @can('device_import')
                <a href="{{ route('devices.import') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-download" aria-hidden="true"></i> {{__('Import')}}
                </a>
                @endcan

                @can('device_export')
                <a href="{{ route('devices.export') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-upload" aria-hidden="true"></i> {{__('Export')}}    
                </a>
                @endcan

                <div class="d-flex gap-2 " role="menu" aria-label="Menu actions">
                    <a href="{{ route('devices.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                </div>
            </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:device-table/>
        </div>
    </div>
</div>
@endsection
