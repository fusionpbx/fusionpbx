@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Extension Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2 " role="group" aria-label="Group actions">

                    @can('extension_import')
                    <a href="{{ route('extensions.import') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-download" aria-hidden="true"></i> {{__('Import')}}
                    </a>
                    @endcan

                    @can('extension_export')
                    <a href="{{ route('extensions.export') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-upload" aria-hidden="true"></i> {{__('Export')}}
                    </a>
                    @endcan
                    
                    @can('extension_all')
                    <a href="{{ route('extensions.index', ['show_all' => 1]) }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-globe" aria-hidden="true"></i> {{ __('Show All') }}
                    </a>
                    @endcan


                    <a href="{{ route('extensions.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>

                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:extension-table/>
        </div>
    </div>
</div>
@endsection

