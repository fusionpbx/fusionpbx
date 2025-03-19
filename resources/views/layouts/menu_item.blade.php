<li class="nav-item">
	<a href="{{ empty($item['items']) ? $item['menu_item_link'] : '#' }}" class="nav-link">
		<p>
			<span>{{ $item["menu_item_title"] }}</span>
			@if(!empty($item["items"]))
				<i class="nav-arrow bi bi-chevron-right"></i>
			@endif
		</p>
	</a>
	@if(!empty($item["items"]))
	<ul class="nav nav-treeview">
		@foreach($item["items"] as $item)
			@include('layouts.menu_item')
		@endforeach
	</ul>
	@endif
</li>
