<?php
namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
	public function index()
	{
		$menus = Menu::whereNull("parent_id")->with("childrenRecursive")->orderBy("order")->get();

		return response()->json($menus);
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
