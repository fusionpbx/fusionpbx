<?php
namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemGroup;
use App\Repositories\MenuRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{

	protected $menuRepository;

	public function __construct(MenuRepository $menuRepository)
	{
		$this->menuRepository = $menuRepository;
	}


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
		$this->menuRepository->create($request->validated());

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
		$this->menuRepository->update($menu, $request->validated());

		return redirect()->route("menus.index");
	}

    public function destroy(Menu $menu)
    {
        $this->menuRepository->delete($menu);

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
            $app_menu = $this->menuRepository->getApplicationMenu($userGroups);
        }

        return $app_menu;
    }
}
