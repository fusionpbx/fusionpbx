<?php
namespace App\Http\Controllers;

use App\Http\Requests\MenuItemRequest;
use App\Models\Group;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
	public function create(Menu $menu)
	{
		$menu->load("children");

		$groups = Group::orderBy("group_name")->get();

		return view("pages.menuitems.form", compact("menu", "groups"));
	}

	public function store(MenuItemRequest $request)
	{
		$menuitem = MenuItem::create($request->validated());

		$this->syncGroups($request, $menuitem);

		return redirect()->route("menus.edit", [$menuitem->menu_uuid]);
	}

	public function show(MenuItem $menuitem)
	{
		//
	}

	public function edit(MenuItem $menuitem)
	{
		$menu = $menuitem->menu;

		$groups = Group::orderBy("group_name")->get();

		return view("pages.menuitems.form", compact("menu", "menuitem", "groups"));
	}

	public function update(MenuItemRequest $request, MenuItem $menuitem)
	{
		$menuitem->update($request->validated());

		$this->syncGroups($request, $menuitem);

		return redirect()->route("menus.edit", [$menuitem->menu_uuid]);
	}

	public function destroy(MenuItem $menuitem)
	{
		return redirect()->route("menuitems.index");
	}

	private function syncGroups(MenuItemRequest $request, MenuItem $menuitem)
	{
		$groups = array_values($request["groups"] ?? []);

		$syncGroups = [];

		foreach($groups as $group)
		{
			$syncGroups[$group] = [
				"menu_item_group_uuid" => Str::uuid()
			];
		}

		$menuitem->groups()->sync($syncGroups);
	}
}
