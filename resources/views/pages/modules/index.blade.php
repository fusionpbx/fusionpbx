@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Modules Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2" role="module" aria-label="Modules actions">
                    @can('module_add')
                    <a href="{{ route('modules.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">
			<table class="laravel-livewire-table table table table-striped table-hover table-bordered">
				<tbody>
                @php
                $category = "";
                @endphp
				@foreach($modules as $module)
                    @if($category != $module->module_category)
                        @php
                        $category = $module->module_category;
                        @endphp
					<tr>
						<td colspan="4" style="background-color:#f0f0f0; color: #0d6efd;">{{ $module->module_category }}</td>
					</tr>
					<tr>
						<td style="font-weight: bold;">{{ __('Label') }}</td>
						<td style="font-weight: bold;">{{ __('Status') }}</td>
						<td style="font-weight: bold;">{{ __('Action') }}</td>
						<td style="font-weight: bold;">{{ __('Enabled') }}</td>
					</tr>
                    @endif
                    <tr>
                        <td>{{ $module->module_label }}</td>
                        <td>@if($module->module_status) {{ __('Running') }} @else {{ __('Stopped') }} @endif</td>
                        <td></td>
                        <td>
                            <svg class="d-inline-block @if($module->module_enabled) text-success @else text-danger @endif laravel-livewire-tables-btn-small" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                @if($module->module_enabled)
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                                @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                                @endif
                            </svg>
                        </td>
                    </tr>
				@endforeach
				</tbody>
			</table>
        </div>
    </div>
</div>
@endsection

