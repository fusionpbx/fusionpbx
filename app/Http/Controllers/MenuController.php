<?php
namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
	public function index()
	{
		$menu = $this->getMenu();

		return response()->json($menu);
	}

	public function getMenu()
	{
		return Menu::with(['items.items'])->get()->toArray();
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
