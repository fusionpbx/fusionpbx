<?php
namespace App\Http\Controllers;

use App\Http\Requests\MenuItemRequest;
use App\Models\Domain;
use App\Models\Group;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Repositories\MenuItemRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
	protected $menuItemRepository;

	public function __construct(MenuItemRepository $menuItemRepository)
	{
		$this->menuItemRepository = $menuItemRepository;
	}

	public function create(Menu $menu)
	{
		$menu->load("children");

		$groups = Group::orderBy("group_name")->get();

		return view("pages.menuitems.form", compact("menu", "groups"));
	}

	public function store(MenuItemRequest $request)
	{
        $menuItem = $this->menuItemRepository->create($request->validated());

        $this->menuItemRepository->syncGroups($request, $menuItem);

        return redirect()->route("menus.edit", [$menuItem->menu_uuid]);
	}

	public function show(MenuItem $menuitem)
	{
		//
	}

    public function edit(Menu $menu, MenuItem $menuitem)
    {
        //$menu = $menuitem->menu;

        $query =  Group::leftJoin(Domain::getTableName(), Group::getTableName().'.domain_uuid', '=', Domain::getTableName().'.domain_uuid')
                ->select('group_uuid', 'group_protected', 'group_level', 'group_description','group_name', DB::raw("CONCAT(".Group::getTableName().".group_name,'@', IFNULL(v_domains.domain_name,'Global')) AS group_name_group"),'domain_name')
                ->orderBy('group_name')
                ->when(!auth()->user()->hasPermission('domain_select'), function($query, $currentDomain) {
                    // When the permision is not set, you can only have access to the domain groups
                    return $query->where('domain_uuid', $currentDomain->domain_uuid);
                });
        $groups = $query->get();


        return view("pages.menuitems.form", compact("menu", "menuitem", "groups"));
    }

	public function update(MenuItemRequest $request, Menu $menu, MenuItem $menuitem)
	{
        $this->menuItemRepository->update($menuitem, $request->validated());

        $this->menuItemRepository->syncGroups($request, $menuitem);

        return redirect()->route("menus.edit", [$menuitem->menu_uuid]);
	}

	public function destroy(MenuItem $menuitem)
	{
		return redirect()->route("menuitems.index");
	}
}
