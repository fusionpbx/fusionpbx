@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-phrase mr-2"></i> {{__('Phrases Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2" role="phrase" aria-label="Phrase actions">
                    @can('phrase_add')
                    <a href="{{ route('phrases.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:phrases-table />
        </div>
    </div>
</div>
@endsection

