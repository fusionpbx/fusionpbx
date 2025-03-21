<ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
	@foreach($app_menu["items"] as $item)
		@include('layouts.menu_item')
	@endforeach
</ul>
