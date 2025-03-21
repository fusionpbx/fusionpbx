@extends('layouts.app')

@section('content')

<div class="container-fluid">
	<div class="row">
		<div class="col-md-12">
			<div class="card card-primary m-2">
				<div class="card-header">
					<h3 class="card-title">Menu Item</h3>
				</div>
				<form method="POST" action="{{ route('menu_item.update', $menu_item->menu_item_uuid) }}">
					@csrf
					<div class="card-body">
						<div class="form-group">
							<label for="menu_item_title">Title</label>
							<input type="text" value="{{ $menu_item->menu_item_title }}" class="form-control" name="menu_item_title" id="menu_item_title" required>
						</div>
						<div class="form-group">
							<label for="menu_item_link">Link</label>
							<input type="text" value="{{ $menu_item->menu_item_link }}" class="form-control" name="menu_item_link" id="menu_item_link" required>
						</div>
						<div class="form-group">
							<label for="menu_item_category">Target</label>
							<input type="text" value="{{ $menu_item->menu_item_category }}" class="form-control" name="menu_item_category" id="menu_item_category" required>
						</div>
						<!-- <div class="form-group">
							<label for="menu_item_icon">Icon</label>
							<input type="text" value="{{ $menu_item->menu_item_icon }}" class="form-control" name="menu_item_icon" id="menu_item_icon" required>
						</div> -->
						<div class="form-group">
							<label for="menu_item_parent_uuid">Parent menu</label>
							<select name="menu_item_parent_uuid" id="menu_item_parent_uuid" class="form-control">
								<option value="" {{ old('menu_item_protected', $menu_item->menu_item_parent_uuid) == '' ? 'selected' : '' }}></option>
								@foreach($menu_item->menu->children as $option)
								<option value="{{ $option->menu_item_uuid }}" {{ old('menu_item_parent_uuid', $menu_item->menu_item_parent_uuid) == $option->menu_item_uuid ? 'selected' : '' }}>{{ $option->menu_item_title }}</option>
								@endforeach
							</select>
						</div>
						<div class="form-group">
							<label>Groups</label>
							@foreach($groups as $group)
							@php
								$checked = $menu_item->groups->contains('group_uuid', $group->group_uuid);
							@endphp
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="groups[]" value="{{ $group->group_uuid }}" @if($checked) checked @endif>
								<label class="form-check-label">{{ $group->group_name }}</label>
							</div>
							@endforeach
						</div>
						<div class="form-group">
							<label for="menu_item_protected">Protected</label>
							<select name="menu_item_protected" id="menu_item_protected" class="form-control">
								<option value="" {{ old('menu_item_protected', $menu_item->menu_item_protected) == '' ? 'selected' : '' }}></option>
								<option value="True" {{ old('menu_item_protected', $menu_item->menu_item_protected) == 'True' ? 'selected' : '' }}>True</option>
								<option value="False" {{ old('menu_item_protected', $menu_item->menu_item_protected) == 'False' ? 'selected' : '' }}>False</option>
							</select>
						</div>
						<div class="form-group">
							<label for="menu_item_description">Description</label>
							<input type="text" value="{{ $menu_item->menu_item_description }}" class="form-control" name="menu_item_description" id="menu_item_description" required>
						</div>
					</div>
					<div class="card-footer">
						<button type="submit" class="btn btn-primary">Save</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection
