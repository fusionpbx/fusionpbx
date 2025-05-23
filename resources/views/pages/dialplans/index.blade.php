@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-dialplan mr-2"></i> {{__('Dialplans Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2 " role="dialplan" aria-label="Dialplan actions">
	                @if($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4")
                        @can('inbound_route_add')
                        <a href="{{ route('dialplans.inbound.create', ['app_uuid' => $app_uuid]) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                        </a>
                        @endcan
                    @else
                        @can('dialplan_add')
                        <a href="{{ route('dialplans.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                        </a>
                        @endcan
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:dialplans-table :app_uuid="$app_uuid" :context="$context" :show="$show" />
        </div>
    </div>
</div>
@endsection

