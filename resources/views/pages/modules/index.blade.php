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

                    @can('module_edit')
                        @if($modules)
                            <a href="" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#start_modules" >
                                <i class="fa fa-play" aria-hidden="true"></i> {{__('Start')}}
                            </a>

                            <a href="" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#stop_modules" >
                                <i class="fa fa-stop" aria-hidden="true"></i> {{__('Stop')}}
                            </a>

                            <a href="" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#toggle_modules" >
                                <i class="fa fa-toggle-on" aria-hidden="true"></i> {{__('Toggle')}}
                            </a>
                        @endif
                    @endcan

                    @can('module_add')
                    <a href="{{ route('modules.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan

                    @can('module_delete')
                        <a href="" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#delete_modules" >
                            <i class="fa fa-trash" aria-hidden="true"></i> {{__('Delete')}}
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('modules.bulk') }}">
                @csrf
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
                            <td colspan="6" style="background-color:#f0f0f0; color: #0d6efd;">{{ $module->module_category }}</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input select-group" data-category="{{ \Str::slug($module->module_category) }}"></td>
                            <td style="font-weight: bold;">{{ __('Label') }}</td>
                            <td style="font-weight: bold;">{{ __('Status') }}</td>
                            <td style="font-weight: bold;">{{ __('Action') }}</td>
                            <td style="font-weight: bold;">{{ __('Enabled') }}</td>
                            <td style="font-weight: bold;">{{ __('Description') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_modules[]" value="{{ $module->module_uuid }}" class="form-check-input module-checkbox" data-category="{{ \Str::slug($module->module_category) }}">
                            </td>
                            <td><a href="{{ route('modules.edit', [$module->module_uuid]) }}">{{ $module->module_label }}</a></td>
                            <td>@if($module->module_status) {{ __('Running') }} @else {{ __('Stopped') }} @endif</td>
                            <td>
                                @if($module->module_status)
                                <a href="{{ route('modules.stop', [$module->module_uuid]) }}" class="btn btn-primary btn-sm"><i class="fa fa-stop" aria-hidden="true"></i> Stop</a>
                                @else
                                <a href="{{ route('modules.start', [$module->module_uuid]) }}" class="btn btn-primary btn-sm"><i class="fa fa-play" aria-hidden="true"></i> Start</a>
                                @endif
                            </td>
                            <td>
                                <svg class="d-inline-block @if($module->module_enabled) text-success @else text-danger @endif laravel-livewire-tables-btn-small" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    @if($module->module_enabled)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                                    @else
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                                    @endif
                                </svg>
                            </td>
                            <td>{{ $module->module_description }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="modal fade" id="start_modules" tabindex="-1" aria-labelledby="start_modules" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you wish to START these modules?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="action" value="start" class="btn btn-primary">{{ __('Continue') }}</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('Cancel')}}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="stop_modules" tabindex="-1" aria-labelledby="stop_modules" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you wish to STOP these modules?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="action" value="stop" class="btn btn-primary">{{ __('Continue') }}</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('Cancel')}}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="toggle_modules" tabindex="-1" aria-labelledby="toggle_modules" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you wish to TOGGLE these modules?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="action" value="toggle" class="btn btn-primary">{{ __('Continue') }}</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('Cancel')}}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="delete_modules" tabindex="-1" aria-labelledby="delete_modules" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you wish to DELETE these modules?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="action" value="delete" class="btn btn-primary">{{ __('Continue') }}</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('Cancel')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push("scripts")
<script>
document.addEventListener("DOMContentLoaded", function()
{
    document.querySelectorAll('.select-group').forEach(groupCheckbox => {
        groupCheckbox.addEventListener('change', function()
        {
            const category = this.dataset.category;
            const checkboxes = document.querySelectorAll('.module-checkbox[data-category="' + category + '"]');

            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    });
});
</script>

@endpush
