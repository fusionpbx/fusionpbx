<?php
namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
	public function index()
	{
		$menus = Menu::all();

		return view("menu/index", compact("menus"));
	}

	public function getMenu()
	{
		$app_menu = [
			"items" => []
		];

		if(Auth::check())
		{
			$userGroups = Auth::user()->groups->pluck("group_uuid")->toArray();

			$sql = "
				SELECT DISTINCT mi.*
				FROM v_menu_items mi
				INNER JOIN v_menu_item_groups mig ON mig.menu_item_uuid = mi.menu_item_uuid
				WHERE mig.group_uuid IN ('" . implode("', '", $userGroups). "')
				ORDER BY mi.menu_item_order
			";

			$results = DB::select($sql);
			$results = json_decode(json_encode($results), true);

			$app_menu["items"] = $this->buildMenu($results);
		}

		return $app_menu;
	}

	private function buildMenu($elements, $parentId = null, $idField = "menu_item_uuid", $parentField = "menu_item_parent_uuid")
	{
		$branch = [];

		foreach($elements as $element)
		{
			if($element[$parentField] == $parentId)
			{
				$children = $this->buildMenu($elements, $element[$idField], $idField, $parentField);

				if($children)
				{
					$element["items"] = $children;
				}

				$branch[] = $element;
			}
		}

		return $branch;
	}

	private function buildMenuFlat($elements, $parentId = null, $level = 1, $idField = "menu_item_uuid", $parentField = "menu_item_parent_uuid")
	{
		$sortedList = [];

		foreach($elements as $key => $element)
		{
			if($element[$parentField] == $parentId)
			{
				$element["level"] = $level; // Add level field

				$sortedList[] = $element; // Add parent first

				unset($elements[$key]); // Remove processed element

				// Recursively add children
				$sortedList = array_merge($sortedList, $this->buildMenuFlat($element["items"], $element[$idField], $level + 1, $idField, $parentField));
			}
		}

		return $sortedList;
	}

	public function create()
	{
		$menu = new Menu();

		return view("menu/form", compact("menu"));
	}

	public function edit($menu_uuid)
	{
		$menu = Menu::with("items.groups", "items.items.groups")->findOrFail($menu_uuid);

		$menu_items = $this->buildMenuFlat($menu->items->toArray());

		return view("menu/form", compact("menu", "menu_items"));
	}

	public function store(Request $request)
	{
		$validated = $request->validate([
			"menu_name" => "required|string|max:255",
			"menu_language" => "required|string|max:255",
			"menu_description" => "required|string|max:255",
		]);

		Menu::create($validated);

		return redirect()->route("menu.index")->with("success", "Menu created successfully!");
	}

	public function update(Request $request, $menu_uuid)
	{
		$menu = Menu::findOrFail($menu_uuid);

		$validated = $request->validate([
			"menu_name" => "required|string|max:255",
			"menu_language" => "required|string|max:255",
			"menu_description" => "required|string|max:255",
		]);

		$menu->update($validated);

		return redirect()->route("menu.edit", $menu_uuid)->with("success", "Menu updated successfully!");
	}

	public function destroy($menu_uuid)
	{
		$menu = Menu::findOrFail($menu_uuid);

		$menu->delete();

		return redirect()->route("menu.index")->with("success", "Menu deleted successfully!");
	}
}
