<?php

namespace App\Repositories;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MenuRepository
{
    protected $menu;
    protected $menuItem;
    protected $menuItemGroup;

    public function __construct(Menu $menu, MenuItem $menuItem, MenuItemGroup $menuItemGroup)
    {
        $this->menu = $menu;
        $this->menuItem = $menuItem;
        $this->menuItemGroup = $menuItemGroup;
    }


    public function getAll()
    {
        return $this->menu->all();
    }


    public function findByUuid($uuid)
    {
        return $this->menu->findOrFail($uuid);
    }


    public function create(array $data)
    {
        return $this->menu->create($data);
    }


    public function update(Menu $menu, array $data)
    {
        return $menu->update($data);
    }


    public function delete(Menu $menu)
    {
        return $menu->delete();
    }

    public function getMenuItemsByUserGroups(array $userGroups)
    {
        $sql = "
            SELECT DISTINCT mi.*
            FROM ".$this->menuItem->getTableName()." mi
            INNER JOIN ".$this->menuItemGroup->getTableName()." mig ON mig.menu_item_uuid = mi.menu_item_uuid
            WHERE mig.group_uuid IN ('" . implode("', '", $userGroups). "')
            ORDER BY mi.menu_item_order
        ";

        $results = DB::select($sql);
        return json_decode(json_encode($results), true);
    }

    public function buildMenu(array $elements, $parentId = null, $idField = "menu_item_uuid", $parentField = "menu_item_parent_uuid")
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

    public function buildMenuFlat(array $elements, $parentId = null, $level = 1, $idField = "menu_item_uuid", $parentField = "menu_item_parent_uuid")
    {
        $sortedList = [];

        foreach($elements as $key => $element)
        {
            if($element[$parentField] == $parentId)
            {
                $element["level"] = $level; // Add level field

                $sortedList[] = $element; // Add parent first

                unset($elements[$key]); // Remove processed element

                $sortedList = array_merge($sortedList, $this->buildMenuFlat($element["items"], $element[$idField], $level + 1, $idField, $parentField));
            }
        }

        return $sortedList;
    }


    public function getApplicationMenu(array $userGroups)
    {
        $app_menu = [
            "items" => []
        ];

        if (!empty($userGroups)) {
            $menuItems = $this->getMenuItemsByUserGroups($userGroups);
            $app_menu["items"] = $this->buildMenu($menuItems);
        }

        return $app_menu;
    }
}