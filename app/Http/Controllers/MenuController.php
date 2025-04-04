<?php
namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
	public function index()
	{
		return view('pages.menus.index');
	}

	public function create()
	{
		return view("pages.menus.form");
	}

	public function store(MenuRequest $request)
	{
		Menu::create($request->validated());

		return redirect()->route("menus.index");
	}

    public function show(Menu $menu)
    {
        //
    }

	public function edit(Menu $menu)
	{
		return view("pages.menus.form", compact("menu"));
	}

	public function update(MenuRequest $request, Menu $menu)
	{
		$menu->update($request->validated());

		return redirect()->route("menus.index");
	}

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('menus.index');
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
				FROM ".MenuItem::getTableName()." mi
				INNER JOIN ".MenuItemGroup::getTableName()." mig ON mig.menu_item_uuid = mi.menu_item_uuid
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
}
