@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Geteway Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2 " role="group" aria-label="Group actions">
                    <a href="" class="btn btn-primary btn-sm">
                        <i class="fa fa-play" aria-hidden="true"></i> {{__('Start')}}
                    </a>

                    <a href="" class="btn btn-primary btn-sm">
                        <i class="fa fa-stop" aria-hidden="true"></i> {{__('Stop')}}
                    </a>

                    @can('gateway_add')
                    <a href="{{ route('gateways.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan
                    

                    <a href="{{ route('gateways.index', ['show' => 'all']) }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-globe" aria-hidden="true"></i> {{ __('Show All') }}
                    </a>                   

                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:gateways-table/>
          </div>
    </div>
</div>

@endsection
