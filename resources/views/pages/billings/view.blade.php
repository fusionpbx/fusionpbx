@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Billing Invoices')}}
            </h3>
        </div>

        <div class="card-body">
			<livewire:billing-view :billing="$billing"/>
        </div>
    </div>
</div>
@endsection

