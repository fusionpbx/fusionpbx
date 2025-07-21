@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Billing Deals Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2" role="billing" aria-label="Billing deals actions">
                    <a href="{{ route('billings.deals.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:billings-deals-table/>
        </div>
    </div>
</div>
@endsection

