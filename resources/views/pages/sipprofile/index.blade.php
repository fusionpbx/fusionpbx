@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Sip Profile Table')}}
            </h3>

            <div class="card-tools">

                <div class="d-flex gap-2 " role="group" aria-label="Group actions">
                    @can('sofia_global_setting_view')
                    <a href="{{ route('users.index') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-users mr-1"></i> {{__('Settings')}}
                    </a>
                    @endcan

                    @can('sip_profile_add')
                    <a href="{{ route('sipprofiles.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan

                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:sip-profile-table/>
        </div>
    </div>
</div>
@endsection