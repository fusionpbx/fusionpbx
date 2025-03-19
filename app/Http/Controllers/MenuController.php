<?php
namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
	public function index()
	{
		$menu = $this->getMenu();

		return response()->json($menu);
	}

	public function getMenu()
	{
		$menu = [
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

			$menu["items"] = $this->buildMenu($results);
		}

		return $menu;
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

	public function store(Request $request)
	{
		$request->validate([
			"name" => "required|string|max:255",
			"url" => "nullable|string|max:255",
			"parent_id" => "nullable|exists:menus,id",
			"order" => "integer"
		]);

		$menu = Menu::create($request->all());

		return response()->json($menu, 201);
	}
}
