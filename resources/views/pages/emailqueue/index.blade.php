@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="mt-3 card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-layer-group mr-2"></i> {{ __('Email Queue Table') }}
                </h3>

            <div class="card-tools">
                <form action="{{ route('emailqueue.findByStatus') }}" method="GET">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                        <option value="queued">Queued</option>
                    </select>
                </form>
            </div>
        </div>

            <div class="card-body">
                <livewire:email-queue-table />
            </div>
        </div>
    </div>
@endsection
