@extends('layouts.app')

@section('content')

<div class="container-fluid">
	<div class="row">
		<div class="col-md-12">
			<div class="card card-primary mb-4">
				<div class="card-header">
					<h3 class="card-title">Menu Manager</h3>
				</div>
				<!-- /.card-header -->
				<div class="card-body p-0">
					<table class="table table-striped">
						<thead>
							<tr>
								<th>Name</th>
								<th>Language</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
							@foreach($menus as $menu)
							<tr class="align-middle">
								<td><a href="{{ route('menu.edit',  $menu->menu_uuid) }}">{{ $menu->menu_name }}</a></td>
								<td>{{ $menu->menu_language }}</td>
								<td>{{ $menu->menu_description }}</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection
