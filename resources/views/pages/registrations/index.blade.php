@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-phone mr-2"></i> {{__('SIP Registrations')}}
            </h3>

            <div class="card-tools">                
                <div class="d-flex gap-2 " role="group" aria-label="Group actions">
                    <a href="{{route('registrations.index', ['show' => 'all'])}}" type="button" class="btn btn-primary btn-sm" onclick="">
                        <i class="fa fa-globe mr-1"></i> {{__('Show All')}}
                    </a>
                </div>
            </div>

        </div>

        <div class="card-body">
            <livewire:registrations-table />
        </div>
    </div>
</div>

@endsection
