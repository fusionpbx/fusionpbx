<?php

namespace App\Repositories;

use App\Http\Requests\MenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Support\Str;

class MenuItemRepository
{

    protected $model;


    public function __construct(MenuItem $menuItem)
    {
        $this->model = $menuItem;
    }

    public function create(array $data): MenuItem
    {
        return $this->model->create($data);
    }

    public function update(MenuItem $menuItem, array $data): bool
    {
        return $menuItem->update($data);
    }


    public function syncGroups(MenuItemRequest $request, MenuItem $menuItem): void
    {
        $groups = array_values($request["groups"] ?? []);

        $syncGroups = [];

        foreach($groups as $group)
        {
            $syncGroups[$group] = [
                "menu_item_group_uuid" => Str::uuid()
            ];
        }

        $menuItem->groups()->sync($syncGroups);
    }
}