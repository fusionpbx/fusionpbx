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

                <div class="d-flex gap-2 " role="menu" aria-label="Menu actions">
                    <a href="{{ route('devices_vendors.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                </div>
            </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:device-vendor-table/>
        </div>
    </div>
</div>
@endsection
