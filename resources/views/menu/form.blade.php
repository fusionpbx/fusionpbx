@extends('layouts.app')

@section('content')

<div class="container-fluid">
	<div class="row">
		<div class="col-md-12">
			<div class="card card-primary m-2">
				<div class="card-header">
					<h3 class="card-title">Menu</h3>
				</div>
				<form method="POST" action="{{ $menu->exists ? route('menu.update', $menu->menu_uuid) : route('menu.store') }}">
					@csrf
					<div class="card-body">
						<div class="form-group">
							<label for="menu_name">Name</label>
							<input type="text" value="{{ $menu->menu_name ?? old('menu_name') }}" class="form-control" name="menu_name" id="menu_name" required>
						</div>
						<div class="form-group">
							<label for="menu_language">Language</label>
							<input type="text" value="{{ $menu->menu_language ?? old('menu_language') }}" class="form-control" name="menu_language" id="menu_language" required>
						</div>
						<div class="form-group">
							<label for="menu_description">Description</label>
							<input type="text" value="{{ $menu->menu_description ?? old('menu_description') }}" class="form-control" name="menu_description" id="menu_description" required>
						</div>
					</div>
					<div class="card-footer">
						<button type="submit" class="btn btn-primary">Save</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	@if(!empty($menu_items))
	<div class="row">
		<div class="col-md-12">
			<div class="card card-primary m-2">
				<div class="card-header">
					<h3 class="card-title">Menu Items</h3>
				</div>
				<div class="card-body p-0">
					<table class="table table-striped">
						<thead>
							<tr>
								<th>Title</th>
								<th>Groups</th>
								<th>Target</th>
								<th>Protected</th>
							</tr>
						</thead>
						<tbody>
							@foreach($menu_items as $menu_item)
							<tr class="align-middle">
								<td class="ps-{{ $menu_item["level"] * 2 }}"><a href="{{ route('menu_item.edit',  $menu_item["menu_item_uuid"]) }}">{{ $menu_item["menu_item_title"]}}</a></td>
								<td>{{ implode(", ", array_column($menu_item["groups"], "group_name")) }}</td>
								<td>{{ $menu_item["menu_item_category"] }}</td>
								<td>{{ $menu_item["menu_item_protected"] }}</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	@endif
</div>

@endsection
