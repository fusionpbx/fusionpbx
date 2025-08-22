@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="mt-3 card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-layer-group mr-2"></i> {{ __('Email Queue Table') }}
                </h3>

            <div class="card-tools">
                
            </div>
        </div>

            <div class="card-body">
                <livewire:email-queue-table />
            </div>
        </div>
    </div>
@endsection
