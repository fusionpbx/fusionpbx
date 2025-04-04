@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($menu) ? 'Edit Menu' : 'Create Menu' }}
            </h3>
		</div>

		<form method="POST" action="{{ isset($menu) ? route('menus.update', $menu->menu_uuid) : route('menus.store') }}">
			@csrf
            @if(isset($menu))
                @method('PUT')
            @endif
			<div class="card-body">
				<div class="form-group mb-3">
					<label for="menu_name">Name</label>
					<input type="text" value="{{ $menu->menu_name ?? old('menu_name') }}" class="form-control" name="menu_name" id="menu_name" required>
				</div>
				<div class="form-group mb-3">
					<label for="menu_language">Language</label>
					<input type="text" value="{{ $menu->menu_language ?? old('menu_language') }}" class="form-control" name="menu_language" id="menu_language" required>
				</div>
				<div class="form-group mb-3">
					<label for="menu_description">Description</label>
					<input type="text" value="{{ $menu->menu_description ?? old('menu_description') }}" class="form-control" name="menu_description" id="menu_description" required>
				</div>
			</div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($menu) ? 'Update Menu' : 'Create Menu' }}
                </button>
				@if(isset($menu))
                <a href="{{ route('menuitems.create', ['menu' => $menu]) }}" class="btn btn-success ml-2 px-4 py-2" style="border-radius: 4px;">
                    Add Menu Item
                </a>
				@endif
                <a href="{{ route('menus.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
		</form>
	</div>

	<div class="card card-primary mt-3">
		<div class="card-header">
			<h3 class="card-title">Menu Items</h3>
		</div>
		<div class="card-body">
			<livewire:menu-items-table menu_uuid="{{ $menu->menu_uuid }}"/>
		</div>
	</div>
</div>

@endsection
