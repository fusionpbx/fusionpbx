@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($module) ? 'Edit Module' : 'Create Module' }}
            </h3>
        </div>

        <form action="{{ isset($module) ? route('modules.update', $module->module_uuid) : route('modules.store') }}"
              method="POST">
            @csrf
            @if(isset($module))
                @method('PUT')
            @endif

            <div class="card-body">
				<div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_label" class="form-label">Label</label>
                            <input
                                type="text"
                                class="form-control @error('module_label') is-invalid @enderror"
                                id="module_label"
                                name="module_label"
                                placeholder="Enter module label"
                                value="{{ old('module_label', $module->module_label ?? '') }}"
                                required
                            >
                            @error('module_label')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_name" class="form-label">Module Name</label>
                            <input
                                type="text"
                                class="form-control @error('module_name') is-invalid @enderror"
                                id="module_name"
                                name="module_name"
                                placeholder="Enter module name"
                                value="{{ old('module_name', $module->module_name ?? '') }}"
                                required
                            >
                            @error('module_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_order" class="form-label">Order</label>
                            <input
                                type="text"
                                class="form-control @error('module_order') is-invalid @enderror"
                                id="module_order"
                                name="module_order"
                                placeholder="Enter module order"
                                value="{{ old('module_order', $module->module_order ?? '') }}"
                                required
                            >
                            @error('module_order')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

				<div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_category" class="form-label">Module Category</label>
                            <select
                                class="form-select @error('module_category') is-invalid @enderror"
                                id="module_category"
                                name="module_category"
                            >
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(old('module_category', $module->module_category ?? null) == $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                                <option value="Other">Other</option>
                            </select>
                            @error('module_category')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Enabled</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="module_enabled" name="module_enabled" value="true" {{ old('module_enabled', $module->module_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="module_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('module_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Default Enabled</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="module_default_enabled" name="module_default_enabled" value="true" {{ old('module_default_enabled', $module->module_default_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="module_default_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('module_default_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_description" class="form-label">Module Description</label>
                            <textarea
                                class="form-control @error('module_description') is-invalid @enderror"
                                id="module_description"
                                name="module_description"
                                rows="3"
                                placeholder="Enter module description"
                            >{{ old('module_description', $module->module_description ?? '') }}</textarea>
                            @error('module_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($module) ? 'Update Module' : 'Create Module' }}
                </button>
                <a href="{{ route('modules.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
