<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
	public function create($menu_uuid)
	{
		$menu_item = new MenuItem();

		$menu = Menu::findOrFail($menu_uuid);

		$groups = Group::orderBy("group_name")->get();

		return view("menu/item/form", compact("menu", "menu_item", "groups"));
	}

	public function edit($menu_item_uuid)
	{
		$menu_item = MenuItem::with("groups")->findOrFail($menu_item_uuid);

		$menu = $menu_item->menu;

		$groups = Group::orderBy("group_name")->get();

		return view("menu/item/form", compact("menu", "menu_item", "groups"));
	}

	public function store(Request $request, $menu_item_uuid)
	{
		$validated = $request->validate([
			"menu_item_title" => "required|string|max:255",
			"menu_item_link" => "required|string|max:255",
			"menu_item_category" => "required|string|max:255",
			"menu_item_icon" => "string|max:255",
			// "menu_item_protected" => "string|max:255",
			"menu_item_description" => "required|string|max:255",
		]);

		$menu_item = MenuItem::create($validated);

		$groups = array_values($request["groups"] ?? []);

		$sync_groups = [];

		foreach($groups as $groups)
		{
			$sync_groups[$groups] = [
				"menu_item_group_uuid" => Str::uuid()
			];
		}

		$menu_item->groups()->sync($sync_groups);

		return redirect()->route("menu_item.edit", $menu_item_uuid)->with("success", "Menu created updated successfully!");
	}

	public function update(Request $request, $menu_item_uuid)
	{
		$menu_item = MenuItem::findOrFail($menu_item_uuid);

		$validated = $request->validate([
			"menu_item_title" => "required|string|max:255",
			"menu_item_link" => "required|string|max:255",
			"menu_item_category" => "required|string|max:255",
			"menu_item_icon" => "string|max:255",
			// "menu_item_protected" => "string|max:255",
			"menu_item_description" => "required|string|max:255",
		]);

		$menu_item->update($validated);

		$groups = array_values($request["groups"] ?? []);

		$sync_groups = [];

		foreach($groups as $groups)
		{
			$sync_groups[$groups] = [
				"menu_item_group_uuid" => Str::uuid()
			];
		}

		$menu_item->groups()->sync($sync_groups);

		return redirect()->route("menu_item.edit", $menu_item_uuid)->with("success", "Menu item updated successfully!");
	}

	public function destroy($menu_item_uuid)
	{
		$menu_item = MenuItem::findOrFail($menu_item_uuid);

		$menu_uuid = $menu_item->menu->menu_uuid;

		$menu_item->delete();

		return redirect()->route("menu.index", [$menu_uuid])->with("success", "Menu item deleted successfully!");
	}
}
