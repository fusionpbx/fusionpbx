@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-menu mr-2"></i> {{__('Devices Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2 " role="group" aria-label="Group actions">
                @can('device_all')
                <a href="{{ route('devices.index', ['show_all' => 1]) }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-globe" aria-hidden="true"></i> {{ __('Show All') }}
                </a>
                @endcan

                @can('device_import')
                <a href="" class="btn btn-primary btn-sm">
                    <i class="fa fa-clone" aria-hidden="true"></i> {{__('Perfiles')}}
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
