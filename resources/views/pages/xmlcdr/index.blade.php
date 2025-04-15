@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-domain mr-2"></i> {{__('Call Detail Records Table')}}
            </h3>
        </div>

        <div class="card-body">
            <livewire:xml-cdr-table/>
        </div>
    </div>
</div>
@endsection

