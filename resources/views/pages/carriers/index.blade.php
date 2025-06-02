@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-bridge mr-2"></i> {{__('Carriers Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2" role="carrier" aria-label="Carriers actions">
                    @can('carrier_add')
                    <a href="{{ route('carriers.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:carriers-table/>
        </div>
    </div>
</div>
@endsection

