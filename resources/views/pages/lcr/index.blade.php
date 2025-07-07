@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('LCR Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2" role="lcr" aria-label="LCR actions">
                    @can('lcr_add')
                    <a href="{{ route('lcr.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:lcr-table/>
        </div>
    </div>
</div>
@endsection

